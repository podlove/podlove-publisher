<?php

namespace Podlove\Modules\Onboarding;

use Podlove\Modules\Onboarding\Settings\OnboardingPage;
use Podlove\Modules\Onboarding\WP_REST_PodloveOnboarding_Controller;

class Onboarding extends \Podlove\Modules\Base
{
  protected $module_name = 'Onboarding';
  protected $module_description = 'Handling the onboarding to the Podlove Publisher';
  protected $module_group = 'system';

  public function load()
  {
    add_action('admin_enqueue_scripts', [$this, 'add_scripts_and_styles']);
    add_action('admin_notices', [$this, 'onboarding_banner']);
    add_action('admin_menu', [$this, 'add_onboarding_menu'], 20);
    add_action('rest_api_init', [$this, 'api_init']);
  }

  public static function is_visible()
  {
    return true;
  }

  public function onboarding_banner()
  {
    if (self::is_banner_hide()) {
      return;
    }

    if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'podlove_settings_onboarding_handle') {
      return;
    } ?>

    <div id="podlove-panel-wrap" class=podlove-panel-wrap>
      <div class="podlove-panel">
        <?php
          echo sprintf(
          '<a id="podlove-panel-banner-dismiss" class="podlove-panel-banner-dismiss" href="#"></a>'
          ); ?>
        <div class="podlove-panel-content">
          <div class="podlove-panel-header">
            <div class="podlove-panel-header-image">
              <img src="data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.dev/svgjs' width='1440' height='560' preserveAspectRatio='none' viewBox='0 0 1440 560'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1092%26quot%3b)' fill='none'%3e%3cpath d='M272 242L271 -68' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M121 369L120 691' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1223 512L1222 310' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1055 343L1054 -1' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1438 141L1437 -37' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M339 160L338 -21' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1080 22L1079 -337' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M300 467L299 274' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1438 372L1437 790' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M296 497L295 755' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M887 301L886 0' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M286 172L285 18' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M732 256L731 -48' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M685 394L684 766' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1186 526L1185 354' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M353 292L352 113' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M253 447L252 646' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M687 471L686 640' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1272 166L1271 457' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M927 173L926 385' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1409 204L1408 483' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M847 434L846 127' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M437 224L436 -140' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M126 67L125 -324' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M804 35L803 -197' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M983 242L982 7' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M649 102L648 -247' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M697 282L696 551' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1149 236L1148 19' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M333 420L332 157' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1396 100L1395 -189' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M797 117L796 -74' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1094%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1345 459L1344 43' stroke-width='10' stroke='url(%26quot%3b%23SvgjsLinearGradient1096%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M257 131L256 -32' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M99 9L98 -234' stroke-width='8' stroke='url(%26quot%3b%23SvgjsLinearGradient1095%26quot%3b)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M723 435L722 56' stroke-width='6' stroke='url(%26quot%3b%23SvgjsLinearGradient1093%26quot%3b)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1092'%3e%3crect width='1440' height='560' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='0%25' y1='100%25' x2='0%25' y2='0%25' id='SvgjsLinearGradient1093'%3e%3cstop stop-color='rgba(67%2c 56%2c 202%2c 0)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(67%2c 56%2c 202%2c 0.3)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3clinearGradient x1='0%25' y1='100%25' x2='0%25' y2='0%25' id='SvgjsLinearGradient1094'%3e%3cstop stop-color='rgba(233%2c 232%2c 249%2c 0)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(233%2c 232%2c 249%2c 0.3)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3clinearGradient x1='0%25' y1='0%25' x2='0%25' y2='100%25' id='SvgjsLinearGradient1095'%3e%3cstop stop-color='rgba(233%2c 232%2c 249%2c 0)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(233%2c 232%2c 249%2c 0.3)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3clinearGradient x1='0%25' y1='0%25' x2='0%25' y2='100%25' id='SvgjsLinearGradient1096'%3e%3cstop stop-color='rgba(67%2c 56%2c 202%2c 0)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(67%2c 56%2c 202%2c 0.3)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e"; />
            </div>
            <div class="podlove-panel-banner">
              <div class="podlove-panel-banner-left">
                <div class="podlove-panel-banner-image">
                  <img src="<?php print \Podlove\PLUGIN_URL.'/images/logo/podlove-publisher-icon-500.png'; ?>" />
                </div>
              </div>
              <div class="podlove-panel-banner-right">
                <h2 class="podlove-panel-banner-head"><?php print(__('Welcome to Podlove', 'podlove-podcasting-plugin-for-wordpress')); ?></h2>
                <p class="podlove-panel-banner-text">
                  <?php print(__('Ready to share your voice with the world? Let\'s start your podcasting journey! Explore our new Onboarding Assistant for a seamless setup. Choose between starting a new podcast or importing an existing one, and let\'s get your stories out there!', 'podlove-podcasting-plugin-for-wordpress')); ?>
                </p>
                <a id="podlove-panel-banner-button" class="podlove-panel-banner-button" href="<?php print \Podlove\Modules\Onboarding\Settings\OnboardingPage::get_page_link() ?>"><?php print __('Get started', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script type="text/javascript">
      const podloveBanner = document.getElementById('podlove-panel-wrap');
      function hiddenPodloveBanner() {
        podloveBanner.classList.add('hidden');
      }
      const dismissLink = document.getElementById('podlove-panel-banner-dismiss');
      if (dismissLink !== undefined && dismissLink !== null) {
        dismissLink.addEventListener('click', function(){
          fetch(ajaxurl + '?' + new URLSearchParams({
              action: 'podlove-banner-hide',
              _podlove_nonce: '<?php echo wp_create_nonce('podlove_onboarding'); ?>'
            }),
            {
              method: 'GET'
          }).then(response => {
            if (response.ok) {
              hiddenPodloveBanner();
            }
          })
        });
      }
    </script>
<?php
  }

  public function add_scripts_and_styles()
  {
    wp_register_style('podlove-onboarding-banner-style', $this->get_module_url().'/css/podlove-onboarding-banner.css');
    wp_enqueue_style('podlove-onboarding-banner-style');
  }

  public function add_onboarding_menu()
  {
    new OnboardingPage(\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE);
  }

  /**
   * Onboarding options:
   *    - hide banner
   *    - type: start / import
   *    - feedurl
   */
  public static function is_banner_hide()
  {
    $onboarding_options = self::get_options();
    if (isset($onboarding_options['hide_banner'])) {
      return $onboarding_options['hide_banner'];
    }

    return false;
  }

  public static function set_banner_hide($option)
  {
    $onboarding_options = self::get_options();
    if (strtolower($option) == 'true') {
      $onboarding_options['hide_banner'] = true;
    } else {
      if (isset($onboarding_options['hide_banner'])) {
        unset($onboarding_options['hide_banner']);
      }
    }
    self::update_options($onboarding_options);
  }

  /** PHP 8.1 change this to an enum */
  public static function get_onboarding_type()
  {
    $onboarding_options = self::get_options();
    if (isset($onboarding_options['type'])) {
      return $onboarding_options['type'];
    }
  }

  public static function set_onboarding_type($option)
  {
    $onboarding_options = self::get_options();
    switch (strtolower($option)) {
      case 'start':
      case 'import':
        $onboarding_options['type'] = $option;

        break;

      default:
        if (isset($onboarding_options['type'])) {
          unset($onboarding_options['type']);
        }

        break;
    }
    self::update_options($onboarding_options);
  }

  public static function get_acknowlegde_option($user_id)
  {
    $option = get_user_meta($user_id, "podlove_onboarding_acknowledge", true);
    return $option;
  }

  public static function set_acknowledge_option($user_id, $option)
  {
    update_user_meta($user_id, "podlove_onboarding_acknowledge", $option);
  }


  private static function get_options()
  {
    return get_option('podlove_modules_onboarding', []);
  }

  private static function update_options($onboarding_options)
  {
    update_option('podlove_modules_onboarding', $onboarding_options);
  }

  /**
   * Onboarding API init (add to admin-route).
   */
  public function api_init()
  {
    $api_onboarding = new WP_REST_PodloveOnboarding_Controller();
    $api_onboarding->register_routes();
  }
}
