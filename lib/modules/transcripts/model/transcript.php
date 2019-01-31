<?php
namespace Podlove\Modules\Transcripts\Model;

class Transcript extends \Podlove\Model\Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    public static function exists_for_episode($episode_id)
    {
        global $wpdb;

        $sql = 'SELECT id FROM '
        . static::table_name()
        . ' WHERE episode_id = ' . (int) $episode_id
            . ' LIMIT 1';

        return $wpdb->get_var($sql) > 0;
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
			SELECT t.start, t.end, t.content, t.voice, va.contributor_id
			FROM ' . static::table_name() . ' t
			LEFT JOIN ' . VoiceAssignment::table_name() . ' va ON va.`episode_id` = t.`episode_id` AND va.voice = t.voice
			LEFT JOIN ' . \Podlove\Modules\Contributors\Model\Contributor::table_name() . ' c ON c.id = va.contributor_id
			WHERE t.episode_id = ' . (int) $episode_id . '
			ORDER BY t.start ASC';

        return $wpdb->get_results($sql);
    }

    /**
     * Prepares transcript from database for further processing or viewing.
     *
     * Example
     *
     *   $transcript = Transcript::get_transcript($episode_id);
     *   $transcript = Transcript::prepare_transcript($transcript, 'grouped');
     *
     */
    public static function prepare_transcript($transcript, $mode = 'flat')
    {
        $transcript = array_map(function ($t) {
            return [
                'start'    => \Podlove\Modules\Transcripts\Renderer::format_time($t->start),
                'start_ms' => (int) $t->start,
                'end'      => \Podlove\Modules\Transcripts\Renderer::format_time($t->end),
                'end_ms'   => (int) $t->end,
                'speaker'  => $t->contributor_id,
                'voice'    => $t->voice,
                'text'     => $t->content,
            ];
        }, $transcript);

        if ($mode != 'flat') {
            $transcript = array_reduce($transcript, function ($agg, $item) {

                if (empty($agg)) {
                    $agg['items']        = [];
                    $agg['prev_speaker'] = null;
                    $agg['prev_voice']   = null;
                }

                $speaker = $item['speaker'];
                unset($item['speaker']);

                $voice = $item['voice'];
                unset($item['voice']);

                if ($agg['prev_voice'] == $voice) {
                    $agg['items'][count($agg['items']) - 1]['items'][] = $item;
                } else {
                    $agg['items'][] = [
                        'speaker' => $speaker,
                        'voice'   => $voice,
                        'items'   => [$item],
                    ];
                }

                $agg['prev_speaker'] = $speaker;
                $agg['prev_voice']   = $voice;

                return $agg;
            }, []);
            $transcript = $transcript['items'];
        }

        return $transcript;
    }
}

Transcript::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Transcript::property('episode_id', 'INT');
Transcript::property('start', 'INT UNSIGNED');
Transcript::property('end', 'INT UNSIGNED');
Transcript::property('voice', 'VARCHAR(255)');
Transcript::property('content', 'TEXT');
