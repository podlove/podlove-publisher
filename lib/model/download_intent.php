<?php
namespace Podlove\Model;

class DownloadIntent extends Base {

	public static function total_by_episode_id($episode_id) {
		global $wpdb;

		$sql = "
			SELECT
				COUNT(*)
			FROM
				" . \Podlove\Model\DownloadIntent::table_name() . "
			WHERE
				media_file_id IN (
					SELECT id FROM " . \Podlove\Model\MediaFile::table_name() . " WHERE episode_id = %d
				)
		";

		return $wpdb->get_var(
			$wpdb->prepare($sql, $episode_id)
		);
	}

}

DownloadIntent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntent::property( 'user_agent_id', 'INT' );
DownloadIntent::property( 'media_file_id', 'INT' );
DownloadIntent::property( 'accessed_at', 'DATETIME' );
DownloadIntent::property( 'source', 'VARCHAR(255)' );
DownloadIntent::property( 'context', 'VARCHAR(255)' );
DownloadIntent::property( 'ip', 'VARCHAR(255)' );