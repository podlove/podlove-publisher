<?php

namespace Podlove\Modules\Plus;

class ModuleSettings
{
    private $module;
    private $api;

    public function __construct($module, $api)
    {
        $this->module = $module;
        $this->api = $api;
    }

    public function init()
    {
        $this->register_settings();
    }

    public function register_settings()
    {
        if (!$this->module::is_module_settings_page()) {
            return;
        }

        $api_key = $this->module->get_module_option('plus_api_token');

        if (!$api_key) {
            add_action('admin_notices', [$this, 'show_missing_token_notice']);
        }

        if ($api_key && ($user = $this->api->get_me())) {
            $description = '<i class="podlove-icon-ok"></i> '.sprintf(
                __('You are logged in as %s.', 'podlove-podcasting-plugin-for-wordpress'),
                '<strong>'.$user->email.'</strong>'
            );
        } else {
            $auth_url = $this->module::base_url();
            $description = __('You need to allow Podlove Publisher to access the PLUS API.', 'podlove-podcasting-plugin-for-wordpress')
            .'<br><a href="'.$auth_url.'" target="_blank">'.__('Get Token', 'podlove-podcasting-plugin-for-wordpress').'</a>';

            if ($api_key) {
                $description = '<i class="podlove-icon-remove"></i> '.__('Invalid API token', 'podlove-podcasting-plugin-for-wordpress').'<br>'.$description;
            }
        }

        $this->module->register_option('plus_api_token', 'string', [
            'label' => __('API Token', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => $description,
            'html' => ['class' => 'regular-text podlove-check-input'],
        ]);
    }

    public function show_missing_token_notice()
    {
        ?>
        <div class="notice notice-success">
            <p>
              <strong><?php echo __('Publisher PLUS needs an API token', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
            </p>
            <p>
              <a href="#plus"><?php echo __('go to API Token setting', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
            </p>
        </div>
        <?php
    }
}
