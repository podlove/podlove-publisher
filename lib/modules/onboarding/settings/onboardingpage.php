<?php

namespace Podlove\Modules\Onboarding\Settings;

use Podlove\Modules\Onboarding\Onboarding;

class OnboardingPage
{
    public static $pagehook;

    public function __construct($handle)
    {
        OnboardingPage::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            'Onboarding',
            // $menu_title
            'Onboarding',
            // $capability
            'administrator',
            // $menu_slug
            'podlove_settings_onboarding_handle',
            // $function
            [$this, 'page']
        );
    }

    public static function get_page_link($select = '')
    {
        if ($select == 'start' || $select == 'import') {
            $page = sprintf('?page=%s&select=%s', 'podlove_settings_onboarding_handle', $select);
            return admin_url('admin.php'.$page);
        }
        return admin_url('admin.php?page=podlove_settings_onboarding_handle');
    }

    public function page()
    {
        if (isset($_REQUEST['select'])) {
            $option = $_REQUEST['select'];
            if ($option == 'start' || $option == 'import') {
                Onboarding::set_onboarding_type($option);
            }
        }
        ?>
            <div data-client="podlove">
                <podlove-onboarding></podlove-onboarding>
            </div>
        <?php
    }
}
