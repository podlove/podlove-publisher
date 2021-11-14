<?php

namespace Podlove\Modules\Transcripts;

use Podlove\Model\Episode;
use Podlove\Modules\Transcripts\Model\Transcript;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;

use WP_REST_Controller;
use WP_REST_Server;

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
                $voice_assignment = new VoiceAssignment();
                $voice_assignment->episode_id = $episode->id;
                $voice_assignment->voice = $voice;
                $voice_assignment->contributor_id = (int) $id;
                $voice_assignment->save();
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



class WP_REST_PodloveTranscripts_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'transcripts';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.'),
                    'type' => 'integer',
                    'required' => 'true'
                ],
            ],
            [
                'args' => [
                    'limit' => [
                        'description' => __('Type of transcript file'),
                        'type' => 'string',
                    ],
                    'offset' => [
                        'description' => __('Transcription file'),
                        'type' => 'string',
                    ]
                ],
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'type' => [
                        'description' => __('Type of transcript file'),
                        'type' => 'string',
                        'required' => 'true'
                    ],
                    'file' => [
                        'description' => __('Transcription file'),
                        'type' => 'string',
                        'required' => 'true'
                    ]
                ],
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_transcript'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],

            ]
        ]);
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)/chaptions/(?P<chaption_id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.'),
                    'type' => 'integer',
                    'required' => 'true'
                ],
                'chaption_id' => [
                    'description' => __('Unique identifier for the part of the transcription (called chaption).'),
                    'type' => 'integer',
                    'required' => 'true'
                ],
            ],
            [
                'args' => [
                    'start' => [
                        'description' => __('Timestamp begin of the chaption'),
                        'type' => 'string',
                    ],
                    'end' => [
                        'description' => __('Timestamp end of the chaption'),
                        'type' => 'string',
                    ],
                    'voices' => [
                        'description' => __('Name of the speaker'),
                        'type' => 'string',
                    ],
                    'content' => [
                        'description' => __('Content'),
                        'type' => 'string',
                    ]
                ],
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'description' => __('Add a chaption to the transcript'),
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'args' => [
                    'start' => [
                        'description' => __('Timestamp begin of the chaption'),
                        'type' => 'string',
                    ],
                    'end' => [
                        'description' => __('Timestamp end of the chaption'),
                        'type' => 'string',
                    ],
                    'voices' => [
                        'description' => __('Name of the speaker'),
                        'type' => 'string',
                    ],
                    'content' => [
                        'description' => __('Content'),
                        'type' => 'string',
                    ]
                ],
                'description' => __('Edit a chaption of the transcript'),
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'description' => __('Delete a chaption of the transcript'),
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],

            ]
        ]);

    }

    public function get_item_permissions_check( $request )
    {
        return true;
    }

    public function get_item( $request )
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if (!$episode)
            return new \Podlove\Api\Error\NotFound();

        $limit = 0;
        $offset = 0;
        $count = 0;

        if (isset($request['limit']))
            $limit = $request['limit'];
        if (isset($request['offset']))
            $offset = $request['offset'];

        $count = Transcript::get_transcript_count($id);

        if ($offset === 0 && $limit === 0) {
            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'count' => $count
            ]);
        }
        else {
            return Transcript::prepare_transcript(Transcript::get_transcript_offset_limit($id, $offset, $limit));
        }

    }

    public function create_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }
        return true;
    }

    public function create_item( $request )
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok'
        ]); 
    }

    public function update_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_item( $request )
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok' 
        ]);
    }

    public function delete_item_permissions_check( $request )
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function delete_transcript( $request )
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        Transcript::delete_for_episode($id);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok' 
        ]);

    }

    public function delete_item( $request )
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }

        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok' 
        ]);
    }
}
