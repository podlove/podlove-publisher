<?php

namespace Podlove\Modules\Transcripts;

use Podlove\Modules\Transcripts\Model\Transcript;

class TranscriptMutation
{
    /**
     * Serialize transcript and voice writes for one episode, including across requests.
     *
     * Callbacks return true on success or a WP_Error to roll back their changes.
     *
     * @param mixed $episode_id
     */
    public static function run($episode_id, callable $callback)
    {
        global $wpdb;

        // MySQL lock names are server-wide and limited to 64 characters.
        $scope = DB_NAME.':'.Transcript::table_name().':'.(int) $episode_id;
        $lock = 'podlove_transcript_'.substr(hash('sha256', $scope), 0, 40);

        if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', $lock)) !== '1') {
            return new \WP_Error(
                'podlove_transcript_busy',
                __(
                    'The transcript is currently being updated. Please try again.',
                    'podlove-podcasting-plugin-for-wordpress'
                ),
                ['status' => 409]
            );
        }

        $in_transaction = false;

        try {
            if ($wpdb->query('START TRANSACTION') === false) {
                throw new \RuntimeException('Could not start transcript transaction.');
            }
            $in_transaction = true;
            $result = $callback();

            if (is_wp_error($result)) {
                return $result;
            }

            if ($wpdb->query('COMMIT') === false) {
                throw new \RuntimeException('Could not commit transcript transaction.');
            }
            $in_transaction = false;

            return $result;
        } catch (\Throwable $e) {
            \Podlove\Log::get()->addError('Transcript update failed: '.$e->getMessage());

            return new \WP_Error(
                'podlove_transcript_import_failed',
                __('Could not save the transcript. Please try again.', 'podlove-podcasting-plugin-for-wordpress'),
                ['status' => 500]
            );
        } finally {
            if ($in_transaction) {
                $wpdb->query('ROLLBACK');
            }
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }
}
