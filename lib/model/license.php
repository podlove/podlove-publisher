<?php
namespace Podlove\Model;

use \Podlove\Model\Podcast;

class License {

	// "podcast" or "episode"
	public $scope;

	public $type;
	public $name;
	public $url;
	public $modifcation;
	public $commercial_use;
	public $jurisdiction;

	public function __construct($scope, $attributes) {

		$license = self::get_license_from_url( $attributes['license_url'] );

		$this->scope 			 = $scope;
		$this->type  			 = self::getLicenseType( $attributes['license_url'], $attributes['license_name'] );
		$this->name  			 = $attributes['license_name'];
		$this->url  			 = $attributes['license_url'];
		$this->modification  	 = $license['modification'] ;
		$this->commercial_use  	 = $license['commercial_use'];
		$this->jurisdiction   	 = $license['jurisdiction'];
	}	

	public function getLicenseType( $url, $name ) {

		if( empty($url) || empty($name) )
			return;

		if( self::is_cc_license( $url, $name ) )
			return 'cc';

		return 'other';
	}

	public function getName() {
		return $this->name;
	}

	public function getUrl() {
		return $this->url;
	}

	public function getHtml() {
		if ($this->type == 'cc') {
			return "
			<div class=\"podlove_cc_license\">
				<img src=\"" . $this->getPictureUrl() . "\" alt=\"License\" />
				<p>
					This work is licensed under a <a rel=\"license\" href=\"" . $this->url . "\">" . $this->name . "</a>.
				</p>
			</div>";
		}

		if ($this->type == 'other') {
			return "
			<div class=\"podlove_license\">
				<p>
					" . sprintf(
						__('This work is licensed under the %s license.', 'podlove'),
						'<a rel="license" href="' . $this->url . '">' . $this->name . '</a>'
					) . "
				</p>
			</div>";
		}

		// episodes fall back to podcast licenses
		if ($this->scope == 'episode')
			return Podcast::get_instance()->get_license_html();

		// ... otherwise, a license is missing
		return "
		<div class=\"podlove_license\">
				<p style='color: red;'>
					" . __('This work is (not yet) licensed, as no license was chosen.', 'podlove') . "
				</p>
		</div>";
	}

	public function getPictureUrl() {

		if ($this->type != 'cc')
			throw new Exception("Only cc licenses have pictures");

		return \Podlove\PLUGIN_URL
			. "/images/cc/"
			. $this->getAllowModificationId() 
			. "_"
			. $this->getAlloCommercialUseId() 
			. ".png";		
	}

	private function getAllowModificationId() {
		switch ($this->modification) {
			case "yes" :
				return 1;
			break;
			case "yesbutshare" :
				return 10;
			break;
			case "no" :
				return 0;
			break;
			default :
				return 1;
			break;
		}
	}

	private function getAlloCommercialUseId() {
		return $this->commercial_use == "no" ? "0" : "1";
	}

	private function getURLSlug( $allow_modifications, $allow_commercial_use ) {
			switch ( $allow_modifications ) {
				case "yes" :
					$modification_url_slug = "";
				break;
				case "yesbutshare" :
					$modification_url_slug = "-sa";
				break;
				case "no" :
					$modification_url_slug = "-nd";
				break;
			}
			switch( $allow_commercial_use ) {
				case "yes" :
					$commercial_use_url_slug = "";
				break;
				case "no" :
					$commercial_use_url_slug = "-nc";
				break;
			}
			return array(
							'allow_modifications' => $modification_url_slug,
							'allow_commercial_use' => $commercial_use_url_slug
						);
	}

	public static function is_cc_license( $license_url, $license_name ) {
		if( !is_string( $license_url ) )
			return;

		if ( strpos( $license_url, 'creativecommons.org' ) === FALSE || strpos( $license_name, 'Creative Commons' ) === FALSE  )
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
							'jurisdiction'		=>	( $raw_extract[2] == 'deed.en' || $raw_extract[2] == '' ? 'international' : $raw_extract[2] )
						);

		return $license;
	}

	public static function get_name_from_license( $license ) {

		$locales = \Podlove\License\locales_cc();

		$license_attributions = '';

		if( $license['commercial_use'] == 'no' )
			$license_attributions .= '-NonCommercial';

		if( $license['modification'] == 'no' )
			$license_attributions .= '-NoDerivatives';

		if( $license['modification'] == 'yesbutshare' )
			$license_attributions .= '-ShareAlike';

		return 'Creative Commons Attribution' . $license_attributions . ' ' . $license['version'] . ' ' .  ( $license['jurisdiction'] == 'international' ? 'Unported' : $locales[$license['jurisdiction']] ) . ' License';
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

