<?php

namespace Podlove\Modules\Onboarding\Settings;

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

    public static function get_page_link()
    {
        return '?page=podlove_settings_onboarding_handle';
    }

    public function page()
    {
        ?>
            <div data-client="podlove">
                <podlove-onboarding></podlove-onboarding>
            </div>
        <?php
    }
}
