<?php
namespace Podlove\Modules\Transcripts\Model;

class VoiceAssignment extends \Podlove\Model\Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
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

    public static function is_voice_set($episode_id, $voice)
    {
        return (bool) self::find_one_by_where(
            sprintf('`episode_id` = "%d" AND `voice` = "%s"', (int) $episode_id, esc_sql($voice))
        );
    }
}

VoiceAssignment::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
VoiceAssignment::property('episode_id', 'INT');
VoiceAssignment::property('voice', 'VARCHAR(255)');
VoiceAssignment::property('contributor_id', 'INT UNSIGNED');
