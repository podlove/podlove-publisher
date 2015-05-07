<?php
namespace Podlove\Model;

class Image {

	private $id;
	private $source_url;
	private $file_name;
	private $file_extension;
	private $upload_basedir;
	private $upload_baseurl;
	
	private $crop = false;

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

	/**
	 * Get URL for resized image.
	 * 
	 * Examples
	 * 
	 * 	$image->url();       // returns the unresized image URL
	 *  $image->url(10, 20); // returns resized / cropped image URL
	 *  $image->url(10);     // returns image URL resized to 10x10
	 * 
	 * Note: It is not _guaranteed_ to get back the resized image. If it is 
	 * not ready yet, the source URL will be returned.
	 * 
	 * @param  int|NULL $width  Image width. If NULL, the image is not resized. Default: NULL.
	 * @param  int|NULL $height Image height. If NULL, it takes the value of $width. Default: NULL.
	 * @return string image URL
	 */
	public function url($width = NULL, $height = NULL) {

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
		if (!file_exists($this->resized_file($width, $height)))
			$this->generate_resized_copy($width, $height);

		return $this->resized_url($width, $height);
	}

	/**
	 * Get HTML image tag for resized image.
	 * 
	 * Examples
	 * 
	 * 	$image->image();       // returns the unresized image tag
	 *  $image->image(10, 20); // returns resized / cropped image tag
	 *  $image->image(10);     // returns image tag resized to 10x10
	 * 
	 * Note: It is not _guaranteed_ to get back the resized image. If it is 
	 * not ready yet, the source URL will be returned.
	 * 
	 * @param  int|NULL $width    Image width. If NULL, the image is not resized. Default: NULL.
	 * @param  int|NULL $height   Image height. If NULL, it takes the value of $width. Default: NULL.
	 * @param  string|NULL $alt   Image alt-text. If NULL, it defaults to $file_name. Default: NULL.
	 * @param  string|NULL $title Image title-text. If NULL, it defaults to $file_name. Default: NULL.
	 * @return string HTML image tag
	 */
	public function image($width = NULL, $height = NULL, $alt = NULL, $title = NULL) {

		if (is_null($alt))
			$alt = $this->file_name;

		if (is_null($title))
			$title = $this->file_name;

		$dom = new \Podlove\DomDocumentFragment;
		$img = $dom->createElement('img');
		$img->setAttribute('src', $this->url($width, $height));
		$img->setAttribute('alt', $alt);
		$img->setAttribute('title', $title);
		$dom->appendChild($img);
		
		return (string) $dom;
	}

	public function schedule_download_source() {
		if (!wp_next_scheduled('podlove_download_image_source', [$this->source_url, $this->file_name]))
			wp_schedule_single_event(time(), 'podlove_download_image_source', [$this->source_url, $this->file_name]);
	}

	public function file_name($size_slug) {
		if ($this->file_name) {
			return $this->file_name . '_' . $size_slug;
		} else {
			return $size_slug;
		}
	}

	private function source_exists() {
		return is_file($this->original_file());
	}

	private function original_file() {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $this->file_name('original')]) . '.' . $this->file_extension;
	}

	private function resized_file($width, $height) {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $this->file_name($this->size_slug($width, $height))]) . '.' . $this->file_extension;
	}

	private function original_url() {
		return implode('/', [$this->upload_baseurl, $this->file_name('original')]) . '.' . $this->file_extension;
	}

	private function resized_url($width, $height) {
		return implode('/', [$this->upload_baseurl, $this->file_name($this->size_slug($width, $height))]) . '.' . $this->file_extension;
	}

	private function generate_resized_copy($width, $height) {
		$image = wp_get_image_editor($this->original_file());

		if (is_wp_error($image))
			return;

		$image->resize($width, $height, $this->crop);
		$image->save($this->resized_file($width, $height));
	}

	private function size_slug($width, $height) {

		$crop = $this->crop ? 'c' : '';

		if ($width || $height)
			return $width . 'x' . $height . $crop;
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