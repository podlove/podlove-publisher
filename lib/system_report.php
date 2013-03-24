<?php
namespace Podlove;

class SystemReport {

	private $fields = array();
	private $notices = array();
	private $errors = array();

	public function __construct() {
		
		$this->fields = array(
			'site'        => array( 'title' => 'Website',           'callback' => function() { return get_site_url(); } ),
			'php_version' => array( 'title' => 'PHP Version',       'callback' => function() { return phpversion(); } ),
			'wp_version'  => array( 'title' => 'WordPress Version', 'callback' => function() { return get_bloginfo('version'); } ),
			'curl'        => array( 'title' => 'curl Version',      'callback' => function() {
				$module_loaded = in_array( 'curl', get_loaded_extensions() );
				$function_disabled = stripos( ini_get( 'disable_functions' ), 'curl_exec' ) !== false;
				$out = '';

				if ( $module_loaded ) {
					$curl = curl_version();
					$out .= $curl['version'];
				} else {
					$out .= 'EXTENSION MISSING';
					$this->errors[] = 'curl extension is not loaded';
				}

				if ( $function_disabled ) {
					$out = ' | curl_exec is disabled';
					$this->errors[] = 'curl_exec is disabled';
				}

				return $out;
			} ),
			'allow_url_fopen'     => array( 'callback' => function() { return ini_get( 'allow_url_fopen' ); } ),
			'max_execution_time'  => array( 'callback' => function() { return ini_get( 'max_execution_time' ); } ),
			'upload_max_filesize' => array( 'callback' => function() { return ini_get( 'upload_max_filesize' ); } ),
			'memory_limit'        => array( 'callback' => function() { return ini_get( 'memory_limit' ); } ),
			'disable_classes'     => array( 'callback' => function() { return ini_get( 'disable_classes' ); } ),
			'disable_functions'   => array( 'callback' => function() { return ini_get( 'disable_functions' ); } )
		);

		$this->run();
	}

	public function run() {

		$this->errors = array();
		$this->notices = array();

		foreach ( $this->fields as $field_key => $field ) {
			$this->fields[ $field_key ]['value'] = call_user_func( $field['callback'] );
		}
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

		return '<pre>' . $out . '</pre>';
	}

}
