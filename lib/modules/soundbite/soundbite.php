<?php

namespace Podlove\Modules\Soundbite;

use Podlove\Model\Episode;

class Soundbite extends \Podlove\Modules\Base
{
    protected $module_name = 'Soundbite';
    protected $module_description = 'Manage a podcast::soundbite tag.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_filter('podlove_episode_form_data', [$this, 'extend_epsiode_form'], 10, 2);
    }

    public function extend_epsiode_form($form_data, $epsiode)
    {
        $form_data[] = [
            'type' => 'callback',
            'key' => 'soundbite',
            'options' => [
                'label' => __('podcast::soundbite', 'podlove-podcasting-plugin-for-wordpress'),
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

}