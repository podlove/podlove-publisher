<?php 
namespace Podlove\Modules\Seasons;

use \Podlove\Modules\Seasons\Model\Season;

class TemplateExtensions {

	/**
	 * List of podcast seasons
	 * 
	 * @accessor
	 * @dynamicAccessor podcast.seasons
	 */
	public static function accessorPodcastSeasons($return, $method_name, $podcast, $args = []) {
		return $podcast->with_blog_scope(function() use ($return, $method_name, $podcast, $args) {
			return array_map(function($season) {
				return new Template\Season($season);
			}, Season::find_all_by_where("1 = 1 ORDER BY start_date ASC"));
		});
	}

	/**
	 * Get season for an episode
	 * 
	 * @accessor
	 * @dynamicAccessor episode.season
	 */
	public static function accessorEpisodeSeason($return, $method_name, $episode, $post, $args = []) {
		return new Template\Season(Model\Season::for_episode($episode));
	}

}
