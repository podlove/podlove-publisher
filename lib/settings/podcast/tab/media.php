<?php

namespace Podlove\Settings\Podcast\Tab;

use Podlove\Settings\Podcast\Tab;

class Media extends Tab
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

        $formKeys = ['media_file_base_uri'];

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
        ]; ?>
		<p>
			<?php _e('The Podlove Publisher expects all your media files to be in the same <strong>Upload Location</strong>.
					It should be a publicly readable directory containing all media files.
					You should not create a separate directory for each episode.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<?php

        \Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            $wrapper->string('media_file_base_uri', apply_filters('podlove_media_file_base_uri_form', [
                'label' => __('Upload Location', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Example: http://cdn.example.com/pod/', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => [
                    'class' => 'large-text required podlove-check-input',
                    'data-podlove-input-type' => 'url'
                ],
            ]));
        });
    }
}
