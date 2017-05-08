<?php 
namespace Podlove\Modules\Shows;
use \Podlove\Modules\Shows\Model\Show;

class TemplateExtensions {
	/**
	 * List of all Podcast shows
	 *
	 * **Examples**
	 * 
	 * ```
	 * This podcast features several shows:
	 * <ul>
	 * 	{% for show in podcast.shows %}
	 *  	<li>{{ show.title }}</li>
	 *  {% endfor %}
	 * </ul>
	 * ```
	 *
	 * @accessor
	 * @dynamicAccessor podcast.shows
	 */
	public static function accessorPodcastShows($return, $method_name, $episode) {
		return $episode->with_blog_scope(function() use ($return, $method_name, $episode) {
			return Show::all();
		});
	}

	/**
	 * Episode Show
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
	 */
	public static function accessorEpisodesShow($return, $method_name, $episode) {
		return $episode->with_blog_scope(function() use ($return, $method_name, $episode) {
			return Show::find_one_by_episode_id($episode->id);
		});
	}
}