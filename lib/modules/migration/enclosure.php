<?php 
namespace Podlove\Modules\Migration;
use \Podlove\Model;
use \Podlove\Duration;

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

	public static function all_for_post( $post_id, $args = array() ) {

		$enclosures = array();

		$file_types_for_this_episode = array();
		$wordpress_enclosures = get_post_meta( $post_id, 'enclosure', false );
		foreach ( $wordpress_enclosures as $enclosure_data ) {
			$enclosure = Enclosure::from_enclosure_meta( $enclosure_data, $post_id );

			if ( in_array( $enclosure->file_type->id, $file_types_for_this_episode ) )
				continue;

			$file_types_for_this_episode[] = $enclosure->file_type->id;
			$enclosures[] = $enclosure;
		}

		// process podPress files
		$podPress_enclosures = get_post_meta( $post_id, '_podPressMedia', false );
		if ( is_array( $podPress_enclosures ) && ! empty( $podPress_enclosures ) ) {
			foreach ( $podPress_enclosures[0] as $file ) {
				$enclosure = Enclosure::from_enclosure_podPress( $file, $post_id );

				if ( in_array( $enclosure->file_type->id, $file_types_for_this_episode ) )
					continue;

				$file_types_for_this_episode[] = $enclosure->file_type->id;
				$enclosures[] = $enclosure;
			}
		}

		// if ( isset( $args['only_valid'] ) && $args['only_valid'] ) {
		// 	foreach ( $enclosures as $enclosure ) {
		// 		if ( $enclosure->errors ) {
		// 			// unset( current( $enclosure ) );
		// 		}
		// 	}
		// }

		return $enclosures;
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

		if ( is_array( $extra_data ) && array_key_exists( 'duration', $extra_data ) ) {
			$duration = new Duration( $extra_data['duration'] );
			$enclosure->duration  = $duration->get();
		}

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

	public static function from_enclosure_podPress( $file, $post_id = NULL ) {

		$mime_type = str_replace(
			array( 'audio_mp3',  'audio_m4a', '_' ),
			array( 'audio/mpeg', 'audio/mp4', '/' ),
			$file['type']
		);

		$enclosure = new self();


		$enclosure->post_id        = $post_id;
		$enclosure->url            = $file['URI'];
		$enclosure->content_length = $file['size'];
		$enclosure->mime_type      = $mime_type;
		$enclosure->file_type      = Model\FileType::find_one_by_mime_type( $enclosure->mime_type );

		$duration = new Duration( $file['duration'] );
		$enclosure->duration       = $duration->get();

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
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $enclosure->post_id ), get_the_title( $enclosure->post_id ) )
			);
			return $enclosure;
		}

		if ( ! $enclosure->file_type ) {
			$errors[] = sprintf(
				__( '<strong>Unknown extension "%s"</strong> in post %s If you want to migrate files with this extension, you need to create your own %sfile type%s', 'podlove' ),
				$enclosure->extension,
				sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $enclosure->post_id ), get_the_title( $enclosure->post_id ) ),
				'<a href="?page=podlove_file_types_settings_handle" target="_blank">',
				'</a>'
			);
			return $enclosure;
		}

		return $enclosure;
	}

}