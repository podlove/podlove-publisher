<?php
namespace Podlove\Model;

class Image {

	private $id;
	private $source_url;
	private $upload_basedir;
	private $upload_baseurl;

	public function __construct($url) {
		$this->source_url = trim($url);
		$this->id = md5($url);

		$upload = wp_upload_dir();
		$this->upload_basedir = implode(DIRECTORY_SEPARATOR, [$upload['basedir'], 'podlove', $this->id]);
		$this->upload_baseurl = implode('/', [$upload['baseurl'], 'podlove', $this->id]);
	}

	public function schedule_download_source() {
		if (!wp_next_scheduled('podlove_download_image_source', [$this->source_url]))
			wp_schedule_single_event(time(), 'podlove_download_image_source', [$this->source_url]);
	}

	public function url($width = NULL, $height = NULL) {

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

	private function source_exists() {
		return is_file($this->original_file());
	}

	private function original_file() {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, 'original']) . '.jpg';
	}

	private function resized_file($width, $height) {
		return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, self::size_slug($width, $height)]) . '.jpg';
	}

	private function original_url() {
		return implode('/', [$this->upload_baseurl, 'original']) . '.jpg';
	}

	private function resized_url($width, $height) {
		return implode('/', [$this->upload_baseurl, self::size_slug($width, $height)]) . '.jpg';
	}

	private function generate_resized_copy($width, $height) {
		$image = wp_get_image_editor($this->original_file());

		if (is_wp_error($image))
			return;

		$image->resize($width, $height);
		$image->save($this->resized_file($width, $height));
	}

	private static function size_slug($width, $height) {
		$size = '';
		if ($width && $height) {
			$size = $width . 'x' . $height;
		} elseif ($width) {
			$size = $width . 'x' . $width;
		} else {
			$size = 'original';
		}

		return $size;
	}

	public function download_source() {

  		// for download_url()
   		require_once(ABSPATH . 'wp-admin/includes/file.php');

		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $this->source_url, $matches );
		$file_array = array();
		$file_array['name'] = basename( $matches[0] );

		// Download$this->source_url to temp location.
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


}