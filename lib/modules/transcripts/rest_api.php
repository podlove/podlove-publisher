<?php

namespace Podlove\Modules\Transcripts;

use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;
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
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                    'required' => 'true'
                ],
            ],
            [
                'args' => [
                    'limit' => [
                        'description' => __('How many entries should be delivered?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ],
                    'offset' => [
                        'description' => __('From which entry should the data be delivered?', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ],
                    'count' => [
                        'description' => __('How many entries are there? Ignored limit and offset.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'content' => [
                        'description' => __('Transcription file as plain text utf-8 encoded', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true'
                    ]
                ],
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'args' => [
                    'content' => [
                        'description' => __('Transcription file', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'required' => 'true'
                    ]
                ],
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, '/'.$this->rest_base.'/voices/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                    'required' => 'true'
                ],
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_voices'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'voice' => [
                        'description' => __('Voice', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'contributor_id' => [
                        'description' => __('Contributor Id assigned to the voice.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'integer',
                    ]
                ],
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_voices'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ]
        ]);
        register_rest_route($this->namespace, '/'.$this->rest_base.'/paragraphs/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the part of the transcription (called chaption).', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                    'required' => 'true'
                ]
            ],
            [
                'args' => [
                    'start' => [
                        'description' => __('Timestamp begin of the chaption', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'end' => [
                        'description' => __('Timestamp end of the chaption', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'voices' => [
                        'description' => __('Name of the speaker', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ],
                    'content' => [
                        'description' => __('Content', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item_transcripts'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'start' => [
                        'description' => __('Timestamp begin of the paragraph', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
                    ],
                    'end' => [
                        'description' => __('Timestamp end of the paragraph', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                        'validate_callback' => '\Podlove\Api\Validation::timestamp'
                    ],
                    'text' => [
                        'description' => __('Content', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'string',
                    ]
                ],
                'description' => __('Edit a chaption of the transcript', 'podlove-podcasting-plugin-for-wordpress'),
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item_transcripts'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'description' => __('Delete a chaption of the transcript', 'podlove-podcasting-plugin-for-wordpress'),
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item_transcripts'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ]
        ]);
    }

    public function get_item_permissions_check($request)
    {
        return true;
    }

    public function get_items($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $limit = 0;
        $offset = 0;

        if (isset($request['limit'])) {
            $limit = $request['limit'];
        }

        if (isset($request['offset'])) {
            $offset = $request['offset'];
        }

        if (isset($request['count'])) {
            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'count' => Transcript::get_transcript_count($id),
            ]);
        }

        if ($offset === 0 && $limit === 0) {
            $transcript = Transcript::get_transcript($id);
            $transcript = array_map(function ($t) {
                return [
                    'start' => \Podlove\Modules\Transcripts\Renderer::format_time($t->start),
                    'start_ms' => (int) $t->start,
                    'end' => \Podlove\Modules\Transcripts\Renderer::format_time($t->end),
                    'end_ms' => (int) $t->end,
                    'voice' => $t->voice,
                    'text' => $t->content,
                ];
            }, $transcript);

            $transcript = array_filter($transcript);
            $transcript = array_values($transcript);

            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'transcript' => $transcript,
            ]);
        }

        $count = Transcript::get_transcript_count($id);

        $transcript = Transcript::get_transcript_offset_limit($id, $offset, $limit);
        $transcript = array_map(function ($t) {
            return [
                'id' => (int) $t->id,
                'start' => \Podlove\Modules\Transcripts\Renderer::format_time($t->start),
                'start_ms' => (int) $t->start,
                'end' => \Podlove\Modules\Transcripts\Renderer::format_time($t->end),
                'end_ms' => (int) $t->end,
                'voice' => $t->voice,
                'text' => $t->content,
            ];
        }, $transcript);

        $transcript = array_filter($transcript);
        $transcript = array_values($transcript);

        $next_url = '';
        $prev_url = '';

        $next = $offset + $limit;
        if ($next < $count) {
            $next_url = $this->namespace.'/'.$this->rest_base.'/'.$id.'?offset='.$next.'?limit='.$limit;
        }

        $prev = $offset - $limit;
        if ($prev > 0) {
            $prev_url = $this->namespace.'/'.$this->rest_base.'/'.$id.'?offset='.$prev.'?limit='.$limit;
        }

        if ($prev_url && $next_url) {
            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'prev' => $prev_url,
                'next' => $next_url,
                'transcript' => $transcript,
            ]);
        }

        if ($prev_url) {
            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'prev' => $prev_url,
                'transcript' => $transcript,
            ]);
        }
        if ($next_url) {
            return new \Podlove\Api\Response\OkResponse([
                '_version' => 'v2',
                'next' => $next_url,
                'transcript' => $transcript,
            ]);
        }
    }

    public function get_item_transcripts($request)
    {
        $id = $request->get_param('id');
        $transcript = Transcript::find_by_id($id);

        if (!$transcript) {
            return new \Podlove\Api\Error\NotFound(
                'not_found',
                'transcript with id '.$id.' was not found'
            );
        }

        $data = [
            '_version' => 'v2',
            'id' => $id,
            'episode' => $transcript->episode_id,
            'start' => \Podlove\Modules\Transcripts\Renderer::format_time($transcript->start),
            'start_ms' => $transcript->start,
            'end' => \Podlove\Modules\Transcripts\Renderer::format_time($transcript->end),
            'end_ms' => $transcript->end,
            'voice' => $transcript->voice,
            'text' => $transcript->content
        ];

        return new \Podlove\Api\Response\OkResponse($data);
    }

    public function get_item_voices($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound();
        }

        $data = Transcript::get_voices_for_episode_id($id);

        return new \Podlove\Api\Response\OkResponse([
            '_version' => 'v2',
            'voices' => $data
        ]);
    }

    public function create_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function create_item($request)
    {
        return $this->update_item($request);
    }

    public function update_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function update_item($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound('not_found', 'Episode not found');
        }

        $file_content = '';

        if (isset($request['content'])) {
            $file_content = $request['content'];

            if (function_exists('mb_check_encoding') && !mb_check_encoding($file_content, 'UTF-8')) {
                \Podlove\Api\Error\NotSupported('not_supported', 'Error parsing webvtt file: must be UTF-8 encoded.');
            }

            $result = Transcripts::parse_webvtt($file_content);

            if ($result === false) {
                return new \Podlove\Api\Error\InternalServerError('internal_server_error', 'Sorry, we can not parse your vtt content.');
            }

            Transcript::delete_for_episode($episode->id);
            VoiceAssignment::delete_for_episode($episode->id);

            foreach ($result['cues'] as $cue) {
                $line = new Transcript();
                $line->episode_id = $episode->id;
                $line->start = $cue['start'] * 1000;
                $line->end = $cue['end'] * 1000;
                $line->voice = $cue['voice'];
                $line->content = $cue['text'];
                $line->save();
            }

            $voices = array_unique(array_map(function ($cue) {
                return $cue['voice'];
            }, $result['cues']));

            foreach ($voices as $voice) {
                $contributor = Contributor::find_one_by_property('identifier', $voice);

                if (!VoiceAssignment::is_voice_set($episode->id, $voice) && $contributor) {
                    $voice_assignment = new VoiceAssignment();
                    $voice_assignment->episode_id = $episode->id;
                    $voice_assignment->voice = $voice;
                    $voice_assignment->contributor_id = $contributor->id;
                    $voice_assignment->save();
                }
            }

            $transcript = Transcript::get_transcript($episode->id);
            $transcript = array_map(function ($t) {
                return [
                    'start' => \Podlove\Modules\Transcripts\Renderer::format_time($t->start),
                    'start_ms' => (int) $t->start,
                    'end' => \Podlove\Modules\Transcripts\Renderer::format_time($t->end),
                    'end_ms' => (int) $t->end,
                    'voice' => $t->voice,
                    'text' => $t->content,
                ];
            }, $transcript);

            return new \Podlove\Api\Response\OkResponse([
                'status' => 'ok',
                'transcript' => $transcript
            ]);
        }

        return new \Podlove\Api\Response\OkResponse();
    }

    public function update_item_voices($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if (!$episode) {
            return new \Podlove\Api\Error\NotFound('not_found', 'Episode not found');
        }

        $voice_assignment = null;

        if (isset($request['voice'])) {
            $voice = $request['voice'];
            $voice_assignment = VoiceAssignment::find_one_by_where(
                sprintf('`episode_id` = "%d" AND `voice` = "%s"', (int) $id, esc_sql($voice))
            );
            if (!$voice_assignment) {
                $voice_assignment = new VoiceAssignment();
                $voice_assignment->episode_id = $episode->id;
                $voice_assignment->voice = $voice;
                $voice_assignment->contributer_id = 0;
            }
        }

        $cid = 0;

        if (isset($request['contributor_id'])) {
            $cid = $request['contributor_id'];
            $contributor = Contributor::find_by_id($cid);
            if (!$contributor) {
                return new \Podlove\Api\Error\NotFound('not_found', 'Contributor is not found');
            }
        }

        $voice_assignment->contributor_id = $cid;
        $voice_assignment->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item_transcripts($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }
        $transcript = Transcript::find_by_id($id);

        if (!$transcript) {
            return new \Podlove\Api\Error\NotFound(
                'not_found',
                'transcript with id '.$id.' was not found'
            );
        }

        if (isset($request['start'])) {
            $start = $request['start'];
            $transcript->start = $start;
        }

        if (isset($request['end'])) {
            $end = $request['end'];
            $transcript->start = $end;
        }

        if (isset($request['text'])) {
            $content = $request['text'];
            $transcript->content = $content;
        }

        $transcript->save();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function delete_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }

    public function delete_item($request)
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

    public function delete_item_transcripts($request)
    {
        $id = $request->get_param('id');
        if (!$id) {
            return;
        }
        $transcript = Transcript::find_by_id($id);

        if (!$transcript) {
            return new \Podlove\Api\Error\NotFound(
                'not_found',
                'transcript with id '.$id.' was not found'
            );
        }

        $transcript->delete();

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }
}
