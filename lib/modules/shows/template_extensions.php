<?php

namespace Podlove\Modules\Shows;

class TemplateExtensions
{
    /**
     * List of all Podcast shows.
     *
     * **Examples**
     *
     * ```
     * This podcast features several shows:
     * <ul>
     *     {% for show in podcast.shows %}
     *      <li>{{ show.title }}</li>
     *  {% endfor %}
     * </ul>
     * ```
     *
     * @accessor
     * @dynamicAccessor podcast.shows
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     */
    public static function accessorPodcastShows($return, $method_name, $episode)
    {
        return $episode->with_blog_scope(function () use ($return, $method_name, $episode) {
            return array_map(function (Model\Show $show) {
                return new Template\Show($show);
            }, Model\Show::all());
        });
    }

    /**
     * Episode Show.
     *
     * **Examples**
     *
     * ```
     * This episode is part of the Show: {{ episode.show.title }} which deals with
     * {{ episode.show.summary }}
     * ```
     *
     * @accessor
     * @dynamicAccessor episode.show
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     */
    public static function accessorEpisodesShow($return, $method_name, $episode)
    {
        return $episode->with_blog_scope(function () use ($return, $method_name, $episode) {
            if ($show = Model\Show::find_one_by_episode_id($episode->id)) {
                return new Template\Show($show);
            }

            return null;
        });
    }
}
