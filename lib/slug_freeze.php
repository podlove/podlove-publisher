<?php

namespace Podlove;

use Podlove\Model\Episode;
use Podlove\Model\MediaFile;

/**
 * Handles automatic slug freezing when media files validate successfully.
 */
class SlugFreeze
{
    public static function init()
    {
        // Hook into media file validation to automatically freeze slug
        add_action('podlove_media_file_content_verified', [__CLASS__, 'maybe_freeze_slug_on_media_validation']);
    }

    /**
     * Freeze episode slug when the first media file validates successfully.
     *
     * @param int $media_file_id
     */
    public static function maybe_freeze_slug_on_media_validation($media_file_id)
    {
        $media_file = MediaFile::find_by_id($media_file_id);

        if (!$media_file || !$media_file->is_valid()) {
            return;
        }

        $episode = Episode::find_by_id($media_file->episode_id);

        if (!$episode || $episode->is_slug_frozen()) {
            return;
        }

        // Only freeze if episode has a slug (avoid freezing empty slugs)
        if (empty(trim($episode->slug))) {
            return;
        }

        \Podlove\Log::get()->addInfo(
            'Auto-freezing slug for episode',
            ['episode_id' => $episode->id, 'slug' => $episode->slug]
        );
        $episode->freeze_slug();
    }

    /**
     * Apply slug freezing logic to existing episodes with valid media files.
     *
     * This migration corrects existing episodes by freezing their slugs if they have
     * valid media files, following the same logic as the automatic slug freeze feature.
     */
    public static function apply_slug_freeze_to_existing_episodes()
    {
        // Find all episodes that are not frozen and have non-empty slugs
        $episodes = Model\Episode::find_all_by_time();
        $frozen_count = 0;

        foreach ($episodes as $episode) {
            // Check if episode has any valid media files
            $media_files = $episode->media_files();
            $has_valid_media = false;

            foreach ($media_files as $media_file) {
                if ($media_file->is_valid()) {
                    $has_valid_media = true;

                    break;
                }
            }

            // If episode has valid media files, freeze the slug
            if ($has_valid_media) {
                $episode->freeze_slug();
                $frozen_count++;

                \Podlove\Log::get()->addInfo(
                    'Migration 162: Auto-freezing slug for existing episode',
                    ['episode_id' => $episode->id, 'slug' => $episode->slug]
                );
            }
        }

        \Podlove\Log::get()->addInfo(
            'Migration 162: Completed slug freeze migration',
            ['episodes_processed' => count($episodes), 'episodes_frozen' => $frozen_count]
        );
    }
}
