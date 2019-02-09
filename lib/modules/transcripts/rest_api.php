<?php
namespace Podlove\Modules\Transcripts;

use Podlove\Model\Episode;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;

class REST_API
{
    const api_namespace = 'podlove/v1';
    const api_base      = 'transcripts';

    public function register_routes()
    {
        register_rest_route(self::api_namespace, self::api_base . '/(?P<id>[\d]+)/voices', [
            'args' => [
                'id' => [
                    'description' => __('post id'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'  => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_voices'],
            ],
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

        foreach ($request['transcript_voice'] as $voice => $id) {
            if ($id > 0) {
                $voice_assignment                 = new VoiceAssignment;
                $voice_assignment->episode_id     = $episode->id;
                $voice_assignment->voice          = $voice;
                $voice_assignment->contributor_id = $id;
                $voice_assignment->save();
            }
        }

        $response = rest_ensure_response(["status" => "ok"]);
        return $response;
    }
}
