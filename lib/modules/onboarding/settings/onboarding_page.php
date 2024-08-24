<?php

namespace Podlove\Modules\Onboarding\Settings;

use Podlove\Authentication;

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
    return admin_url('admin.php?page=podlove_settings_onboarding_handle');
  }

  public function page()
  {
    $onboardingInclude = \podlove_get_onboarding_include();

    if (!$onboardingInclude) {
      return;
    }

    $authentication = Authentication::application_password();

    $site = urlencode(rtrim(get_site_url(), '/'));
    $user = $authentication['name'];
    $password = $authentication['password'];
    $userLang = explode("_", get_locale())[0];

    $iframeSrc = "$onboardingInclude?site_url=$site&user_login=$user&password=$password&lang=$userLang";
    $acknowledgeHeadline = __('Onboarding Assistant 👋', 'podlove-podcasting-plugin-for-wordpress');
    $acknowledgeDescription = __('To be able to offer you this service, we have to run the onboarding assistant on our external server. We have done everything in our power to make the service as privacy friendly as possible. We do not store any of your entered data, everything is saved in your browser 🤞. However, it is important to us that you are aware of this fact before you use the onboarding service.', 'podlove-podcasting-plugin-for-wordpress');
    $acknowledgeButton = __('All right, I\'ve got it', 'podlove-podcasting-plugin-for-wordpress');

    echo <<<EOD
      <iframe id="onboarding-assistant" class="hidden"></iframe>
      <div id="onboarding-acknowledge">
        <h1 class="onboarding-headline">{$acknowledgeHeadline}</h1>
        <p class="onboarding-description">{$acknowledgeDescription}</p>
        <button id="acknowledge-button" class="onboarding-button">{$acknowledgeButton}</button>
      </div>

      <script type="module">
        const acknowledgeHint = document.getElementById("onboarding-acknowledge");
        const acknowledgeButton = document.getElementById("acknowledge-button");
        const onboardingAssistant = document.getElementById("onboarding-assistant");
        const onboardingAcknowledged = localStorage.getItem("podlove-pulbisher:onboarding-acknowledged");

        function loadService() {
          localStorage.setItem("podlove-pulbisher:onboarding-acknowledged", true);
          onboardingAssistant.contentWindow.location.href = "{$iframeSrc}";
          onboardingAssistant.classList.remove("hidden");
          acknowledgeHint.classList.add("hidden");
        }

        if (onboardingAcknowledged) {
          loadService();
        }

        acknowledgeButton.addEventListener("click", loadService);
      </script>

      <style>
        #onboarding-assistant.hidden, #onboarding-acknowledge.hidden {
          display: none;
        }

        #onboarding-assistant, #onboarding-acknowledge {
          width: 100%;
          height: 100vh;
          position: absolute;
          top: 0;
        }

        #onboarding-acknowledge {
          background: rgba(128, 128, 128, 0.3);
          display: flex;
          align-items: center;
          flex-direction: column;
        }

        .update-message {
          display: none;
        }

        .onboarding-headline {
          margin-top: 80px;
          margin-bottom: 25px;
          padding: 0;
        }

        .onboarding-description {
          width: 50%;
          text-center;
          margin-bottom: 25px;
        }

        .onboarding-button {
          color: white;
          font-size: 0.875em;
          padding: 0.5rem 0.75rem;
          line-height: 1rem;
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
