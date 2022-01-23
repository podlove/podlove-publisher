<?php

namespace Podlove\Modules\Soundbite;

class Soundbite extends \Podlove\Modules\Base
{
    protected $module_name = 'Soundbite';
    protected $module_description = 'Points to a soundbite within a podcast episode. The intended use includes episodes previews, discoverability, audiogram generation, episode highlights, etc. (adds podcast::soundbite tag to RSS feed)';
    protected $module_group = 'metadata';

    public function load()
    {
        add_filter('podlove_episode_form_data', [$this, 'extend_epsiode_form'], 10, 2);

        $this->add_soundbite_to_feed();
    }

    public function extend_epsiode_form($form_data, $episode)
    {
        $form_data[] = [
            'type' => 'callback',
            'key' => 'soundbite',
            'options' => [
                'label' => __('Soundbite', 'podlove-podcasting-plugin-for-wordpress'),
                'callback' => [$this, 'soundbite_form'],
            ],
            'position' => 456,
        ];

        return $form_data;
    }

    public function soundbite_form()
    {
        ?>
            <div id="podlove-soundbite-app"><soundbite></soundbite></div>
        <?php
    }

    public function add_soundbite_to_feed()
    {
        add_action('podlove_append_to_feed_entry', [$this, 'add_soundbite_to_episode_feed'], 10, 4);
    }

    public function add_soundbite_to_episode_feed($podcast, $episode, $feed, $format)
    {
        if ($episode->get_soundbite_start() && $episode->get_soundbite_duration()) {
            $title = $episode->soundbite_title;
            $start = $episode->soundbite_start;
            $duration = $episode->soundbite_duration;

            $start_sec = \Podlove\NormalPlayTime\Parser::parse($start, 'ms');
            $start_sec = $start_sec / 1000.;
            $duration_sec = \Podlove\NormalPlayTime\Parser::parse($duration, 'ms');
            $duration_sec = $duration_sec / 1000.;

            if ($duration_sec > 0) {
                $doc = new \DOMDocument();
                $node = $doc->createElement('podcast:soundbite');
                if ($title && strlen($title) > 0) {
                    $text = $doc->createTextNode($title);
                    $node->appendChild($text);
                } else {
                    $text = $doc->createTextNode('');
                    $node->appendChild($text);
                }
                $attr = $doc->createAttribute('startTime');
                $attr->value = number_format($start_sec, 3);
                $node->appendChild($attr);
                $attr = $doc->createAttribute('duration');
                $attr->value = number_format($duration_sec, 3);
                $node->appendChild($attr);

                $xml = $doc->saveXML($node);

                echo "\n\t\t".$xml."\n";
            }
        }
    }
}
