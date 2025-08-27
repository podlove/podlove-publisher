<?php

namespace Podlove\Modules\Plus;

use Podlove\Http;
use Podlove\Model\Podcast;

class API
{
    private $module;
    private $token;

    public function __construct($module, $token)
    {
        $this->module = $module;
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function get_me()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/me', $this->params());

        return $this->handle_json_response($curl);
    }

    public function get_account_id()
    {
        $cache = \Podlove\Cache\TemplateCache::get_instance();

        return $cache->cache_for('plus_account_id', function () {
            $user = $this->get_me();

            if (!$user) {
                return;
            }

            return $user->account_id;
        }, 60);
    }

    public function list_feeds()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/feeds', $this->params());

        return $this->handle_json_response($curl);
    }

    public function push_feeds($feeds)
    {
        $payload = wp_json_encode(['feeds' => $feeds]);

        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/feeds', $this->params([
            'method' => 'POST',
            'body' => $payload,
        ]));

        do_action('podlove_plus_api_push_feeds');

        return $curl->get_response();
    }

    public function get_proxy_url($origin_url)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/feeds/proxy_url?url='.urlencode($origin_url), $this->params());

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->url ?? false;
        }

        return false;
    }

    public function create_image_preset($template_name, $modifications = [])
    {
        $payload = wp_json_encode(['template' => $template_name, 'modifications' => $modifications]);

        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/image/preset', $this->params([
            'method' => 'POST',
            'body' => $payload,
        ]));

        do_action('podlove_plus_api_create_image_preset');

        return $curl->get_response();
    }

    public function create_file_upload($filename)
    {
        $filename = $this->sanitize_filename($filename);

        $query = http_build_query([
            'filename' => $filename,
            'podcast_guid' => (string) Podcast::get()->guid
        ]);

        $curl = new Http\Curl();
        $curl->request(
            $this->module::base_url().'/api/rest/v1/files/upload/new?'.$query,
            $this->params(['method' => 'POST'])
        );

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->url ?? false;
        }

        return false;
    }

    public function check_file_exists($filename)
    {
        $filename = $this->sanitize_filename($filename);

        $query = http_build_query([
            'filename' => $filename,
            'podcast_guid' => (string) Podcast::get()->guid
        ]);

        $curl = new Http\Curl();
        $curl->request(
            $this->module::base_url().'/api/rest/v1/files/upload/exists?'.$query,
            $this->params(['method' => 'GET'])
        );

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->exists ?? false;
        }

        return false;
    }

    public function complete_file_upload($filename)
    {
        $filename = $this->sanitize_filename($filename);

        $query = http_build_query([
            'filename' => $filename,
            'podcast_guid' => (string) Podcast::get()->guid
        ]);

        $curl = new Http\Curl();
        $curl->request(
            $this->module::base_url().'/api/rest/v1/files/upload/complete?'.$query,
            $this->params(['method' => 'POST'])
        );

        $response = $this->handle_json_response($curl);
        if ($response) {
            return $response->file ?? false;
        }

        return false;
    }

    public function migrate_file($filename, $file_url, $prevent_double_uploads = true)
    {
        $filename = $this->sanitize_filename($filename);

        // prevent double uploads
        if ($prevent_double_uploads && $this->check_file_exists($filename)) {
            return true;
        }

        $presigned_upload_url = $this->create_file_upload($filename);

        if (!$presigned_upload_url) {
            return false;
        }

        if (!$this->do_upload($presigned_upload_url, $file_url, $filename)) {
            return false;
        }

        return $this->complete_file_upload($filename);
    }

    /**
     * Migrate an Auphonic file to PLUS storage.
     *
     * This method is a wrapper around migrate_file specifically for Auphonic files.
     * It provides additional error handling and logging for the Auphonic integration.
     *
     * @param string $auphonic_url The download URL from Auphonic
     * @param string $filename     The filename to use in PLUS storage
     *
     * @return bool True on success, false on failure
     */
    public function migrate_auphonic_file($auphonic_url, $filename)
    {
        $filename = $this->sanitize_filename($filename);

        \Podlove\Log::get()->addInfo(
            'Starting Auphonic file migration to PLUS storage.',
            ['filename' => $filename, 'source_url' => $auphonic_url]
        );

        try {
            $result = $this->migrate_file($filename, $auphonic_url, false);

            if ($result) {
                \Podlove\Log::get()->addInfo(
                    'Auphonic file migration to PLUS storage successful.',
                    ['filename' => $filename]
                );
            } else {
                \Podlove\Log::get()->addError(
                    'Auphonic file migration to PLUS storage failed.',
                    ['filename' => $filename, 'source_url' => $auphonic_url]
                );
            }

            return $result;
        } catch (\Exception $e) {
            \Podlove\Log::get()->addError(
                'Auphonic file migration to PLUS storage failed with exception.',
                ['filename' => $filename, 'source_url' => $auphonic_url, 'error' => $e->getMessage()]
            );

            return false;
        }
    }

    /**
     * List all podcasts for the connected account in PLUS.
     */
    public function list_podcasts()
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts', $this->params());

        return $this->handle_json_response($curl) ?? [];
    }

    /**
     * Update podcast title in PLUS.
     *
     * This function will create a podcast if it doesn't exist yet.
     */
    public function upsert_podcast_title(string $guid, string $title)
    {
        $podcast = $this->get_podcast_by_guid($guid);

        if ($podcast) {
            return $this->update_podcast($podcast->id, ['title' => $title]);
        }

        return $this->create_podcast($guid, ['title' => $title]);
    }

    /**
     * Get PLUS podcast by guid.
     */
    public function get_podcast_by_guid(string $guid)
    {
        $podcasts = $this->list_podcasts();

        $matching_podcast = array_filter($podcasts, function ($podcast) use ($guid) {
            return $podcast->guid === $guid;
        });

        return array_values($matching_podcast)[0] ?? false;
    }

    /**
     * Get PLUS podcast by id.
     */
    public function get_podcast(int $podcast_id)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts/'.$podcast_id, $this->params());

        return $this->handle_json_response($curl);
    }

    public function update_podcast(int $podcast_id, array $data)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts/'.$podcast_id, $this->params([
            'method' => 'PUT',
            'body' => wp_json_encode(['podcast' => $data]),
        ]));

        return $this->handle_json_response($curl);
    }

    /**
     * Create a podcast in PLUS.
     *
     * Currently only supports the required fields: `guid` and `title`.
     */
    public function create_podcast(string $guid, array $data)
    {
        $curl = new Http\Curl();
        $curl->request($this->module::base_url().'/api/rest/v1/podcasts', $this->params([
            'method' => 'POST',
            'body' => wp_json_encode(['podcast' => ['guid' => $guid, 'title' => $data['title']]]),
        ]));

        return $this->handle_json_response($curl);
    }

    /**
     * Sets a flag to indicate that the file migration has been completed.
     *
     * @return array Response with success status
     */
    public function set_migration_complete()
    {
        update_option('podlove_plus_migration_completed', true);

        return ['success' => true];
    }

    /**
     * Checks if the migration has been completed.
     *
     * @return bool True if migration has been completed, false otherwise
     */
    public function is_migration_complete()
    {
        return (bool) get_option('podlove_plus_migration_completed');
    }

    private function do_upload($target_url, $origin_url, $filename)
    {
        $filename = $this->sanitize_filename($filename);
        $temp_file = \get_temp_dir().$filename;

        try {
            // Download to temporary file with streaming
            $response = wp_remote_get($origin_url, [
                'timeout' => 300,
                'stream' => true,
                'filename' => $temp_file
            ]);

            if (is_wp_error($response)) {
                error_log('Download failed: '.$response->get_error_message());
                @unlink($temp_file);

                return false;
            }

            // Get file size and content type
            $file_size = filesize($temp_file);
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            if (empty($content_type)) {
                $content_type = 'application/octet-stream';
            }

            // Open the temporary file
            $file_handle = fopen($temp_file, 'r');
            if (!$file_handle) {
                error_log("Cannot open temporary file for reading: {$temp_file}");
                @unlink($temp_file);

                return false;
            }

            // Initialize cURL for upload
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $target_url);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_INFILE, $file_handle);
            curl_setopt($ch, CURLOPT_INFILESIZE, $file_size);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: '.$content_type,
                'Content-Length: '.$file_size
            ]);

            // Execute upload
            $upload_response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);

            curl_close($ch);
            fclose($file_handle);
            @unlink($temp_file);

            if ($curl_error) {
                error_log("cURL error during upload: {$curl_error}");
            }

            if ($http_code < 200 || $http_code >= 300) {
                error_log("Upload failed with HTTP code {$http_code}: {$upload_response}");

                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log('Exception during file migration: '.$e->getMessage());

            // Cleanup resources
            if (isset($file_handle) && is_resource($file_handle)) {
                fclose($file_handle);
            }
            if (isset($ch) && is_resource($ch)) {
                curl_close($ch);
            }
            @unlink($temp_file);

            return false;
        }
    }

    /**
     * Handles common JSON response processing.
     *
     * @param Http\Curl $curl The curl object with the executed request
     *
     * @return mixed Decoded JSON object or false on failure
     */
    private function handle_json_response($curl)
    {
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            return json_decode($response['body']) ?? false;
        }

        return false;
    }

    private function params($params = [])
    {
        return array_merge([
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->token,
            ],
        ], $params);
    }

    /**
     * Sanitizes filenames by replacing slashes with dashes to prevent path traversal issues.
     *
     * @param string $filename The filename to sanitize
     *
     * @return string The sanitized filename
     */
    private function sanitize_filename($filename)
    {
        return str_replace('/', '-', $filename);
    }
}
