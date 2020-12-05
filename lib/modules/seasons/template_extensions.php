<?php

namespace Podlove\Modules\Seasons;

use Podlove\Modules\Seasons\Model\Season;

class TemplateExtensions
{
    /**
     * List of podcast seasons.
     *
     * Parameters:
     *
     * - **order:** (optional) "DESC" or "ASC". Default: "ASC"
     *
     * @accessor
     * @dynamicAccessor podcast.seasons
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $podcast
     * @param mixed $args
     */
    public static function accessorPodcastSeasons($return, $method_name, $podcast, $args = [])
    {
        return $podcast->with_blog_scope(function () use ($return, $method_name, $podcast, $args) {
            $order = isset($args['order']) && strtoupper($args['order']) == 'DESC' ? 'DESC' : 'ASC';
            $seasons = Season::find_all_by_where("1 = 1 ORDER BY start_date {$order}");

            return array_map(function ($season) {
                return new Template\Season($season);
            }, $seasons);
        });
    }

    /**
     * Get season for an episode.
     *
     * @accessor
     * @dynamicAccessor episode.season
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     * @param mixed $post
     * @param mixed $args
     */
    public static function accessorEpisodeSeason($return, $method_name, $episode, $post, $args = [])
    {
        $season = Model\Season::for_episode($episode);

        if ($season === null) {
            return new \stdClass();
        }

        return new Template\Season($season);
    }
}
