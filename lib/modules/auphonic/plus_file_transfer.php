<?php

namespace Podlove\Modules\Auphonic;

class PlusFileTransfer
{
    /**
     * @var Auphonic
     */
    private $auphonic_module;

    public function __construct(Auphonic $auphonic_module)
    {
        $this->auphonic_module = $auphonic_module;
    }

    /**
     * Initiate PLUS file transfers for matching Auphonic output files.
     *
     * @param int $post_id
     */
    public function initiate_transfers($post_id)
    {
        $production = $this->get_production_data($post_id);
        if (!$production) {
            return;
        }

        $this->set_transfer_status($post_id, 'in_progress');

        $matching_files = $this->get_matching_files($production['output_files'], $post_id);
        if (empty($matching_files)) {
            $this->set_transfer_status($post_id, 'completed');

            return;
        }

        $episode = $this->get_episode($post_id);
        if (!$episode) {
            return;
        }

        $transfer_results = $this->process_file_transfers($matching_files, $episode, $post_id);
        $this->handle_transfer_results($transfer_results, $post_id);
    }

    /**
     * Generate filename based on episode and matching asset.
     *
     * When uploading or importing files, their filenames may not match the
     * expectations set by the asset system. Here we determine the filename as
     * the Publisher expects it.
     *
     * @param string                 $original_filename
     * @param \Podlove\Model\Episode $episode
     *
     * @return string
     */
    public static function generate_filename($original_filename, $episode)
    {
        $matching_asset = self::find_matching_asset_for_filename($original_filename);

        if ($matching_asset) {
            $temp_media_file = new \Podlove\Model\MediaFile();
            $temp_media_file->episode_id = $episode->id;
            $temp_media_file->episode_asset_id = $matching_asset->id;

            return $temp_media_file->get_file_name();
        }

        // Fallback
        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);

        return $episode->slug.'.'.$extension;
    }

    /**
     * Get transfer queue without executing transfers.
     *
     * @param int $post_id
     *
     * @return array
     */
    public function get_transfer_queue($post_id)
    {
        $production = $this->get_production_data($post_id);
        if (!$production) {
            return [];
        }

        $matching_files = $this->get_matching_files($production['output_files'], $post_id);
        if (empty($matching_files)) {
            return [];
        }

        $episode = $this->get_episode($post_id);
        if (!$episode) {
            return [];
        }

        $transfer_queue = [];
        foreach ($matching_files as $file) {
            $file['filename'] = self::generate_filename($file['filename'], $episode);
            $transfer_queue[] = $file;
        }

        return $transfer_queue;
    }

    /**
     * Transfer a single file.
     *
     * @param int   $post_id
     * @param array $file_data
     *
     * @return array
     */
    public function transfer_single_file($post_id, $file_data)
    {
        $plus_module = \Podlove\Modules\Plus\Plus::instance();

        return $this->transfer_file_to_plus($plus_module, $file_data, $post_id);
    }

    /**
     * Set final transfer status after frontend processing completes.
     *
     * @param int $post_id
     * @param string $status
     * @param array|null $files
     * @param string|null $errors
     */
    public function set_final_transfer_status($post_id, $status, $files = null, $errors = null)
    {
        update_post_meta($post_id, 'auphonic_plus_transfer_status', $status);

        if ($files !== null) {
            update_post_meta($post_id, 'auphonic_plus_transfer_files', $files);
        }

        if (!empty($errors)) {
            update_post_meta($post_id, 'auphonic_plus_transfer_errors', $errors);
        } else {
            delete_post_meta($post_id, 'auphonic_plus_transfer_errors');
        }

        \Podlove\Log::get()->addInfo(
            'PLUS transfer final status updated.',
            [
                'post_id' => $post_id,
                'status' => $status,
                'files_count' => is_array($files) ? count($files) : 0
            ]
        );
    }

    /**
     * Get and validate production data from Auphonic.
     *
     * @param int $post_id
     *
     * @return array|false
     */
    private function get_production_data($post_id)
    {
        $production = json_decode($this->auphonic_module->fetch_production($_POST['uuid']), true)['data'];

        if (!isset($production['output_files']) || !is_array($production['output_files'])) {
            \Podlove\Log::get()->addInfo(
                'No output files found in Auphonic production data.',
                ['post_id' => $post_id]
            );

            return false;
        }

        return $production;
    }

    /**
     * Get files that match configured asset extensions.
     *
     * @param array $output_files
     * @param int   $post_id
     *
     * @return array
     */
    private function get_matching_files($output_files, $post_id)
    {
        $configured_extensions = $this->get_configured_asset_extensions();
        $matching_files = $this->filter_output_files_by_extensions($output_files, $configured_extensions);

        if (empty($matching_files)) {
            \Podlove\Log::get()->addInfo(
                'No matching files found for configured asset extensions.',
                ['post_id' => $post_id, 'configured_extensions' => $configured_extensions]
            );
        }

        return $matching_files;
    }

    /**
     * Get episode by post ID.
     *
     * @param int $post_id
     *
     * @return false|\Podlove\Model\Episode
     */
    private function get_episode($post_id)
    {
        $episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);
        if (!$episode) {
            \Podlove\Log::get()->addError(
                'Could not find episode for post ID when generating filename.',
                ['post_id' => $post_id]
            );
            $this->set_transfer_status($post_id, 'failed', 'Episode not found');

            return false;
        }

        return $episode;
    }

    /**
     * Process file transfers for all matching files.
     *
     * @param array                  $matching_files
     * @param \Podlove\Model\Episode $episode
     * @param int                    $post_id
     *
     * @return array
     */
    private function process_file_transfers($matching_files, $episode, $post_id)
    {
        $plus_module = \Podlove\Modules\Plus\Plus::instance();
        $transfer_results = [];

        foreach ($matching_files as $file) {
            $file['filename'] = self::generate_filename($file['filename'], $episode);
            $result = $this->transfer_file_to_plus($plus_module, $file, $post_id);
            $transfer_results[] = $result;
        }

        return $transfer_results;
    }

    /**
     * Handle transfer results and set final status.
     *
     * @param array $transfer_results
     * @param int   $post_id
     */
    private function handle_transfer_results($transfer_results, $post_id)
    {
        $failed_transfers = array_filter($transfer_results, function ($result) {
            return !$result['success'];
        });

        if (empty($failed_transfers)) {
            $this->set_transfer_status($post_id, 'completed');
            \Podlove\Log::get()->addInfo(
                'All Auphonic files transferred successfully to PLUS storage.',
                ['post_id' => $post_id, 'files_count' => count($transfer_results)]
            );
        } else {
            $this->set_transfer_status($post_id, 'failed', 'Some files failed to transfer');
            \Podlove\Log::get()->addError(
                'Some Auphonic files failed to transfer to PLUS storage.',
                ['post_id' => $post_id, 'failed_count' => count($failed_transfers)]
            );
        }

        // Store transfer results for UI feedback
        update_post_meta($post_id, 'auphonic_plus_transfer_files', $transfer_results);
    }

    /**
     * Get configured asset extensions.
     *
     * @return array
     */
    private function get_configured_asset_extensions()
    {
        $episode_assets = \Podlove\Model\EpisodeAsset::all();

        $extensions = array_map(
            fn ($asset) => ($file_type = $asset->file_type()) ? $file_type->extension : null,
            $episode_assets
        );

        $filtered_extensions = array_filter(
            $extensions,
            fn ($extension) => !is_null($extension)
        );

        return array_unique($filtered_extensions);
    }

    /**
     * Filter output files to only include those matching configured extensions.
     *
     * @param array $output_files
     * @param array $configured_extensions
     *
     * @return array
     */
    private function filter_output_files_by_extensions($output_files, $configured_extensions)
    {
        return array_filter($output_files, function ($file) use ($configured_extensions) {
            $filename = $file['filename'];

            // we purposely do not use `pathinfo` here because one of our valid
            // "extensions" is "chapters.txt" and that would not match.
            return array_reduce(
                $configured_extensions,
                 fn ($carry, $extension) => $carry || str_ends_with($filename, $extension),
                false
            );
        });
    }

    /**
     * Transfer a single file to PLUS storage.
     *
     * @param \Podlove\Modules\Plus\Plus $plus_module
     * @param array                      $file_data
     * @param int                        $post_id
     *
     * @return array
     */
    private function transfer_file_to_plus($plus_module, $file_data, $post_id)
    {
        try {
            $filename = $file_data['filename'];
            $download_url = $file_data['download_url'];

            $api_key = $this->auphonic_module->get_module_option('auphonic_api_key');
            $download_url = $download_url.'?bearer_token='.$api_key;

            $result = $plus_module->get_api()->migrate_auphonic_file($download_url, $filename);

            if ($result) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'download_url' => $download_url,
                    'message' => 'File transferred successfully'
                ];
            }

            return [
                'success' => false,
                'filename' => $filename,
                'download_url' => $download_url,
                'message' => 'File transfer failed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'filename' => $file_data['filename'] ?? 'unknown',
                'download_url' => $file_data['download_url'] ?? 'unknown',
                'message' => 'Transfer failed: '.$e->getMessage()
            ];
        }
    }

    private static function find_matching_asset_for_filename($original_filename)
    {
        $episode_assets = \Podlove\Model\EpisodeAsset::all();

        // Find all assets whose extension matches the end of the original filename
        $matching_assets = array_filter($episode_assets, function ($asset) use ($original_filename) {
            $file_type = $asset->file_type();

            return $file_type && str_ends_with($original_filename, $file_type->extension);
        });

        if (empty($matching_assets)) {
            return null;
        }

        if (count($matching_assets) === 1) {
            return reset($matching_assets);
        }

        // If multiple matches, prefer the one with matching suffix
        // First, try to find assets with suffixes that match the filename pattern
        $assets_with_matching_suffix = array_filter($matching_assets, function ($asset) use ($original_filename) {
            if (is_null($asset->suffix) || $asset->suffix === '' || strlen($asset->suffix) === 0) {
                return false;
            }

            $suffix = $asset->suffix;
            $extension = $asset->file_type()->extension;

            return str_ends_with($original_filename, $suffix.'.'.$extension);
        });

        if (!empty($assets_with_matching_suffix)) {
            return reset($assets_with_matching_suffix);
        }

        // If no suffix match, return the first asset without suffix
        $assets_without_suffix = array_filter($matching_assets, function ($asset) {
            return is_null($asset->suffix) || $asset->suffix === '' || strlen($asset->suffix) === 0;
        });

        if (!empty($assets_without_suffix)) {
            return reset($assets_without_suffix);
        }

        // Fallback to first match
        return reset($matching_assets);
    }

    /**
     * Set transfer status for an episode.
     *
     * @param int    $post_id
     * @param string $status        waiting_for_webhook|in_progress|completed|failed
     * @param string $error_message
     */
    private function set_transfer_status($post_id, $status, $error_message = '')
    {
        update_post_meta($post_id, 'auphonic_plus_transfer_status', $status);

        if ($error_message) {
            update_post_meta($post_id, 'auphonic_plus_transfer_errors', $error_message);
        } else {
            delete_post_meta($post_id, 'auphonic_plus_transfer_errors');
        }
    }
}
