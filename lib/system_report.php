<?php
namespace Podlove;

class SystemReport {

	private $fields = array();
	private $notices = array();
	private $errors = array();

	public function __construct() {

		$errors =& $this->errors;
		
		$this->fields = array(
			'site'        => array( 'title' => 'Website',           'callback' => function() { return get_site_url(); } ),
			'php_version' => array( 'title' => 'PHP Version',       'callback' => function() { return phpversion(); } ),
			'wp_version'  => array( 'title' => 'WordPress Version', 'callback' => function() { return get_bloginfo('version'); } ),
			'podlove_version' => array( 'title' => 'Publisher Version', 'callback' => function() { return \Podlove\get_plugin_header( 'Version' ); } ),
			'player_version'  => array( 'title' => 'Web Player Version', 'callback' => function() {

				if ( ! defined( 'PODLOVEWEBPLAYER_DIR' ) )
					return 'no web player found';

				$pwp_file = PODLOVEWEBPLAYER_DIR . 'podlove-web-player.php';
				if ( ! is_readable( $pwp_file ) )
					return 'not readable';

				$plugin_data = \get_plugin_data( $pwp_file );

				return $plugin_data['Version'];
			} ),
			'curl'        => array( 'title' => 'curl Version',      'callback' => function() use ( &$errors ) {
				$module_loaded = in_array( 'curl', get_loaded_extensions() );
				$function_disabled = stripos( ini_get( 'disable_functions' ), 'curl_exec' ) !== false;
				$out = '';

				if ( $module_loaded ) {
					$curl = curl_version();
					$out .= $curl['version'];
				} else {
					$out .= 'EXTENSION MISSING';
					$errors[] = 'curl extension is not loaded';
				}

				if ( $function_disabled ) {
					$out .= ' | curl_exec is disabled';
					$errors[] = 'curl_exec is disabled';
				}

				return $out;
			} ),
			'iconv' => array( 'callback' => function() use ( &$errors ) {	
				$iconv_available = function_exists( 'iconv' );

				if ( ! $iconv_available ) {
					$errors[] = 'You need to install/activate php5-iconv';
				}

				return $iconv_available ? "available" : "MISSING";
			} ),
			'allow_url_fopen' => array( 'callback' => function() use ( &$errors ) {

				if ( ! $allow_url_fopen = ini_get( 'allow_url_fopen' ) )
					$errors[] = 'allow_url_fopen must be activated in your php.ini';

				return $allow_url_fopen;
			} ),
			'simplexml' => array( 'callback' => function() use ( &$errors ) {
				
				if ( ! $simplexml = in_array('SimpleXML', get_loaded_extensions()) )
					$errors[] = 'You need to install/activate the PHP SimpleXML module';

				return $simplexml ? 'ok' : 'missing!';
			} ),
			'max_execution_time'  => array( 'callback' => function() { return ini_get( 'max_execution_time' ); } ),
			'upload_max_filesize' => array( 'callback' => function() { return ini_get( 'upload_max_filesize' ); } ),
			'memory_limit'        => array( 'callback' => function() { return ini_get( 'memory_limit' ); } ),
			'disable_classes'     => array( 'callback' => function() { return ini_get( 'disable_classes' ); } ),
			'disable_functions'   => array( 'callback' => function() { return ini_get( 'disable_functions' ); } ),
			'permalinks' => array( 'callback' => function() use ( &$errors ) {

				if ( \Podlove\get_setting( 'website', 'use_post_permastruct' ) == 'on' )
					return 'ok';

				if ( stristr( \Podlove\get_setting( 'website', 'custom_episode_slug' ), '%podcast%' ) === FALSE ) {
					$website_options = get_option( 'podlove_website' );
					$website_options['use_post_permastruct'] = 'on';
					update_option( 'podlove_website', $website_options );
				}

				return 'ok';
			} ),
			'podcast_settings' => array( 'callback' => function() use ( &$errors ) {

				$out = '';
				$podcast = Model\Podcast::get_instance();

				if ( ! $podcast->title ) {
					$error = __( 'Your podcast needs a title.', 'podlove' );
					$errors[] = $error;
					$out .= $error;
				}

				if ( ! $podcast->media_file_base_uri ) {
					$error = __( 'Your podcast needs an upload location for file storage.', 'podlove' );
					$errors[] = $error;
					$out .= $error;
				}

				return $out;
			} ),
			'web_player' => array( 'callback' => function() use ( &$errors ) {

				foreach ( get_option( 'podlove_webplayer_formats', array() ) as $_ => $media_types ) {
					foreach ( $media_types as $extension => $asset_id ) {
						if ( $asset_id ) {
							return "ok";
						}
					}
				}

				$error = __( 'You need to assign at least one asset to the web player.', 'podlove' );
				$errors[] = $error;
				return $error;
			} )
		);

		$this->run();
	}

	public function run() {

		$this->errors = array();
		$this->notices = array();

		foreach ( $this->fields as $field_key => $field ) {
			$this->fields[ $field_key ]['value'] = call_user_func( $field['callback'] );
		}

		update_option( 'podlove_global_messages', array( 'errors' => $this->errors, 'notices' => $this->notices ) );
	}

	public function render() {

		$rfill = function ( $string, $length, $fillchar = ' ' ) {
			while ( strlen( $string ) < $length ) {
				$string .= $fillchar;
			}
			return $string;
		};

		$fill_length = 1 + max( array_map( function($k) { return strlen($k); }, array_keys( $this->fields ) ) );

		$out = '';

		foreach ( $this->fields as $field_key => $field ) {
			$title = isset( $field['title'] ) ? $field['title'] : $field_key;
			$out .= $rfill( $title, $fill_length ) . $field['value'] . "\n";
		}

		$out .= "\n";

		if ( count( $this->errors ) ) {
			$out .= count( $this->errors ) . " CRITICAL ERRORS: \n";
			foreach ( $this->errors as $error ) {
				$out .= "$error\n";
			}
		} else {
			$out .= "0 errors\n";
		}

		if ( count( $this->notices ) ) {
			$out .= count( $this->notices ) . " notices (no dealbreaker, but should be fixed if possible): \n";
			foreach ( $this->notices as $error ) {
				$out .= "$error\n";
			}
		} else {
			$out .= "0 notices\n";
		}

		if ( count( $this->errors ) + count( $this->notices ) === 0 ) {
			$out .= "Nice, Everything looks fine!";
		}

		return $out;
	}

}
