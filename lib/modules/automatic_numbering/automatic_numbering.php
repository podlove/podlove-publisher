<?php

namespace Podlove\Modules\AutomaticNumbering;

use Podlove\Model;

class Automatic_Numbering extends \Podlove\Modules\Base
{
    protected $module_name = 'Automatic Numbering';
    protected $module_description = 'Automatically increase the Episode number when creating episodes.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_filter('podlove_model_defaults', [$this, 'override_episode_attribute_defaults'], 10, 2);
        add_action('podlove_episode_meta_box_end', [$this, 'add_episode_scripts']);
    }

    public function override_episode_attribute_defaults(array $defaults, $model)
    {
        if ($model::name() !== Model\Episode::name()) {
            return $defaults;
        }

        return $this->append_episode_number_to_defaults($defaults, $model);
    }

    public function append_episode_number_to_defaults(array $defaults, Model\Episode $episode)
    {
        $next_number = $episode->get_next_episode_number();
        $defaults['number'] = $next_number;

        return $defaults;
    }

    public function add_episode_scripts()
    {
        ?>
        <script>
        function getNextEpisodeNumberForShow(showSlug) {
            const numberInput = document.getElementById('_podlove_meta_number');

            const params = new URLSearchParams({
                showSlug: showSlug,
                episodeId: podlove_vue.episode_id,
                action: 'podlove-episode-next-number'
            });

            fetch(ajaxurl + '?' + params.toString())
            .then((response) => response.json())
            .then((data) => {
              const episodeNumber = data.number;
              numberInput.value = episodeNumber;
            })
        }

        function podloveInitShowWidgetNumberingHook() {
          const showWidget = document.getElementById("showschecklist")
          const items = showWidget.querySelectorAll('li input');

          for (const item of items) {
            item.addEventListener('click', (e) => {
              const input = item;

              getNextEpisodeNumberForShow(input.value)
            })
          }
        }

        if (document.readyState !== 'loading') {
          podloveInitShowWidgetNumberingHook();
        } else {
          document.addEventListener('DOMContentLoaded', podloveInitShowWidgetNumberingHook);
        }
        </script>
        <?php
    }
}
