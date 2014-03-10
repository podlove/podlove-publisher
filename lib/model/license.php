<?php
namespace Podlove\Model;

use \Podlove\Model\Podcast;

class License {

	public static function is_cc_license( $license_url ) {
		if( !is_string( $license_url ) )
			return;

		if ( strpos( $license_url, 'creativecommons.org' ) === FALSE)
			return FALSE;

		return TRUE;
	}

	public static function get_license_from_url( $url=NULL ) {

		if( is_null($url) )
			return;

		$raw_extract = array_slice(
										explode( '/', $url ),
										4, // remove http://creativecommons.org/
										3
							  		 );

		$license = array(
							'version'			=>	$raw_extract[1],
							'commercial_use'	=>	( strpos( $raw_extract[0], 'nc' ) ? 'no' : 'yes' ),
							'modification'		=>	self::get_modification_state( $raw_extract[0] ),
							'jurisdiction'		=>	( $raw_extract[2] !== 'deed.en' ? $raw_extract[2] : 'international' )
						);

		return $license;
	}

	public static function get_name_from_license( $license ) {
		$locales = \Podlove\License\locales_cc();
		return 'Creative Commons Attribution ' . $license['version'] . ' ' .  ( $license['jurisdiction'] == 'international' ? 'Unported' : $locales[$license['jurisdiction']] ) . ' License';
	}

	public static function get_url_from_license( $license ) {

		if( !is_array($license) )
			return;

		$url = 'http://creativecommons.org/licenses/by'
				. ( $license['commercial_use'] == 'no' ? '-nc' : '' )
				. ( $license['modification'] == 'yes' ? '/' : ( $license['modification'] == 'no' ? '-nd/' : '-sa/' ) )
				. $license['version']
				. ( $license['jurisdiction'] == 'international' ? '/' : '/'.$license['jurisdiction'].'/' )
				. 'deed.en';

		return $url;
	}

	private static function get_modification_state( $parameter_string ) {
		if ( strpos( $parameter_string, 'sa' ) )
			return 'yesbutshare';

		if ( strpos( $parameter_string, 'nd' ) )
			return 'no';

		return 'yes';
	}

}

