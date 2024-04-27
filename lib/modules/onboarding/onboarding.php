<?php

namespace Podlove\Modules\Onboarding;

use Podlove\Modules\Onboarding\Settings\OnboardingPage;
use Podlove\Api\Admin\WP_REST_PodloveOnboarding_Controller;

class Onboarding extends \Podlove\Modules\Base
{
  protected $module_name = 'Onboarding';
  protected $module_description = 'Handling the onboarding to the Podlove Publisher';
  protected $module_group = 'system';

  public function load()
  {
    if (\podlove_is_onboarding_active()) {
      add_action('admin_enqueue_scripts', [$this, 'add_scripts_and_styles']);
      add_action('admin_notices', [$this, 'onboarding_banner']);
      add_action('admin_menu', [$this, 'add_onboarding_menu'], 20);
      add_action('rest_api_init', [$this, 'api_init']);
    }
  }

  public static function is_visible()
  {
    return \podlove_is_onboarding_active();
  }

  public function onboarding_banner()
  {
    if (self::is_banner_hide()) {
      return;
    }

    if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'podlove_settings_onboarding_handle') {
      return;
    } ?>

    <div id="podlove-banner" class="podlove-banner">
      <div class="podlove-banner-left">
        <div class="podlove-banner-image">
          <img src="<?php print \Podlove\PLUGIN_URL.'/images/logo/podlove-publisher-icon-500.png'; ?>" />
        </div>
      </div>
      <div class="podlove-banner-right">
        <div>
          <?php
          echo sprintf(
            '<a id="podlove-banner-dismiss" class="podlove-banner-dismiss" href="#"></a>'
        ); ?>
        </div>
        <h2 class="podlove-banner-head"><?php print(__('Podlove Onboarding', 'podlove-podcasting-plugin-for-wordpress')); ?></h2>
          <p class="podlove-banner-text">
            <?php print(__('Do you want to create a new podcast? Or do you already have a podcast and want to migrate? Try our Onboarding and Migration Assistant to set up your podcast.', 'podlove-podcasting-plugin-for-wordpress')); ?>
          </p>
          <a class="podlove-banner-button" href="<?php print \Podlove\Modules\Onboarding\Settings\OnboardingPage::get_page_link() ?>"><?php print __('Start Onboarding', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
        </div>
      </div>
    </div>
    <script type="text/javascript">
      const podloveBanner = document.getElementById('podlove-banner');
      function hiddenPodloveBanner() {
        podloveBanner.classList.add('hidden');
      }
      const dismissLink = document.getElementById('podlove-banner-dismiss');
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
