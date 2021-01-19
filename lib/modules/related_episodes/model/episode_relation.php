<?php

namespace Podlove\Modules\RelatedEpisodes\Model;

use Podlove\Model\Base;
use Podlove\Model\Episode;

class EpisodeRelation extends Base
{
    /**
     * Get episodes related to the given episode.
     *
     * @param bool|int $episode_id
     * @param array    $args       List of optional arguments.
     *                             only_published - If true, only return already published episodes. Default: false.
     *
     * @return array
     */
    public static function get_related_episodes($episode_id = false, $args = [])
    {
        global $wpdb;

        $defaults = ['only_published' => false];
        $args = wp_parse_args($args, $defaults);

        if (!$episode_id) {
            return [];
        }

        $filter_post_status = '';
        if ($args['only_published']) {
            $filter_post_status = 'AND p.post_status IN (\'publish\', \'private\')';
        }

        $sql = sprintf('SELECT
			e.*
			FROM
			'.Episode::table_name().' e
			INNER JOIN '.$wpdb->posts.' p ON p.ID = e.post_id
			WHERE e.id IN (
				SELECT right_episode_id FROM '.self::table_name().' WHERE left_episode_id = %1$d
				UNION
				SELECT left_episode_id FROM '.self::table_name().' WHERE right_episode_id = %1$d
			) '.$filter_post_status.'
			ORDER BY p.post_date_gmt ASC', $episode_id);

        return Episode::find_all_by_sql($sql);
    }
}

EpisodeRelation::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
EpisodeRelation::property('left_episode_id', 'INT');
EpisodeRelation::property('right_episode_id', 'INT');
