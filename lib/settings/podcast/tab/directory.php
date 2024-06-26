<?php

namespace Podlove\Settings\Podcast\Tab;

use Podlove\Settings\Podcast\Tab;

class Directory extends Tab
{
    private static $nonce = 'update_podcast_settings_directory';

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

        $formKeys = [
            'author_name',
            'publisher_name',
            'publisher_url',
            'owner_name',
            'owner_email',
            'category_1',
            'category_2',
            'category_3',
            'explicit',
            'complete',
            'funding_url',
            'funding_label',
            'copyright',
        ];

        $settings = get_option('podlove_podcast');
        foreach ($formKeys as $key) {
            if (isset($_POST['podlove_podcast'][$key])) {
                $settings[$key] = stripslashes($_POST['podlove_podcast'][$key]);
            } else {
                $settings[$key] = null;
            }
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
            'nonce' => self::$nonce
        ]; ?>
		<p>
			<?php _e('You may provide additional information about your podcast that may or may not be used by podcast directories like iTunes.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<?php

        \Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);
            $podcast = $form->object;

            $wrapper->string('author_name', [
                'label' => __('Author Name', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Publicly displayed in Podcast directories.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input'],
            ]);

            $wrapper->string('publisher_name', [
                'label' => __('Publisher Name', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input'],
            ]);

            $wrapper->string('publisher_url', [
                'label' => __('Publisher URL', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
            ]);

            $wrapper->string('owner_name', [
                'label' => __('Owner Name', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Used by iTunes and other Podcast directories to contact you.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input'],
            ]);

            $wrapper->string('owner_email', [
                'label' => __('Owner Email', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Used by iTunes and other Podcast directories to contact you.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'email'],
            ]);

            $wrapper->select('category_1', [
                'label' => __('iTunes Categories', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => '',
                'type' => 'select',
                'options' => \Podlove\Itunes\categories(),
            ]);

            $wrapper->select('category_2', [
                'label' => __('iTunes Categories', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Optional. May be ignored by directories.'),
                'type' => 'select',
                'options' => \Podlove\Itunes\categories(),
            ]);

            $wrapper->select('category_3', [
                'label' => __('iTunes Categories', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Optional. May be ignored by directories.'),
                'type' => 'select',
                'options' => \Podlove\Itunes\categories(),
            ]);

            $wrapper->select('explicit', [
                'label' => __('Explicit Content?', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('True: If you specify true, indicating the presence of explicit content, directories may display an Explicit parental advisory graphic for your podcast. False: If you specify false, indicating that your podcast does not contain explicit language or adult content, directories may display a Clean parental advisory graphic for your podcast.', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => [0 => 'false', 1 => 'true'],
            ]);

            $wrapper->checkbox('complete', [
                'label' => __('Podcast complete?', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Shows that this Podcast is finished and no further episodes will be added.', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => false,
            ]);

            $wrapper->string('funding_url', [
                'label' => __('Funding URL', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Can be used by podcatchers show funding/donation links for the podcast.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
            ]);

            $wrapper->string('funding_label', [
                'label' => __('Funding Label', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Label for funding/donation URL.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text'],
            ]);

            $wrapper->string('copyright', [
                'label' => __('Copyright', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Copyright notice for content in the channel. If you leave this blank, a default copyright notice will appear in the feed because it is required by the Apple Podcasts Connect.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text', 'placeholder' => \esc_attr($podcast->default_copyright_claim())],
            ]);
        });
    }
}
