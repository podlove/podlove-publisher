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
			return "Creative Commons 3.0";
		} else {
			return $this->name;
		}
	}

	public function getUrl() {
		if ($this->type == 'cc') {
			return "http://creativecommons.org/licenses/by/3.0/";
		} else {
			return $this->name;
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

	public function getHtml() {
		$locales  = \Podlove\License\locales_cc();
		$versions = \Podlove\License\version_per_country_cc();
		$podcast  = Podcast::get_instance();

		if ($this->type == 'cc' && $this->hasCompleteCCData()) {
			if($this->cc_license_jurisdiction == "international") {
				$locale = "";
				$version = $versions["international"]["version"];
				$name = $versions["international"]["name"];
			} else {
				$locale = $this->cc_license_jurisdiction."/";
				$version = $versions[$this->cc_license_jurisdiction]["version"];
				$name = $locales[$this->cc_license_jurisdiction];
			}
			return "
			<div class=\"podlove_cc_license\">
				<img src=\"" . $this->getPictureUrl() . "\" />
				<p>
					This work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by/" . $version . "/" . $locale . "deed.en\">Creative Commons Attribution " . $version . " " . $name . " License</a>.
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
			return $podcast->get_license_html();

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