<?php

namespace Podlove\Modules\AssemblyAI;

use Podlove\Http;
use Podlove\Model\Episode;
use Podlove\Model\EpisodeAsset;
use Podlove\Modules\Transcripts\Transcripts;

class REST_API
{
    public const API_NAMESPACE = 'podlove/v2';
    public const API_BASE = 'assemblyai';
    public const ASSEMBLYAI_BASE_URL = 'https://api.assemblyai.com/v2';

    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function register_routes()
    {
        register_rest_route(self::API_NAMESPACE, self::API_BASE.'/config', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_config'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route(self::API_NAMESPACE, self::API_BASE.'/transcribe/(?P<post_id>[0-9]+)', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'start_transcription'],
                'permission_callback' => [$this, 'permission_check_post'],
                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        register_rest_route(self::API_NAMESPACE, self::API_BASE.'/status/(?P<post_id>[0-9]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_status'],
                'permission_callback' => [$this, 'permission_check_post'],
                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        register_rest_route(self::API_NAMESPACE, self::API_BASE.'/import/(?P<post_id>[0-9]+)', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'import_transcript'],
                'permission_callback' => [$this, 'permission_check_post'],
                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);
    }

    public function permission_check()
    {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error(
                'rest_forbidden',
                'Sorry, you are not allowed to do that.',
                ['status' => 403]
            );
        }

        return true;
    }

    public function permission_check_post(\WP_REST_Request $request)
    {
        $post_id = (int) $request->get_param('post_id');

        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error(
                'rest_forbidden',
                'Sorry, you are not allowed to edit this post.',
                ['status' => 403]
            );
        }

        return true;
    }

    public function get_config()
    {
        $api_key = $this->module->get_module_option('assemblyai_api_key', '');

        return new \WP_REST_Response([
            'has_api_key' => !empty($api_key),
        ]);
    }

    public function start_transcription(\WP_REST_Request $request)
    {
        $post_id = (int) $request->get_param('post_id');
        $api_key = $this->module->get_module_option('assemblyai_api_key', '');

        if (empty($api_key)) {
            return new \WP_REST_Response(['error' => 'API key not configured'], 400);
        }

        $episode = Episode::find_or_create_by_post_id($post_id);
        if (!$episode) {
            return new \WP_REST_Response(['error' => 'Episode not found'], 404);
        }

        $audio_url = $this->get_audio_url($episode);
        if (!$audio_url) {
            return new \WP_REST_Response(['error' => 'No active audio file found for this episode'], 400);
        }

        $payload = [
            'audio_url' => $audio_url,
            'speech_model' => 'best',
            'speaker_labels' => true,
            'language_detection' => true,
        ];

        // Add speaker count hint from Contributors module
        $speakers_expected = $this->get_speakers_expected($episode);
        if ($speakers_expected > 0) {
            $payload['speakers_expected'] = $speakers_expected;
        }

        $curl = new Http\Curl();
        $curl->request(self::ASSEMBLYAI_BASE_URL.'/transcript', [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $api_key,
            ],
            'body' => json_encode($payload),
        ]);

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {
            $error = 'Failed to submit transcription to AssemblyAI';
            if (!is_wp_error($response) && isset($response['body'])) {
                $body = json_decode($response['body'], true);
                if (isset($body['error'])) {
                    $error = $body['error'];
                }
            }

            return new \WP_REST_Response(['error' => $error], 500);
        }

        $body = json_decode($response['body'], true);

        if (!is_array($body) || !isset($body['id'], $body['status'])) {
            return new \WP_REST_Response(['error' => 'Unexpected response from AssemblyAI'], 500);
        }

        $transcript_id = sanitize_text_field($body['id']);

        update_post_meta($post_id, 'assemblyai_transcript_id', $transcript_id);
        update_post_meta($post_id, 'assemblyai_status', $body['status']);

        return new \WP_REST_Response([
            'transcript_id' => $transcript_id,
            'status' => $body['status'],
        ]);
    }

    public function get_status(\WP_REST_Request $request)
    {
        $post_id = (int) $request->get_param('post_id');
        $api_key = $this->module->get_module_option('assemblyai_api_key', '');

        if (empty($api_key)) {
            return new \WP_REST_Response(['error' => 'API key not configured'], 400);
        }

        $transcript_id = $this->get_valid_transcript_id($post_id);
        if (!$transcript_id) {
            return new \WP_REST_Response(['error' => 'No transcription found for this episode'], 404);
        }

        $curl = new Http\Curl();
        $curl->request(self::ASSEMBLYAI_BASE_URL.'/transcript/'.$transcript_id, [
            'headers' => [
                'Authorization' => $api_key,
            ],
        ]);

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {
            return new \WP_REST_Response(['error' => 'Failed to fetch transcription status'], 500);
        }

        $body = json_decode($response['body'], true);

        if (!is_array($body) || !isset($body['status'])) {
            return new \WP_REST_Response(['error' => 'Unexpected response from AssemblyAI'], 500);
        }

        $status = $body['status'];

        update_post_meta($post_id, 'assemblyai_status', $status);

        $result = ['status' => $status];

        if ($status === 'error' && isset($body['error'])) {
            $result['error'] = $body['error'];
        }

        return new \WP_REST_Response($result);
    }

    public function import_transcript(\WP_REST_Request $request)
    {
        $post_id = (int) $request->get_param('post_id');
        $api_key = $this->module->get_module_option('assemblyai_api_key', '');

        if (empty($api_key)) {
            return new \WP_REST_Response(['error' => 'API key not configured'], 400);
        }

        $transcript_id = $this->get_valid_transcript_id($post_id);
        if (!$transcript_id) {
            return new \WP_REST_Response(['error' => 'No transcription found for this episode'], 404);
        }

        $episode = Episode::find_or_create_by_post_id($post_id);
        if (!$episode) {
            return new \WP_REST_Response(['error' => 'Episode not found'], 404);
        }

        // Fetch full transcript from AssemblyAI
        $curl = new Http\Curl();
        $curl->request(self::ASSEMBLYAI_BASE_URL.'/transcript/'.$transcript_id, [
            'headers' => [
                'Authorization' => $api_key,
            ],
        ]);

        $response = $curl->get_response();

        if (!$curl->isSuccessful()) {
            return new \WP_REST_Response(['error' => 'Failed to fetch transcript from AssemblyAI'], 500);
        }

        $body = json_decode($response['body'], true);

        if (!is_array($body) || !isset($body['status'])) {
            return new \WP_REST_Response(['error' => 'Unexpected response from AssemblyAI'], 500);
        }

        if ($body['status'] !== 'completed') {
            return new \WP_REST_Response(['error' => 'Transcript is not yet completed'], 400);
        }

        // Convert to WebVTT
        $vtt_content = VttConverter::convert($body);

        // Import via existing Transcripts module
        Transcripts::parse_and_import_webvtt($episode, $vtt_content);

        // Update status
        update_post_meta($post_id, 'assemblyai_status', 'imported');

        return new \WP_REST_Response(['success' => true]);
    }

    /**
     * Get and validate transcript ID from post meta.
     *
     * @param int $post_id
     *
     * @return string|null valid transcript ID or null
     */
    private function get_valid_transcript_id($post_id)
    {
        $transcript_id = get_post_meta($post_id, 'assemblyai_transcript_id', true);

        if (empty($transcript_id)) {
            return null;
        }

        // AssemblyAI IDs are alphanumeric with hyphens
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $transcript_id)) {
            return null;
        }

        return $transcript_id;
    }

    /**
     * Find the first active audio media file URL for an episode.
     *
     * @param mixed $episode
     */
    private function get_audio_url($episode)
    {
        $media_files = $episode->media_files();

        foreach ($media_files as $file) {
            if (!$file->active || $file->size <= 0) {
                continue;
            }

            $asset = EpisodeAsset::find_by_id($file->episode_asset_id);
            if (!$asset) {
                continue;
            }

            $file_type = $asset->file_type();
            if ($file_type && $file_type->type === 'audio') {
                return $file->get_file_url();
            }
        }

        return null;
    }

    /**
     * Get expected speaker count from Contributors module.
     *
     * @param mixed $episode
     */
    private function get_speakers_expected($episode)
    {
        if (!\Podlove\Modules\Base::is_active('contributors')) {
            return 0;
        }

        $contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id);

        if (empty($contributions)) {
            return 0;
        }

        return count($contributions);
    }
}
