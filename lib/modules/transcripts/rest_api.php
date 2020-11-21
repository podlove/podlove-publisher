<?php

namespace Podlove\Modules\Transcripts;

use Podlove\Model\Episode;
use Podlove\Modules\Transcripts\Model\Transcript;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;

class REST_API
{
    const api_namespace = 'podlove/v1';
    const api_base = 'transcripts';

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base.'/(?P<id>[\d]+)/voices', [
            'args' => [
                'id' => [
                    'description' => __('post id'),
                    'type' => 'integer'
                ]
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_voices'],
                'permission_callback' => [$this, 'permission_check']
            ]
        ]);

        register_rest_route(self::api_namespace, self::api_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('episode id'),
                    'type' => 'integer'
                ]
            ],
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_transcript'],
                'permission_callback' => '__return_true'
            ]
        ]);
    }

    public function update_voices($request)
    {
        $post_id = $request['id'];
        $episode = Episode::find_one_by_post_id($post_id);

        if (!$episode) {
            return new \WP_Error('podlove_rest_episode_not_found', 'episode does not exist', ['status' => 400]);
        }

        VoiceAssignment::delete_for_episode($episode->id);

        if (is_array($request['transcript_voice'])) {
            foreach ($request['transcript_voice'] as $voice => $id) {
                if ($id > 0) {
                    $voice_assignment = new VoiceAssignment();
                    $voice_assignment->episode_id = $episode->id;
                    $voice_assignment->voice = $voice;
                    $voice_assignment->contributor_id = $id;
                    $voice_assignment->save();
                }
            }
        }

        return rest_ensure_response(['status' => 'ok']);
    }

    public function get_transcript($request)
    {
        $episode_id = $request->get_param('id');
        $mode = $request->get_param('mode') ?? 'flat';

        if ($mode != 'flat' && $mode != 'grouped') {
            return new \WP_Error('podlove_rest_episode_invalid_parameter', 'paramenter mode only allows flat or grouped', ['status' => 400]);
        }

        return Transcript::prepare_transcript(Transcript::get_transcript($episode_id));
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('rest_forbidden', 'sorry, you do not have permissions to use this REST API endpoint', ['status' => 401]);
        }

        return true;
    }
}
