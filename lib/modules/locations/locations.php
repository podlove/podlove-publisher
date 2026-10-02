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
        add_action('podlove_xml_export', [$this, 'expandExportFile']);
        add_filter('podlove_import_jobs', [$this, 'expandImport']);

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

    /**
     * Append episode_location rows to the Publisher export XML.
     */
    public function expandExportFile(\SimpleXMLElement $xml)
    {
        $xml_group = $xml->addChild('xmlns:wpe:episode_locations');

        foreach (Location::all() as $location) {
            $xml_item = $xml_group->addChild('xmlns:wpe:episode_location');
            foreach (self::export_property_names() as $property_name) {
                $value = $location->{$property_name};
                if ($value === null || $value === '') {
                    continue;
                }
                $xml_item->addChild(
                    'xmlns:wpe:'.$property_name,
                    htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                );
            }
        }
    }

    /**
     * @param string[] $jobs
     *
     * @return string[]
     */
    public function expandImport($jobs)
    {
        if (!is_array($jobs)) {
            $jobs = [];
        }
        $jobs[] = '\Podlove\Modules\Locations\PodcastImportEpisodeLocationsJob';

        return $jobs;
    }

    /**
     * @return string[]
     */
    private static function export_property_names()
    {
        return [
            'id',
            'episode_id',
            'rel',
            'location_name',
            'location_lat',
            'location_lng',
            'location_address',
            'location_country',
            'location_osm',
        ];
    }
}
