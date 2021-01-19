<?php

namespace Podlove\Settings\Podcast\Tab;

use Podlove\Model\Episode;
use Podlove\Settings\Podcast\Tab;

class Player extends Tab
{
    public function init()
    {
        add_action($this->page_hook, [$this, 'register_page']);
        add_action('admin_init', [$this, 'process_form']);
    }

    public function process_form()
    {
        if (!isset($_POST['podlove_webplayer_settings']) || !$this->is_active()) {
            return;
        }

        $formKeys = array_keys(\Podlove\get_webplayer_defaults());

        $settings = get_option('podlove_webplayer_settings');
        foreach ($formKeys as $key) {
            $settings[$key] = $_POST['podlove_webplayer_settings'][$key] ?? null;
        }

        update_option('podlove_webplayer_settings', $settings);
        \Podlove\Cache\TemplateCache::get_instance()->setup_purge();

        header('Location: '.$this->get_url());
    }

    public static function get_form_data()
    {
        $form_data = [
            [
                'type' => 'select',
                'key' => 'version',
                'options' => [
                    'label' => __('Web Player', 'podlove-podcasting-plugin-for-wordpress'),
                    'description' => '',
                    'options' => [
                        'player_v4' => __('Podlove Web Player 4 (deprecated)', 'podlove-podcasting-plugin-for-wordpress'),
                        'player_v5' => __('Podlove Web Player 5', 'podlove-podcasting-plugin-for-wordpress'),
                        'podigee' => __('Podigee Podcast Player', 'podlove-podcasting-plugin-for-wordpress'),
                    ],
                ],
                'position' => 1000,
            ],
        ];

        // allow modules to add / change the form
        $form_data = apply_filters('podlove_player_form_data', $form_data);

        // sort entities by position
        usort($form_data, [__CLASS__, 'compare_by_position']);

        return $form_data;
    }

    public static function compare_by_position($a, $b)
    {
        $pos_a = isset($a['position']) ? (int) $a['position'] : 0;
        $pos_b = isset($b['position']) ? (int) $b['position'] : 0;

        if ($a == $b || $pos_a == $pos_b) {
            return 0;
        }

        return ($pos_a < $pos_b) ? 1 : -1;
    }

    public function register_page()
    {
        $form_attributes = [
            'context' => 'podlove_webplayer_settings',
            'action' => $this->get_url(),
        ];

        $form_data = self::get_form_data();

        \Podlove\Form\build_for((object) \Podlove\get_webplayer_settings(), $form_attributes, function ($form) use ($form_data) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            foreach ($form_data as $entry) {
                $wrapper->{$entry['type']}($entry['key'], $entry['options']);
            }
        });

        $this->preview_section();
    }

    public function preview_section()
    {
        $episode = Episode::latest();
        if ($episode) {
            $this->preview_player($episode);
        } else {
            $this->preview_player(new Episode());
        }
    }

    public function preview_player($episode)
    {
        $printer = \Podlove\Modules\PodloveWebPlayer\Podlove_Web_Player::get_player_printer($episode);
        if ($printer && method_exists($printer, 'render')) {
            echo '<h3>Preview</h3>';
            echo $printer->render('preview');
        }
    }
}
