<?php 
namespace Podlove\Modules\RelatedEpisodes\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Episode;

class EpisodeRelation extends Base {
	public static function get_related_episodes($episode_id=FALSE) {
		global $wpdb;

		if ( ! $episode_id )
			return array();

		$sql = sprintf( '( 
				SELECT ep.* 
				FROM '.self::table_name().' rel 
				INNER JOIN '.Episode::table_name().' ep 
				ON rel.right_episode_id = ep.id 
				WHERE left_episode_id=%1$d
			) UNION (
				SELECT ep.* from '.self::table_name().' rel
				INNER JOIN '.Episode::table_name().' ep 
				ON rel.left_episode_id = ep.id 
				WHERE right_episode_id=%1$d
			);', $episode_id );

		return $wpdb->get_results($sql);
	}

}

EpisodeRelation::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeRelation::property( 'left_episode_id', 'INT' );
EpisodeRelation::property( 'right_episode_id', 'INT' );