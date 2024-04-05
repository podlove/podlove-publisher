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
    $onboardingInclude = \podlove_get_onboarding_include();

    if (!$onboardingInclude) {
      return;
    }

    if (isset($_REQUEST['select']) && ($_REQUEST['select'] == 'start' || $_REQUEST['select'] == 'import')) {
        $option = $_REQUEST['select'];

      switch ($option) {
        case 'start':
          $onboardingInclude = $onboardingInclude.'/onboarding';

        break;

        case 'import':
          $onboardingInclude = $onboardingInclude.'/import';
          
        break;
      }
    } else {
      $onboardingInclude = $onboardingInclude.'/select';
    }

    $authentication = Authentication::application_password();

    $site = urlencode(rtrim(get_site_url(), '/'));
    $user = $authentication['name'];
    $password = $authentication['password'];
    $userLang = explode("_", get_locale())[0];

    $iframeSrc = "$onboardingInclude?site_url=$site&user_login=$user&password=$password&lang=$userLang";

    // this is needed because of this 18 years old bug: https://bugzilla.mozilla.org/show_bug.cgi?id=356558
    echo <<<EOD
      <iframe id="publisher-iframe"></iframe>

      <script type="module">
        document.getElementById("publisher-iframe").contentWindow.location.href = "{$iframeSrc}";
      </script>

      <style>
        #publisher-iframe {
          width: calc(100% + 20px);
          height: 850px;
          position: absolute;
          top: 0;
          left: -20px;
        }
      </style>
    EOD;
    }
}
