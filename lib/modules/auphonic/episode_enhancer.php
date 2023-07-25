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
                'callback' => [$this, 'auphonic_episodes_form'],
            ],
            'position' => 700,
        ];

        return $form_data;
    }

    public function auphonic_episodes_form()
    {
        ?>
        <div data-client="podlove" style="margin: 15px 0;">
          <podlove-auphonic></podlove-auphonic>
        </div>
		<?php
    }
}
