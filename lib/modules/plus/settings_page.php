<?php

namespace Podlove\Modules\Plus;

use Podlove\Model\Podcast;

class SettingsPage
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
        add_action('admin_menu', [$this, 'add_admin_menu'], 275);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'podlove_settings_handle',
            __('Publisher PLUS', 'podlove-podcasting-plugin-for-wordpress'),
            __('Publisher PLUS', 'podlove-podcasting-plugin-for-wordpress'),
            'administrator',
            'publisher_plus_settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18.5" height="30" viewBox="0 0 99.32 160.81">
                    <path fill="#181716" d="M78.119 9c6.728 0 12.201 5.474 12.201 12.202V139.61c0 6.728-5.474 12.201-12.201 12.201H21.2c-6.727 0-12.2-5.473-12.2-12.201V21.202C9 14.474 14.473 9 21.2 9zm0-9H21.2C9.493 0 0 9.493 0 21.202V139.61c0 11.708 9.493 21.201 21.2 21.201h56.919c11.71 0 21.201-9.492 21.201-21.201V21.202C99.32 9.493 89.829 0 78.119 0z"/>
                    <path fill="#181716" d="M49.576 90.412c12.742 0 23.069 10.327 23.069 23.068 0 12.74-10.327 23.069-23.069 23.069-12.738 0-23.067-10.329-23.067-23.069 0-12.741 10.329-23.068 23.067-23.068m0-9c-17.682 0-32.067 14.386-32.067 32.068 0 17.683 14.385 32.069 32.067 32.069 17.683 0 32.069-14.386 32.069-32.069.001-17.682-14.386-32.068-32.069-32.068z"/>
                    <g clip-rule="evenodd">
                        <path fill="none" stroke="#181716" stroke-miterlimit="10" stroke-width="9" d="M72.895 46.223l-23.57 23.583L25.758 46.22c-2.649-2.7-4.285-6.399-4.285-10.481 0-8.267 6.702-14.968 14.968-14.968 5.485 0 10.278 2.949 12.885 7.347 2.606-4.398 7.401-7.347 12.884-7.347 8.268 0 14.97 6.701 14.97 14.968 0 4.082-1.636 7.783-4.285 10.484z"/>
                        <path fill="#181716" fill-rule="evenodd" d="M49.577 105.223c4.561 0 8.26 3.698 8.26 8.257 0 4.562-3.699 8.258-8.26 8.258-4.56 0-8.257-3.696-8.257-8.258 0-4.559 3.697-8.257 8.257-8.257z"/>
                    </g>
                </svg>
                <h1 style="padding: 0;"><?php echo __('Publisher PLUS', 'podlove-podcasting-plugin-for-wordpress'); ?></h1>
            </div>

            <div style="max-width: 800px;">
              <?php if ($this->module->get_module_option('plus_api_token')) {
                Banner::plus_authenticated();
              } else {
                Banner::plus_main();
              } ?>
            </div>

            <div data-client="podlove" style="margin: 15px 0; max-width: 800px; ">
              <podlove-plus-token/>
            </div>

            <div data-client="podlove" style="margin: 15px 0; max-width: 800px; ">
              <podlove-plus-features/>
            </div>

        </div>
        <?php
    }
}
