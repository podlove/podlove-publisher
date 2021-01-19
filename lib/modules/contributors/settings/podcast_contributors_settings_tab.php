<?php

namespace Podlove\Modules\Contributors\Settings;

use Podlove\Modules\Contributors\Model\ShowContribution;
use Podlove\Settings\Podcast\Tab;

class PodcastContributorsSettingsTab extends Tab
{
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

        $formKeys = ['contributor'];

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
        ]; ?>
		<p>
			<?php echo sprintf(
            __('This is the current team of your podcast. Display this list using the shortcode %s', 'podlove-podcasting-plugin-for-wordpress'),
            '<code>[podlove-podcast-contributor-list]</code>'
        ); ?>
		</p>
		<?php

        \Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\DivWrapper($form);
            $podcast = $form->object;

            $wrapper->callback('contributors', [
                // 'label'    => __( 'Contributors', 'podlove-podcasting-plugin-for-wordpress' ),
                'callback' => [__CLASS__, 'podcast_form_extension_form'],
            ]);
        });
    }

    public static function podcast_form_extension_form()
    {
        $contributions = ShowContribution::all();

        // map indices to IDs
        $map = [];
        foreach ($contributions as $c) {
            $map[$c->id] = $c;
        }

        \Podlove\Modules\Contributors\Contributors::contributors_form_table($map, 'podlove_podcast[contributor]');
    }
}
