<?php 
namespace Podlove\Modules\Migration;
use \Podlove\Model;

class Enclosure {

	public $url;
	public $duration;
	public $mime_type;
	public $content_length;
	public $file_type;
	public $extension;
	public $errors = array();

	public function __construct() {

	}

	/**
	 * Takes a WordPress enclosure data blob and wraps it in a convenient API.
	 * 
	 * @param  string $url
	 * @param  int    $post_id
	 * @return Enclosure
	 */
	public static function from_enclosure_meta( $enclosure_meta, $post_id = NULL ) {

		$enclosure = new self();

		$enc_data   = explode( "\n", $enclosure_meta );
		$mime_data  = preg_split('/[ \t]/', trim( $enc_data[2] ) );
		$extra_data = ( isset( $enc_data[3] ) ) ? unserialize( $enc_data[3] ) : array();

		if ( is_array( $extra_data ) && array_key_exists( 'duration', $extra_data ) )
			$enclosure->duration  = trim( $extra_data['duration'] );

		$enclosure->post_id        = $post_id;
		$enclosure->url            = trim( $enc_data[0] );
		$enclosure->content_length = trim( $enc_data[1] );
		$enclosure->mime_type      = trim( $mime_data[0] );
		$enclosure->file_type      = Model\FileType::find_one_by_mime_type( $enclosure->mime_type );

		$enclosure->extension      = pathinfo( $enclosure->url, PATHINFO_EXTENSION );

		if ( ! $enclosure->file_type ) {
			$enclosure->errors[] = sprintf(
				__( '<strong>Unknown mime type "%s"</strong> in post %s If you want to migrate files with this mime type, you need to create your own %sfile type%s', 'podlove' ),
				$enclosure->mime_type,
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $enclosure->post_id ), get_the_title( $enclosure->post_id ) ),
				'<a href="?page=podlove_file_types_settings_handle" target="_blank">',
				'</a>'
			);
			return $enclosure;	
		}

		return $enclosure;
	}

	/**
	 * Takes a URL and extracts some information from it.
	 *
	 * Right now it does *not* actually request the URL to get further information.
	 * 
	 * @param  string $url
	 * @param  int    $post_id
	 * @return Enclosure
	 */
	public static function from_url( $url, $post_id = NULL ) {
		
		$enclosure = new self();

		$enclosure->post_id = $post_id;
		$enclosure->url     = $url;

		$enclosure->extension = pathinfo( $enclosure->url, PATHINFO_EXTENSION );
		$enclosure->file_type = Model\FileType::find_one_by_extension( $enclosure->extension );

		if ( filter_var( $enclosure->url, FILTER_VALIDATE_URL ) === false  ) {
			$this->errors[] = sprintf(
				'Invalid URL for enclosure in %s',
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $this->post_id ), get_the_title( $this->post_id ) )
			);
			return $enclosure;
		}

		if ( ! $enclosure->file_type ) {
			$errors[] = sprintf(
				__( '<strong>Unknown extension "%s"</strong> in post %s If you want to migrate files with this extension, you need to create your own %sfile type%s', 'podlove' ),
				$enclosure->extension,
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $this->post_id ), get_the_title( $this->post_id ) ),
				'<a href="?page=podlove_file_types_settings_handle" target="_blank">',
				'</a>'
			);
			return $enclosure;
		}

		return $enclosure;
	}

}