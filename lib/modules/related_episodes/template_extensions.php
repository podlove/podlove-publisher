<?php 
namespace Podlove\Modules\RelatedEpisodes;

use \Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use \Podlove\Template;
use \Podlove\Model;

class TemplateExtensions {

	/**
	 * List of Related Episodes
	 * 
	 * @accessor
	 * @dynamicAccessor episode.relatedEpisodes
	 */
	public static function accessorRelatedEpisodes($return, $method_name, $episode, $post, $args = array()) {
		$episodes = array();

		foreach (EpisodeRelation::get_related_episodes($episode->id, ['only_published' => true]) as $related_episode) {
			$episodes[] = new Template\Episode(Model\Episode::find_by_id($related_episode->id));
		}
		return $episodes;
	}

}