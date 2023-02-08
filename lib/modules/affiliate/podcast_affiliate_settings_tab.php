<?php

namespace Podlove\Modules\Affiliate;

use Podlove\Settings\Podcast\Tab;

class PodcastAffiliateSettingsTab extends Tab
{
    private static $nonce = 'update_podcast_settings_affiliate';

    public function init()
    {
        add_action($this->page_hook, [$this, 'register_page']);
        add_action('admin_init', [$this, 'process_form']);
    }

    public function process_form()
    {
        if (!isset($_POST['podlove_affiliate']) || !$this->is_active()) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'], self::$nonce)) {
            return;
        }

        $settings = self::get_setting();

        foreach ($_POST['podlove_affiliate'] as $key => $value) {
            $settings[$key] = $value;
        }

        update_option('podlove_affiliate', $settings);

        header('Location: '.$this->get_url());
    }

    public function register_page()
    {
        $form_attributes = [
            'context' => 'podlove_affiliate',
            'action' => $this->get_url(),
            'nonce' => self::$nonce
        ]; ?>
    <p>
      <?php echo __('Register your Affiliate IDs', 'podlove-podcasting-plugin-for-wordpress'); ?>
    </p>
<?php

    \Podlove\Form\build_for((object) self::get_setting(), $form_attributes, function ($form) {
        $wrapper = new \Podlove\Form\Input\TableWrapper($form);

        $wrapper->string('amazon_de', [
            'label' => __('amazon.de', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Your amazon.de tracking id.', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => ['class' => 'regular-text podlove-check-input'],
        ]);

        $wrapper->string('thomann_de', [
            'label' => __('thomann.de', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Your thomann.de partner id.', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => ['class' => 'regular-text podlove-check-input'],
        ]);
    });
    }

    public static function get_setting()
    {
        return get_option('podlove_affiliate');
    }
}
