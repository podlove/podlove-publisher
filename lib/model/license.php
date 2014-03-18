<?php
namespace Podlove\Model;

use \Podlove\Model\Podcast;

class License {

	// "podcast" or "episode"
	public $scope;

	public $type;
	public $name;
	public $url;
	public $cc_allow_modifications;
	public $cc_allow_commercial_use;
	public $cc_license_jurisdiction;

	public function __construct($scope, $attributes) {
		$this->scope                   = $scope;
		$this->type                    = $attributes['type'];
		$this->name                    = $attributes['license_name'];
		$this->url                     = $attributes['license_url'];
		$this->cc_allow_modifications  = $attributes['allow_modifications'];
		$this->cc_allow_commercial_use = $attributes['allow_commercial_use'];
		$this->cc_license_jurisdiction = $attributes['jurisdiction'];
	}

	public function getAttributes() {
		return array(
			'license_type'         => $this->type,
			'license_name'         => $this->name,
			'license_url'          => $this->url,
			'allow_modifications'  => $this->cc_allow_modifications,
			'allow_commercial_use' => $this->cc_allow_commercial_use,
			'jurisdiction'         => $this->cc_license_jurisdiction
		);
	}

	public function getSelectOptions() {
		return array(
			'cc'    => 'Creative Commons',
			'other' => __('Other', 'podlove')
		);
	}

	public function getName() {
		if ($this->type == 'cc') {
			$locales  = \Podlove\License\locales_cc();
			$versions = \Podlove\License\version_per_country_cc();

			if($this->cc_license_jurisdiction == "international") {
				$version = $versions["international"]["version"];
				$name = $versions["international"]["name"];
			} else {
				$version = $versions[$this->cc_license_jurisdiction]["version"];
				$name = $locales[$this->cc_license_jurisdiction];
			}

			return "Creative Commons Attribution " . $version . " " . $name . " License";
		} else {
			return $this->name;
		}
	}

	public function getUrl() {
		if ($this->type == 'cc') {
			$locales  = \Podlove\License\locales_cc();
			$versions = \Podlove\License\version_per_country_cc();
			$url_slugs = $this->getURLSlug( $this->cc_allow_modifications, $this->cc_allow_commercial_use );

			if($this->cc_license_jurisdiction == "international") {
				$locale = "";
				$version = $versions["international"]["version"];
			} else {
				$locale = $this->cc_license_jurisdiction."/";
				$version = $versions[$this->cc_license_jurisdiction]["version"];
			}

			return "http://creativecommons.org/licenses/by"
			     . $url_slugs['allow_commercial_use']
			     . $url_slugs['allow_modifications']
			     . "/" . $version
			     . "/" . $locale
			     . "deed.en";
		} else {
			return $this->url;
		}
	}

	private function hasCompleteCCData() {
		return $this->cc_license_jurisdiction != ""
		    && $this->cc_allow_modifications  != ""
		    && $this->cc_allow_commercial_use != "";
	}

	private function hasCompleteOtherData() {
		return $this->name != "" && $this->url != "";
	}

	public function isCreativeCommons() {
		return $this->type == 'cc';
	}

	public function getHtml() {

		if ($this->type == 'cc' && $this->hasCompleteCCData()) {
			return "
			<div class=\"podlove_cc_license\">
				<img src=\"" . $this->getPictureUrl() . "\" alt=\"License\" />
				<p>
					This work is licensed under a <a rel=\"license\" href=\"" . $this->getUrl() . "\">" . $this->getName() . "</a>.
				</p>
			</div>";
		}

		if ($this->type == 'other' && $this->hasCompleteOtherData()) {
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

	private function getAllowModificationId() {
		switch ($this->cc_allow_modifications) {
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
		return $this->cc_allow_commercial_use == "no" ? "0" : "1";
	}


}