<?php

namespace Podlove\Modules\Auphonic;

/**
 * Auphonic Episode Enhancer.
 *
 * Adds an Auphonic interface to the episode management forms.
 */
class EpisodeEnhancer
{
    private $module;

    public function __construct(Auphonic $module)
    {
        $this->module = $module;

        add_action('save_post', [$this, 'save_post']);

        if ($this->module->get_module_option('auphonic_api_key') != '') {
            add_filter('podlove_episode_form_data', [$this, 'auphonic_episodes'], 10, 2);
        }
    }

    public function auphonic_episodes($form_data, $episode)
    {
        $form_data[] = [
            'type' => 'callback',
            'key' => 'import_from_auphonic_form',
            'options' => [
                'label' => __('Auphonic', 'podlove-podcasting-plugin-for-wordpress'),
                'callback' => [$this, 'auphonic_episodes_form'],
            ],
            'position' => 500,
        ];

        return $form_data;
    }

    public function save_post($post_id)
    {
        if (get_post_type($post_id) !== 'podcast') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_REQUEST['_auphonic_production'])) {
            update_post_meta($post_id, '_auphonic_production', $_REQUEST['_auphonic_production']);
        }
    }

    public function auphonic_episodes_form()
    {
        ?>
        <div>
          Here Be Auphonic!
        </div>
        <div data-client="podlove" style="margin: 15px 0;">
          <podlove-auphonic></podlove-auphonic>
        </div>
		<?php
    }
}
