<?php

namespace Podlove\Modules\Locations;

use Podlove\Model\Episode;
use Podlove\Modules\Locations\Model\Location;
use Podlove\Modules\Locations\Settings\Podcast_Settings_Tab;

class Locations extends \Podlove\Modules\Base
{
    protected $module_name = 'Locations';
    protected $module_description = 'Add subject and creator locations to episodes, templates, and Podcasting 2.0 feeds.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_action('podlove_module_was_activated_locations', [$this, 'was_activated']);
        add_action('podlove_delete_episode', [$this, 'on_delete_episode']);
        add_action('podlove_podcast_settings_tabs', [$this, 'podcast_settings_tab']);

        new Meta_Box();
        new Template_Extensions();
        new Feed_Extension();
    }

    public function was_activated()
    {
        Location::build();
    }

    public function on_delete_episode(Episode $episode)
    {
        Location::delete_for_episode($episode->id);
    }

    public function podcast_settings_tab($tabs)
    {
        $tabs->addTab(new Podcast_Settings_Tab(
            'location',
            __('Locations', 'podlove-podcasting-plugin-for-wordpress'),
            false
        ));

        return $tabs;
    }
}
