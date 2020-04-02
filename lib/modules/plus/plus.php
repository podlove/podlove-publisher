<?php
namespace Podlove\Modules\Plus;

class Plus extends \Podlove\Modules\Base
{
    protected $module_name        = 'Publisher PLUS';
    protected $module_description = 'A Feed Proxy service for subscriber statistics and performance.';
    protected $module_group       = 'external services';

    public function load()
    {
        $this->api = new API($this, $this->get_module_option('plus_api_token'));
        $this->register_settings();
    }

    public function register_settings()
    {
        if (!self::is_module_settings_page()) {
            return;
        }

        $api_key = $this->get_module_option('plus_api_token');

        if ($api_key && ($user = $this->api->get_me())) {
            $description = '<i class="podlove-icon-ok"></i> ' . sprintf(
                __('You are logged in as %s.', 'podlove-podcasting-plugin-for-wordpress'),
                '<strong>' . $user->email . '</strong>'
            );
        } else {
            $auth_url    = self::base_url();
            $description = __('You need to allow Podlove Publisher to access the PLUS API.', 'podlove-podcasting-plugin-for-wordpress')
            . '<br><a href="' . $auth_url . '" target="_blank">' . __('Get Token', 'podlove-podcasting-plugin-for-wordpress') . '</a>';

            if ($api_key) {
                $description = '<i class="podlove-icon-remove"></i> ' . __('Invalid API token', 'podlove-podcasting-plugin-for-wordpress') . '<br>' . $description;
            }
        }

        $this->register_option('plus_api_token', 'string', array(
            'label'       => __('API Token', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => $description,
            'html'        => array('class' => 'regular-text podlove-check-input'),
        ));

    }

    public static function base_url()
    {
        if (defined('PODLOVE_PLUS_BASE_URL')) {
            return PODLOVE_PLUS_BASE_URL;
        } else {
            return apply_filters('podlove_plus_base_url', 'https://plus.podlove.org');
        }
    }
}
