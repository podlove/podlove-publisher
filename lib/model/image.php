<?php
namespace Podlove\Model;

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
 *   $image = (new Image($url))->image("custom alt", "custom title");
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
	private $retina = false;

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
		$this->file_extension = $this->extract_file_extension();
		$this->id = md5($url . $this->file_name);

		$upload = wp_upload_dir();
		$this->upload_basedir = implode(DIRECTORY_SEPARATOR, [$upload['basedir'], 'podlove', $this->id]);
		$this->upload_baseurl = implode('/', [$upload['baseurl'], 'podlove', $this->id]);
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

		// resize if we don't have that size yet
		if (!file_exists($this->resized_file()))
			$this->generate_resized_copy();

		return $this->resized_url();
	}

	/**
	 * Get HTML image tag for resized image.
	 * 
	 * Examples
	 * 
	 * 	$image->image(); // returns image tag
	 * 
	 * @param  string|NULL $alt   Image alt-text. If NULL, it defaults to $file_name. Default: NULL.
	 * @param  string|NULL $title Image title-text. If NULL, it defaults to $file_name. Default: NULL.
	 * @return string HTML image tag
	 */
	public function image($alt = NULL, $title = NULL) {

		if (is_null($alt))
			$alt = $this->file_name;

		if (is_null($title))
			$title = $this->file_name;

		$dom = new \Podlove\DomDocumentFragment;
		$img = $dom->createElement('img');
		$img->setAttribute('src', $this->url());

		if ($this->retina && $srcset = $this->srcset())
			$img->setAttribute('srcset', $srcset);			

		if ($this->width)
			$img->setAttribute('width', $this->width);

		if ($this->height)
			$img->setAttribute('height', $this->height);

		$img->setAttribute('alt', $alt);
		$img->setAttribute('title', $title);
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

	private function generate_resized_copy() {
		$image = wp_get_image_editor($this->original_file());

		if (is_wp_error($image))
			return;

		$image->resize($this->width, $this->height, $this->crop);
		$image->save($this->resized_file());
	}

	private function size_slug() {

		$crop = $this->crop ? 'c' : '';

		if ($this->width || $this->height)
			return $this->width . 'x' . $this->height . $crop;
		else
			return 'original';
	}

	public function download_source() {

  		// for download_url()
   		require_once(ABSPATH . 'wp-admin/includes/file.php');

		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $this->source_url, $matches );
		$file_array = array();
		$file_array['name'] = basename( $matches[0] );

		// Download $this->source_url to temp location.
		$file_array['tmp_name'] = download_url( $this->source_url );

		// If error storing temporarily, return the error.
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return $file_array['tmp_name']; // fixme log error
		}

		if (!wp_mkdir_p($this->upload_basedir)) {
			\Podlove\Log::get()->addWarning(
				sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $this->upload_basedir )
			);
		}

		$move_new_file = @ rename( $file_array['tmp_name'], $this->original_file() );

		if ( false === $move_new_file ) {
			error_log(print_r(sprintf( __('The uploaded file could not be moved to %s.' ), $this->original_file() ), true));
		}

		@ unlink($file_array['tmp_name']);
	}

	private function extract_file_extension() {
		$url = parse_url($this->source_url);
		return pathinfo($url['path'], PATHINFO_EXTENSION);
	}
}