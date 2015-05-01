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

		error_log(print_r([
			'basedir' => $this->upload_basedir,
			'baseurl' => $this->upload_baseurl,
		], true));

		// todo only do this before saving files
		if (!wp_mkdir_p($this->upload_basedir)) {
			$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $this->upload_basedir );
			\Podlove\Log::get()->addWarning( 'This is a warning.' );
		}
	}

	public function url($width = NULL, $height = NULL) {

		$size = '';
		if ($width && $height) {
			$size = $width . 'x' . $height;
		} elseif ($width) {
			$size = $width . 'x' . $width;
		}

		$file = implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $size]) '.jpg';
		if (file_exists($file)) {
			return implode('.', [$this->upload_baseurl, $size]) '.jpg';;
		} elseif (/* try to resize original if it exists */) {

		} else {
			// fetch original via http, then resize, then return
		}
	}

}