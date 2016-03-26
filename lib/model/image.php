<?php
namespace Podlove\Model;

use Symfony\Component\Yaml\Yaml;
use \Podlove\Cache\TemplateCache;

/**
 * Image Object
 * 
 * Usage
 * 
 * 	// get url, resized to 100px width, keep aspect ratio
 * 	$image = (new Image($url))->setWidth(100)->url();
 * 
 * 	// get url, resized to 100px width and 50px height, cropped
 * 	$image = (new Image($url))
 *  	->setWidth(100)
 *   	->setHeight(50)
 * 	  	->setCrop(true)
 * 		->url();
 * 
 *   // get image tag with custom alt text and title
 *   $image = (new Image($url))->image(["alt" => "custom alt", "title" => "custom title"]);
 */
class Image {

	// URL/file properties
	private $id;
	private $source_url;
	private $file_name;
	private $file_extension;
	private $upload_basedir;
	private $upload_baseurl;
	
	// image properties
	private $crop   = false;
	private $width  = NULL;
	private $height = NULL;

	// html rendering properties
	private $retina = true;

	/**
	 * Create image object
	 * 
	 * Manage remote image objects. Cache locally so we can resize and serve 
	 * optimized image dimensions.
	 * 
	 * @param string $url  Remote image URL
	 * @param string $name (optional) image file name prefix
	 */
	public function __construct($url, $file_name = '') {
		$this->source_url = trim($url);
		$this->file_name = sanitize_title($file_name);
		
		// manually remove troublemaking invisible character
		// @see https://community.podlove.org/t/solved-kind-of-cover-art-disappears-caching-issue/478/
		// @see https://sendegate.de/t/problem-mit-caching-von-grafiken/2947
		$this->file_name = str_ireplace("%c2%ad", "", $this->file_name);
		
		$this->file_extension = $this->extract_file_extension();
		$this->id = md5($url . $this->file_name);

		// create subdirectories to avoid too many directories in the root directory
		$id_directory = substr($this->id, 0, 2) . '/' . substr($this->id, 2);

		$this->upload_basedir = self::cache_dir() . $id_directory;
		$this->upload_baseurl = content_url('cache/podlove/') . $id_directory;
	}

	public static function cache_dir() {
		return trailingslashit(WP_CONTENT_DIR) . 'cache/podlove/';
	}

	/**
	 * Delete all image caches.
	 */
	public static function flush_cache() {

		$dir = self::cache_dir();

		if (!file_exists($dir))
			return;

		$it    = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($files as $file) {
			if ($file->isDir()){
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		rmdir($dir);
	}

	/**
	 * Set to true if resizing should crop when necessary.
	 * 
	 * @param  bool $crop Crop image if given dimensions deviate from original aspect ratio.
	 * @return $this for chaining
	 */
	public function setCrop($crop) {
		$this->crop = (bool) $crop;
		return $this;
	}

	public function setWidth($width) {

		if (!$width)
			return $this;

		$this->width = (int) $width;
		$this->height = 0;
		$this->determineMissingDimension();

		return $this;
	}

	public function setHeight($height) {

		if (!$height)
			return $this;

		$this->height = (int) $height;
		$this->width = 0;
		$this->determineMissingDimension();

		return $this;
	}

	private function determineMissingDimension() {

		if (!$this->height) {
			$known_dimension   = 'width';
			$missing_dimension = 'height';
		} elseif (!$this->width) {
			$known_dimension   = 'height';
			$missing_dimension = 'width';
		}

		@list($width, $height) = getimagesize($this->original_file());
		if ($width && $height)
			$this->$missing_dimension = round($this->$known_dimension / ${$known_dimension} * ${$missing_dimension});
	}

	public function setRetina($retina) {
		$this->retina = (bool) $retina;
		return $this;
	}

	/**
	 * Get URL for resized image.
	 * 
	 * Examples
	 * 
	 * 	$image->url(); // returns image URL
	 * 
	 * @return string image URL
	 */
	public function url() {

		if (!$this->file_extension) {
			\Podlove\Log::get()->addWarning(sprintf( __( 'Unable to determine file extension for %s.' ), $this->source_url ));
			return $this->source_url;
		}

		// fetch original if we don't have it — until then, return the original URL
		if (!$this->source_exists()) {
			$this->schedule_download_source();
			return $this->source_url;
		}

		if (!file_exists($this->resized_file())) {
			$this->schedule_image_resize();
			return $this->source_url;
		}

		return $this->resized_url();
	}

	/**
	 * Get HTML image tag for resized image.
	 * 
	 * Examples
	 * 
	 * 	$image->image(); // returns image tag
	 * 
	 * @param  array $args List of arguments
	 * 		- id: Set image tag "id" attribute.
	 * 		- class: Set image tag "class" attribute.
	 * 		- style: Set image tag "style" attribute.
	 * 		- alt: Set image tag "alt" attribute.
	 * 		- title: Set image tag "title" attribute.
	 *      - width: Image width. Set width and leave height blank to keep the orinal aspect ratio.
	 * 		- height: Image height. Set height and leave width blank to keep the orinal aspect ratio.
	 *   	- attributes: List of other HTML attributes, for example: ['data-foo' => 'bar']
	 * @return string HTML image tag
	 */
	public function image($args = []) {

		$defaults = [
			'id'         => '',
			'class'      => '',
			'style'      => '',
			'alt'        => '',
			'title'      => '',
			'width'      => $this->width,
			'height'     => $this->height,
			'attributes' => []
		];
		$args = wp_parse_args($args, $defaults);

		// put everything in 'attributes' for easy iteration
		foreach (['id', 'class', 'style', 'alt', 'title', 'width', 'height'] as $attr) {
			if ($args[$attr])
				$args['attributes'][$attr] = $args[$attr];
		}

		$dom = new \Podlove\DomDocumentFragment;
		$img = $dom->createElement('img');
		
		foreach ($args['attributes'] as $key => $value) {
			$img->setAttribute($key, $value);
		}

		$img->setAttribute('src', $this->url());

		if ($this->retina && $srcset = $this->srcset())
			$img->setAttribute('srcset', $srcset);			

		$dom->appendChild($img);
		
		return (string) $dom;
	}

	/**
	 * Generate srcset attribute for img tag
	 * 
	 * @return string|NULL
	 */
	private function srcset() {
		@list($max_width, $max_height) = getimagesize($this->original_file());

		if ($this->width * 2 > $max_width)
			return NULL;

		$sizes = ['1x' => $this->url()];

		$img2x = clone $this;
		$img2x = $img2x->setWidth($this->width * 2)->url();
		$sizes['2x'] = $img2x;

		if ($this->width * 3 <= $max_width) {
			$img3x = clone $this;
			$img3x = $img3x->setWidth($this->width * 3)->url();
			$sizes['3x'] = $img3x;
		}

		$sources = [];
		foreach ($sizes as $factor => $url) {
			$sources[] = $url . ' ' . $factor;
		}

		return implode(", ", $sources);
	}

	public function schedule_download_source() {
		if (!wp_next_scheduled('podlove_download_image_source', [$this->source_url, $this->file_name]))
			wp_schedule_single_event(time(), 'podlove_download_image_source', [$this->source_url, $this->file_name]);
	}

	public function schedule_image_resize() {
		$args = [$this->source_url, $this->file_name, $this->width, $this->height, $this->crop];
		if (!wp_next_scheduled('podlove_download_image_resize', $args))
			wp_schedule_single_event(time(), 'podlove_download_image_resize', $args);
	}

	public function file_name($size_slug) {
		if ($this->file_name) {
			return $this->file_name . '_' . $size_slug . '.' . $this->file_extension;
		} else {
			return $size_slug . '.' . $this->file_extension;
		}
	}

	private function source_exists() {
		return is_file($this->original_file());
	}

	private function cache_file() {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, 'cache.yml']);
	}

	private function original_file() {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $this->file_name('original')]);
	}

	private function resized_file() {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $this->file_name($this->size_slug())]);
	}

	private function original_url() {
		return implode('/', [$this->upload_baseurl, $this->file_name('original')]);
	}

	private function resized_url() {
		return implode('/', [$this->upload_baseurl, $this->file_name($this->size_slug())]);
	}

	public function generate_resized_copy() {
		$image = wp_get_image_editor($this->original_file());

		if (is_wp_error($image))
			return;

		$image->resize($this->width, $this->height, $this->crop);
		$image->save($this->resized_file());

		// when a new image size is created, Templace Cache must be cleared
		TemplateCache::get_instance()->setup_global_purge();
	}

	private function size_slug() {

		$crop = $this->crop ? 'c' : '';

		if ($this->width || $this->height)
			return $this->width . 'x' . $this->height . $crop;
		else
			return 'original';
	}

	public function redownload_source() {
		$this->download_source();
		$this->delete_resized_versions();
	}

	private function delete_resized_versions() {
		$resized_versions = implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, "*x*.*"]);
		array_map('unlink', glob($resized_versions));
	}

	public function download_source() {

  		// for download_url()
   		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$result = $this->download_url($this->source_url);

		if (is_wp_error($result)) {
			\Podlove\Log::get()->addWarning(
				sprintf(__( 'Unable to download image. %s.' ), $result->get_error_message()),
				['url' => $this->source_url]
			);
			return;
		}

		list($temp_file, $response) = $result;

		if (is_wp_error($temp_file)) {
			\Podlove\Log::get()->addWarning(
				sprintf(__( 'Unable to download image. %s.' ), $temp_file->get_error_message()),
				['url' => $this->source_url]
			);
		}

		if (!wp_mkdir_p($this->upload_basedir))
			\Podlove\Log::get()->addWarning(sprintf(__( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $this->upload_basedir));

		$this->save_cache_data($response);

		$move_new_file = @rename($temp_file, $this->original_file());

		if ( false === $move_new_file )
			\Podlove\Log::get()->addWarning(sprintf(__('The downloaded image could not be moved to %s.' ), $this->original_file()));

		@unlink($temp_file);

		$this->add_donotbackup_dotfile();
	}

	private function add_donotbackup_dotfile() {
		file_put_contents(
			trailingslashit(self::cache_dir()) . '.donotbackup', 
			"Backup plugins are encouraged to not backup folders and subfolders when this file is inside.\n"
		);
	}

	/**
	 * Save data relevant for cache invalidation to file.
	 * 
	 * @param  array $response
	 */
	private function save_cache_data($response) {

		$cache_info = [
			'source'        => $this->source_url,
			'filename'      => $this->file_name,
			'etag'          => wp_remote_retrieve_header($response, 'etag'),
			'last-modified' => wp_remote_retrieve_header($response, 'last-modified'),
			'expires'       => wp_remote_retrieve_header($response, 'expires'),
		];

		file_put_contents($this->cache_file(), Yaml::dump($cache_info));
	}

	/**
	 * Downloads a url to a local temporary file using the WordPress HTTP Class.
	 * Please note, That the calling function must unlink() the file.
	 * 
	 * This is a modified copy of WP Core download_url().
	 * I copied it because I need to look into the header of the response but
	 * unfortunately the original implementation does not expose it.
	 *
	 * @param string $url the URL of the file to download
	 * @param int $timeout The timeout for the request to download the file default 300 seconds
	 * @return mixed WP_Error on failure, array with Filename & http response on success.
	 */
	private function download_url( $url, $timeout = 300 ) {
		//WARNING: The file is not automatically deleted, The script must unlink() the file.
		if ( ! $url )
			return new \WP_Error('http_no_url', __('Invalid URL Provided.'));

		$tmpfname = wp_tempnam($url);
		if ( ! $tmpfname )
			return new \WP_Error('http_no_file', __('Could not create Temporary file.'));

		$response = wp_safe_remote_get( $url, array( 'timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname ) );

		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
			return $response;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ){
			unlink( $tmpfname );
			return new \WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		return [$tmpfname, $response];
	}

	private function extract_file_extension() {
		$url = parse_url($this->source_url);
		return pathinfo($url['path'], PATHINFO_EXTENSION);
	}
}
