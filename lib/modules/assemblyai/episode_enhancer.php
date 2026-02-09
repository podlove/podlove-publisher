<?php

namespace Podlove\Modules\AssemblyAI;

class EpisodeEnhancer
{
    private $module;

    public function __construct(AssemblyAI $module)
    {
        $this->module = $module;

        if ($this->module->get_module_option('assemblyai_api_key', '') != '') {
            add_filter('podlove_episode_form_data', [$this, 'assemblyai_episode'], 10, 2);
        }
    }

    public function assemblyai_episode($form_data, $episode)
    {
        $form_data[] = [
            'type' => 'callback',
            'key' => 'assemblyai_transcription_form',
            'options' => [
                'callback' => [$this, 'assemblyai_episode_form'],
            ],
            'position' => 475, // just above Transcripts (480)
        ];

        return $form_data;
    }

    public function assemblyai_episode_form()
    {
        ?>
        <div data-client="podlove" style="margin: 15px 0;">
          <podlove-assemblyai></podlove-assemblyai>
        </div>
		<?php
    }
}
