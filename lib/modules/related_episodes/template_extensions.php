<?php

namespace Podlove\Modules\RelatedEpisodes;

use Podlove\Model;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use Podlove\Template;

class TemplateExtensions
{
    /**
     * List of Related Episodes.
     *
     * @accessor
     * @dynamicAccessor episode.relatedEpisodes
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     * @param mixed $post
     * @param mixed $args
     */
    public static function accessorRelatedEpisodes($return, $method_name, $episode, $post, $args = [])
    {
        $episodes = [];

        foreach (EpisodeRelation::get_related_episodes($episode->id, ['only_published' => true]) as $related_episode) {
            $episodes[] = new Template\Episode(Model\Episode::find_by_id($related_episode->id));
        }

        return $episodes;
    }
}
