<?php

namespace Podlove;

namespace Podlove\Modules\Onboarding;

use Podlove\Modules\Onboarding\Settings\OnboardingPage;

class Onboarding extends \Podlove\Modules\Base
{
    protected $module_name = 'Onboarding';
    protected $module_description = 'Handling the onboarding to the Podlove Publisher';
    protected $module_group = 'Getting Started';

    public function load()
    {
        if (\is_onboarding_active()) {
            add_action('admin_enqueue_scripts', [$this, 'add_scripts_and_styles']);
            add_action('admin_notices', [$this, 'onboarding_banner']);
            add_action('admin_menu', [$this, 'add_onboarding_menu'], 20);
        }
    }

    public function onboarding_banner()
    {
        ?>
            <div class="podlove-banner">
                <div class="podlove-onboarding-left">
                    <div class="podlove-banner-image">
                        <img src="<?php echo \Podlove\PLUGIN_URL.'/images/logo/podlove-publisher-icon-500.png'; ?>"/>
                    </div>
                </div>
                <div class="podlove-onboarding-right">
                    <h2 class="podlove-banner-head">Podlove Onboarding</h2>
                    <p class="podlove-banner-text">
                        Do you want to create a new podcast? Or do you already have a podcast and want to migrate?
                        Try our Onboarding and Migration Assistant to set up your podcast.</p>
                    <div>
                        <?php
                        echo sprintf(
            '<a class="podlove-banner-button" href="'.admin_url('admin.php'.\Podlove\Modules\Onboarding\Settings\OnboardingPage::get_page_link()).'">'.'Get started</a>'
        ); ?>
                    </div>
                </div>
            </div>
        <?php
    }

    public function add_scripts_and_styles()
    {
        if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'podlove_settings_onboarding_handle') {
        }
        wp_register_style('podlove-onboarding-style', $this->get_module_url().'/css/podlove-onboarding.css');
        wp_enqueue_style('podlove-onboarding-style');
    }

    public function add_onboarding_menu()
    {
        new OnboardingPage(\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE);
    }
}
