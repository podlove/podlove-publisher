<?php 
namespace Podlove\Modules\RelatedEpisodes;

use \Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;

class TemplateExtensions {

	public static function accessorRelatedEpisodes($return, $method_name, $episode, $post, $args = array()) {
		$episodes = array();

		foreach (EpisodeRelation::get_related_episodes($episode->id) as $related_episode) {
			$episodes[] = new \Podlove\Template\Episode( \Podlove\Model\Episode::find_by_id($related_episode->id));
		}
		return $episodes;
	}

}