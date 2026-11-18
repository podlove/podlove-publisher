<?php

namespace Podlove\Modules\Locations;

use Podlove\Modules\Locations\Model\Location;
use Podlove\Modules\Locations\Settings\Podcast_Settings_Tab;

class Feed_Extension
{
    public function __construct()
    {
        add_action('podlove_append_to_feed_head', [$this, 'add_location_to_feed_head'], 10, 3);
        add_action('podlove_append_to_feed_entry', [$this, 'add_location_to_feed_entry'], 10, 4);
    }

    public function add_location_to_feed_head($podcast, $feed, $format)
    {
        if (!Podcast_Settings_Tab::has_podcast_location()) {
            return;
        }

        $data = Podcast_Settings_Tab::get_podcast_location();
        self::emit_location_tag($data, 'creator', "\n\t");
    }

    public function add_location_to_feed_entry($podcast, $episode, $feed, $format)
    {
        $subject = Location::find_by_episode_id_and_rel($episode->id, 'subject');
        if ($subject && self::has_location_data($subject)) {
            self::emit_location_tag([
                'location_name' => $subject->location_name,
                'location_lat' => $subject->location_lat,
                'location_lng' => $subject->location_lng,
                'location_osm' => $subject->location_osm,
                'location_country' => $subject->location_country,
            ], 'subject', "\n\t\t");
        }

        $creator = Location::find_by_episode_id_and_rel($episode->id, 'creator');
        if ($creator && self::has_location_data($creator)) {
            self::emit_location_tag([
                'location_name' => $creator->location_name,
                'location_lat' => $creator->location_lat,
                'location_lng' => $creator->location_lng,
                'location_osm' => $creator->location_osm,
                'location_country' => $creator->location_country,
            ], 'creator', "\n\t\t");
        }
    }

    private static function has_location_data($location)
    {
        return !empty($location->location_name)
            || (!empty($location->location_lat) && !empty($location->location_lng))
            || !empty($location->location_osm)
            || !empty($location->location_country);
    }

    private static function emit_location_tag($data, $rel, $indent)
    {
        $name = !empty($data['location_name']) ? esc_html($data['location_name']) : '';
        $attrs = sprintf('rel="%s"', esc_attr($rel));

        if (!empty($data['location_lat']) && !empty($data['location_lng'])) {
            $geo = sprintf('geo:%s,%s', $data['location_lat'], $data['location_lng']);
            $attrs .= sprintf(' geo="%s"', esc_attr($geo));
        }

        if (!empty($data['location_osm'])) {
            $attrs .= sprintf(' osm="%s"', esc_attr($data['location_osm']));
        }

        if (!empty($data['location_country'])) {
            $attrs .= sprintf(' country="%s"', esc_attr(strtoupper($data['location_country'])));
        }

        if ($name === '' && $attrs === sprintf('rel="%s"', esc_attr($rel))) {
            return;
        }

        if ($name) {
            echo sprintf('%s<podcast:location %s>%s</podcast:location>', $indent, $attrs, $name);
        } else {
            echo sprintf('%s<podcast:location %s />', $indent, $attrs);
        }
    }
}
