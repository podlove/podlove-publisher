<?php 
namespace Podlove\Modules\Transcripts\Model;

class Transcript extends \Podlove\Model\Base
{
	use \Podlove\Model\KeepsBlogReferenceTrait;

	public function __construct() {
		$this->set_blog_id();
	}

	public static function delete_for_episode($episode_id)
	{
		global $wpdb;

		$sql = 'DELETE FROM '
		     . static::table_name()
		     . ' WHERE episode_id = ' . (int) $episode_id;

		return $wpdb->query($sql);
	}

	public static function get_voices_for_episode_id($episode_id)
	{
		global $wpdb;

		$sql = '
			SELECT DISTINCT t.voice, va.`contributor_id`
			FROM ' . static::table_name() . ' t 
			LEFT JOIN ' . VoiceAssignment::table_name() . ' va 
			  ON va.`episode_id` = t.`episode_id` AND va.voice = t.voice
			WHERE t.voice IS NOT NULL 
			  AND t.episode_id = ' . (int) $episode_id;

		return $wpdb->get_results($sql);
	}

	public static function get_transcript($episode_id)
	{
		global $wpdb;

		$sql = '
			SELECT t.start, t.end, t.content, t.voice, va.contributor_id, c.identifier
			FROM ' . static::table_name() . ' t 
			LEFT JOIN ' . VoiceAssignment::table_name() . ' va ON va.`episode_id` = t.`episode_id` AND va.voice = t.voice
			LEFT JOIN ' . \Podlove\Modules\Contributors\Model\Contributor::table_name() . ' c ON c.id = va.contributor_id
			WHERE t.episode_id = ' . (int) $episode_id . ' 
			ORDER BY t.start ASC';

		return $wpdb->get_results($sql);
	}
}

Transcript::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Transcript::property('episode_id', 'INT');
Transcript::property('start', 'INT UNSIGNED');
Transcript::property('end', 'INT UNSIGNED');
Transcript::property('voice', 'VARCHAR(255)');
Transcript::property('content', 'TEXT');
