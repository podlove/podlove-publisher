<?php

namespace Podlove\Model;

class License
{
    // "podcast" or "episode"
    public $scope;

    public $type;
    public $name;
    public $url;
    public $modifcation;
    public $commercial_use;
    public $jurisdiction;
    public $version;
    public $modification;

    public function __construct($scope, $attributes)
    {
        $license = self::get_license_from_url($attributes['license_url']);

        $this->scope = $scope;
        $this->type = self::getLicenseType($attributes['license_url'], $attributes['license_name']);
        $this->name = $attributes['license_name'];
        $this->url = $attributes['license_url'];
        $this->version = $license['version'];
        $this->modification = $license['modification'];
        $this->commercial_use = $license['commercial_use'];
        $this->jurisdiction = $license['jurisdiction'];
    }

    public function getIdentifier()
    {
        if ($this->version == 'pdmark') {
            return 'PDM-1.0';
        }

        if ($this->version == 'cc0') {
            return 'CC0-1.0';
        }

        if ($this->getLicenseType($this->url, $this->name) != 'cc') {
            return $this->name;
        }

        $commercial_segment = match ($this->commercial_use) {
            'yes' => false,
            default => 'nc'
        };

        $modification_segment = match ($this->modification) {
            'yes' => false,
            'no' => 'nd',
            default => 'sa'
        };

        $verison_segment = $this->version == 'cc3' ? '3.0' : '4.0';

        $segments = [
            'cc',
            'by',
            $commercial_segment,
            $modification_segment,
            $verison_segment
        ];

        if ($this->version == 'cc3' && $this->jurisdiction != 'international') {
            $segments[] = $this->jurisdiction;
        }

        $segments = array_filter($segments);

        return implode('-', $segments);
    }

    public function getLicenseType($url, $name)
    {
        if (empty($url) || empty($name)) {
            return;
        }

        if (self::is_cc_license($url, $name)) {
            return 'cc';
        }

        return 'other';
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isCreativeCommons()
    {
        return $this->type == 'cc';
    }

    public function getHtml()
    {
        if ($this->type == 'cc') {
            return '
			<div class="podlove_cc_license">
				<img src="'.$this->getPictureUrl().'" alt="License" />
				<p>
					This work is licensed under a <a rel="license" href="'.$this->getUrl().'">'.$this->getName().'</a>.
				</p>
			</div>';
        }

        if ($this->type == 'other') {
            return '
			<div class="podlove_license">
				<p>
					'.sprintf(
                __('This work is licensed under the %s license.', 'podlove-podcasting-plugin-for-wordpress'),
                '<a rel="license" href="'.$this->url.'">'.$this->name.'</a>'
            ).'
				</p>
			</div>';
        }

        // episodes fall back to podcast licenses
        if ($this->scope == 'episode') {
            return Podcast::get()->get_license_html();
        }

        // ... otherwise, a license is missing
        return "
		<div class=\"podlove_license\">
				<p style='color: red;'>
					".__('This work is (not yet) licensed, as no license was chosen.', 'podlove-podcasting-plugin-for-wordpress').'
				</p>
		</div>';
    }

    public function getPictureUrl()
    {
        if ($this->type != 'cc') {
            throw new \Exception('Only cc licenses have pictures');
        }

        if ($this->version == 'cc0') {
            return \Podlove\PLUGIN_URL
            .'/images/cc/pd.png';
        }

        if ($this->version == 'pdmark') {
            return \Podlove\PLUGIN_URL
            .'/images/cc/pdmark.png';
        }

        return \Podlove\PLUGIN_URL
            .'/images/cc/'
            .$this->getAllowModificationId()
            .'_'
            .$this->getAllowCommercialUseId()
            .'.png';
    }

    public static function is_cc_license($license_url, $license_name)
    {
        if (!is_string($license_url)) {
            return;
        }

        if (strpos($license_url, 'creativecommons.org') === false || strpos($license_name, 'Creative Commons') === false && strpos($license_name, 'Public Domain') === false) {
            return false;
        }

        return true;
    }

    public static function get_license_from_url($url)
    {
        // only parse cc licenses
        if (stripos($url, 'creativecommons.org') === false) {
            return [
                'version' => null,
                'commercial_use' => null,
                'modification' => null,
                'jurisdiction' => null,
            ];
        }

        $raw_extract = array_slice(
            explode('/', $url),
            4, // remove http://creativecommons.org/
            3
        );

        if (stripos($url, '/publicdomain/zero/')) {
            $version = 'cc0';
        } elseif (stripos($url, '/publicdomain/mark/')) {
            $version = 'pdmark';
        } elseif (stripos($url, '/4.0')) {
            $version = 'cc4';
        } else {
            $version = 'cc3';
        }

        return [
            'version' => $version,
            'commercial_use' => strpos($raw_extract[0], 'nc') ? 'no' : 'yes',
            'modification' => self::get_modification_state($raw_extract[0]),
            'jurisdiction' => !isset($raw_extract[2]) || strpos($raw_extract[2], '.') || $raw_extract[2] == '' ? 'international' : $raw_extract[2],
        ];
    }

    public static function get_name_from_license($license)
    {
        $locales = \Podlove\License\locales_cc();
        $versions = \Podlove\License\version_per_country_cc();

        $license_attributions = '';

        if (empty($license['version'])) {
          return '';
        }

        if ($license['version'] == 'pdmark') {
            return 'Public Domain Mark License';
        }

        if ($license['version'] == 'cc0') {
            return 'Public Domain License';
        }

        if ($license['commercial_use'] == 'no') {
            $license_attributions .= '-NonCommercial';
        }

        if ($license['modification'] == 'no') {
            $license_attributions .= '-NoDerivatives';
        }

        if ($license['modification'] == 'yesbutshare') {
            $license_attributions .= '-ShareAlike';
        }

        if ($license['version'] == 'cc4') {
            return 'Creative Commons Attribution'.$license_attributions.' 4.0 International License';
        }

        return 'Creative Commons Attribution'.$license_attributions.' '.$versions[$license['jurisdiction']]['version'].' '.($license['jurisdiction'] == 'international' ? 'Unported' : $locales[$license['jurisdiction']]).' License';
    }

    public static function get_url_from_license($license)
    {
        $versions = \Podlove\License\version_per_country_cc();

        if (!is_array($license)) {
            return;
        }

        if ($license['version'] == 'cc0') {
            return 'http://creativecommons.org/publicdomain/zero/1.0/';
        }

        if ($license['version'] == 'pdmark') {
            return 'http://creativecommons.org/publicdomain/mark/1.0/';
        }

        if ($license['version'] == 'cc4') {
            return 'http://creativecommons.org/licenses/by'
                    .($license['commercial_use'] == 'no' ? '-nc' : '')
                    .($license['modification'] == 'yes' ? '/' : ($license['modification'] == 'no' ? '-nd/' : '-sa/'))
                    .'4.0';
        }

        return 'http://creativecommons.org/licenses/by'
                .($license['commercial_use'] == 'no' ? '-nc' : '')
                .($license['modification'] == 'yes' ? '/' : ($license['modification'] == 'no' ? '-nd/' : '-sa/'))
                .$versions[$license['jurisdiction']]['version']
                .($license['jurisdiction'] == 'international' ? '/' : '/'.$license['jurisdiction'].'/')
                .'deed.en';
    }

    private function getAllowModificationId()
    {
        switch ($this->modification) {
            case 'yes':
                return 1;

                break;
            case 'yesbutshare':
                return 10;

                break;
            case 'no':
                return 0;

                break;

            default:
                return 1;

                break;
        }
    }

    private function getAllowCommercialUseId()
    {
        return $this->commercial_use == 'no' ? '0' : '1';
    }

    private function getURLSlug($allow_modifications, $allow_commercial_use)
    {
        switch ($allow_modifications) {
            case 'yes':
                $modification_url_slug = '';

                break;
            case 'yesbutshare':
                $modification_url_slug = '-sa';

                break;
            case 'no':
                $modification_url_slug = '-nd';

                break;
        }
        switch ($allow_commercial_use) {
            case 'yes':
                $commercial_use_url_slug = '';

                break;
            case 'no':
                $commercial_use_url_slug = '-nc';

                break;
        }

        return [
            'allow_modifications' => $modification_url_slug,
            'allow_commercial_use' => $commercial_use_url_slug,
        ];
    }

    private static function get_modification_state($parameter_string)
    {
        if (strpos($parameter_string, 'sa')) {
            return 'yesbutshare';
        }

        if (strpos($parameter_string, 'nd')) {
            return 'no';
        }

        return 'yes';
    }
}
