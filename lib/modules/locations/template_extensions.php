<?php

namespace Podlove\Modules\Locations;

use Podlove\Modules\Locations\Model\Location;
use Podlove\Modules\Locations\Settings\Podcast_Settings_Tab;
use Podlove\Template\Episode;

class Template_Extensions
{
    public function __construct()
    {
        Episode::add_accessor('locationSubjectName', [__CLASS__, 'accessor_subject_name'], 4);
        Episode::add_accessor('locationSubjectLat', [__CLASS__, 'accessor_subject_lat'], 4);
        Episode::add_accessor('locationSubjectLng', [__CLASS__, 'accessor_subject_lng'], 4);
        Episode::add_accessor('locationSubjectAddress', [__CLASS__, 'accessor_subject_address'], 4);

        Episode::add_accessor('locationCreatorName', [__CLASS__, 'accessor_creator_name'], 4);
        Episode::add_accessor('locationCreatorLat', [__CLASS__, 'accessor_creator_lat'], 4);
        Episode::add_accessor('locationCreatorLng', [__CLASS__, 'accessor_creator_lng'], 4);
        Episode::add_accessor('locationCreatorAddress', [__CLASS__, 'accessor_creator_address'], 4);
    }

    public static function accessor_subject_name($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_name');
    }

    public static function accessor_subject_lat($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_lat');
    }

    public static function accessor_subject_lng($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_lng');
    }

    public static function accessor_subject_address($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_address');
    }

    public static function accessor_creator_name($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_name');
    }

    public static function accessor_creator_lat($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_lat');
    }

    public static function accessor_creator_lng($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_lng');
    }

    public static function accessor_creator_address($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_address');
    }

    private static function get_field($episode_id, $rel, $field)
    {
        $location = Location::find_by_episode_id_and_rel($episode_id, $rel);

        if ($location && isset($location->{$field}) && $location->{$field} !== '') {
            return $location->{$field};
        }

        if ($rel === 'creator' && Podcast_Settings_Tab::has_podcast_location()) {
            $podcast_data = Podcast_Settings_Tab::get_podcast_location();

            return $podcast_data[$field] ?? '';
        }

        return '';
    }
}
