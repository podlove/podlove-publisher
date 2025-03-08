<?php

namespace Podlove\Modules\Onboarding\Settings;

use Podlove\Authentication;
use Podlove\Modules\Onboarding\Onboarding;

class OnboardingPage
{
    private const DEFAULT_SERVICE_URL = 'https://services.podlove.org/onboarding';
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

        if (!defined('PODLOVE_ONBOARDING')) {
            define('PODLOVE_ONBOARDING', self::DEFAULT_SERVICE_URL);
        }
    }

    /**
     * Get Service URL.
     *
     * If you want to host and use your own service, set the constant in your
     * `wp-config.php`: `define('PODLOVE_ONBOARDING',
     * 'https://self-hosted-services.example.com/onboarding');`
     */
    public static function get_service_url()
    {
        if (is_string(PODLOVE_ONBOARDING)) {
            return PODLOVE_ONBOARDING;
        }

        return null;
    }

    public static function get_page_link()
    {
        return admin_url('admin.php?page=podlove_settings_onboarding_handle');
    }

    public function page()
    {
        $onboardingInclude = self::get_service_url();

        if (!$onboardingInclude) {
            return;
        }

        $authentication = Authentication::application_password();

        $site = urlencode(rtrim(get_site_url(), '/'));
        $rest_url = urlencode(rtrim(get_rest_url(), '/'));
        $user = $authentication['name'];
        $password = $authentication['password'];
        $userLang = explode('_', get_locale())[0];

        $nonce = wp_create_nonce('podlove_onboarding_acknowledge');
        $wp_user_id = get_current_user_id();
        $acknowledgeOption = Onboarding::get_acknowlegde_option($wp_user_id);

        $iframeSrc = "{$onboardingInclude}?site_url={$site}&rest_url={$rest_url}&user_login={$user}&password={$password}&lang={$userLang}";
        $acknowledgeHeadline = __('Onboarding Assistant 👋', 'podlove-podcasting-plugin-for-wordpress');
        $acknowledgeDescription = __('To be able to offer you this service, we have to run the onboarding assistant on our external server. We have done everything in our power to make the service as privacy friendly as possible. We do not store any of your entered data, everything is saved in your browser 🤞. However, it is important to us that you are aware of this fact before you use the onboarding service.', 'podlove-podcasting-plugin-for-wordpress');
        $acknowledgeButton = __('All right, I\'ve got it', 'podlove-podcasting-plugin-for-wordpress');
        $httpsWarningText = __('Warning: Your website is not configured to use https! This usually means that the authentication method the assistant uses is disabled by WordPress for security reasons. Please enable https before continuing.', 'podlove-podcasting-plugin-for-wordpress');
        $applicationPasswordWarningText = __('Warning: Application passwords are not available. Maybe a security plugin is blocking them.', 'podlove-podcasting-plugin-for-wordpress');

        $httpsWarning = !wp_is_using_https() ? <<<EOD
          <p class="onboarding-warning">⚠️ {$httpsWarningText}</p>
        EOD : '';

        $applicationPasswordWarning = !wp_is_application_passwords_available_for_user(wp_get_current_user()) ? <<<EOD
          <p class="onboarding-warning">⚠️ {$applicationPasswordWarningText}</p>
        EOD : '';

        // don't skip intro page if there are warnings
        if ($httpsWarning || $applicationPasswordWarning) {
            $acknowledgeOption = false;
        }

        echo <<<EOD
      <iframe id="onboarding-assistant" class="hidden"></iframe>
      <div id="onboarding-acknowledge">
        <div id="onboarding-acknowledge-message">
          <h1 class="onboarding-headline">{$acknowledgeHeadline}</h1>
          <p class="onboarding-description">{$acknowledgeDescription}</p>
          {$httpsWarning}
          {$applicationPasswordWarning}
          <button id="acknowledge-button" class="onboarding-button">{$acknowledgeButton}</button>
        </div>
      </div>

      <script type="module">
        const acknowledgeHint = document.getElementById("onboarding-acknowledge");
        const acknowledgeButton = document.getElementById("acknowledge-button");
        const onboardingAssistant = document.getElementById("onboarding-assistant");
        const onboardingAcknowledged = "{$acknowledgeOption}";

        function loadService() {
          onboardingAssistant.contentWindow.location.href = "{$iframeSrc}";
          onboardingAssistant.classList.remove("hidden");
          acknowledgeHint.classList.add("hidden");
        }

        if (onboardingAcknowledged) {
          loadService();
        }

        acknowledgeButton.addEventListener("click", function() {
          fetch(ajaxurl + '?' + new URLSearchParams({
              action: 'podlove-onboarding-acknowledge',
              _podlove_nonce: "{$nonce}"
            }),
            {
              method: 'GET'
          }).then(response => {
            if (response.ok) {
              loadService();
            }
          })
        });
      </script>

      <style>
        #onboarding-assistant.hidden, #onboarding-acknowledge.hidden {
          display: none;
        }

        #onboarding-assistant, #onboarding-acknowledge {
          width: 100%;
          height: calc(100vh - 32px);
          position: absolute;
          top: 0;
        }

        #onboarding-acknowledge {
          padding-top: 50px;
          background: rgb(243 244 246);
          font-size: 0.875rem;
          line-height: 1.25rem;
        }

        #onboarding-acknowledge-message {
          background: white;
          padding: 20px;
          box-sizing: border-box;
          max-width: 700px;
          margin-left: auto;
          margin-right: auto;
          border-radius: 0.5rem;
          --tw-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
          --tw-shadow-colored: 0 1px 3px 0 var(--tw-shadow-color), 0 1px 2px -1px var(--tw-shadow-color);
          box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
        }

        .onboarding-headline {
          font-size: 1rem;
          line-height: 1.5rem;
          margin: 0;
          padding: 0;
        }


        .onboarding-description {
          color: rgb(107 114 128);
        }

        .onboarding-warning {
          color: rgb(107 114 128);
          font-weight: bold;
        }

        .update-message {
          display: none;
        }

        .onboarding-button {
          color: white;
          padding: 0.5rem 0.75rem;
          font-weight: 500;
          border-color: transparent;
          background-color: rgb(79 70 229);
          border-width: 1px;
          border-radius: 0.375rem;
          cursor: pointer;
        }

        #wpbody {
          height: 100%;
        }

        #wpcontent {
          padding-left: 0;
          padding-bottom: 0;
          height: 100%;
        }

        #wpfooter {
          display: none;
        }
      </style>
    EOD;
    }
}
