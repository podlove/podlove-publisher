<?php

namespace Podlove\Modules\Social\Settings;

use Podlove\Settings\Podcast\Tab;

class PodcastSettingsSocialTab extends Tab
{
    private static $nonce = 'update_podcast_services';

    public function init()
    {
        add_action($this->page_hook, [$this, 'register_page']);
        add_action('admin_init', [$this, 'process_form']);
    }

    public function process_form()
    {
        if (!isset($_POST['podlove_podcast']) || !$this->is_active()) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'], self::$nonce)) {
            return;
        }

        $formKeys = ['services'];

        $settings = get_option('podlove_podcast');
        foreach ($formKeys as $key) {
            $settings[$key] = $_POST['podlove_podcast'][$key];
        }
        update_option('podlove_podcast', $settings);

        header('Location: '.$this->get_url());
    }

    public function register_page()
    {
        $podcast = \Podlove\Model\Podcast::get();

        $form_attributes = [
            'context' => 'podlove_podcast',
            'action' => $this->get_url(),
            'is_table' => false,
            'nonce' => self::$nonce
        ]; ?>
		<p>
			<?php echo sprintf(
            __('These are the current social media acccount of your podcast. Display this list using the shortcode %s', 'podlove-podcasting-plugin-for-wordpress'),
            '<code>[podlove-podcast-social-media-list]</code>'
        ); ?>
		</p>
		<?php
		\Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
		    $wrapper = new \Podlove\Form\Input\DivWrapper($form);

		    $wrapper->callback('services', [
		        'callback' => [__CLASS__, 'podcast_form_extension_form'],
		    ]);
		});
    }

    public static function podcast_form_extension_form()
    {
        $services = \Podlove\Modules\Social\Model\ShowService::find_by_category();
        \Podlove\Modules\Social\Social::services_form_table($services, 'podlove_podcast[services]');
    }
}
