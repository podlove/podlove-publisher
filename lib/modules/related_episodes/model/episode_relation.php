<?php 
namespace Podlove\Modules\RelatedEpisodes\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Episode;

class EpisodeRelation extends Base {
	public static function get_related_episodes($episode_id=FALSE) {
		global $wpdb;

		if ( ! $episode_id )
			return array();

		$sql = sprintf( 'SELECT
			*
			FROM
			'.Episode::table_name().' e
			WHERE id IN (
				SELECT right_episode_id FROM '.self::table_name().' WHERE left_episode_id = %1$d
				UNION
				SELECT left_episode_id FROM '.self::table_name().' WHERE right_episode_id = %1$d
			)', $episode_id );

		return Episode::find_all_by_sql($sql);
	}

}

EpisodeRelation::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeRelation::property( 'left_episode_id', 'INT' );
EpisodeRelation::property( 'right_episode_id', 'INT' );