<?php

namespace Podlove\Admin;

/**
 * Captures the current admin screen state needed to resolve and configure asset bundles.
 */
class AssetContext
{
    private string $screenBase;
    private bool $isEpisodeEditScreen;
    private bool $isPodloveSettingsScreen;
    private int $postId;
    private string $version;
    private bool $episodeLoaded = false;
    private ?\Podlove\Model\Episode $episode = null;

    public function __construct(
        string $screenBase,
        bool $isEpisodeEditScreen,
        bool $isPodloveSettingsScreen,
        int $postId,
        string $version
    ) {
        $this->screenBase = $screenBase;
        $this->isEpisodeEditScreen = $isEpisodeEditScreen;
        $this->isPodloveSettingsScreen = $isPodloveSettingsScreen;
        $this->postId = $postId;
        $this->version = $version;
    }

    public static function fromCurrentScreen(): self
    {
        $screen = get_current_screen();

        return new self(
            $screen ? $screen->base : '',
            \Podlove\is_episode_edit_screen(),
            \Podlove\is_podlove_settings_screen(),
            (int) get_the_ID(),
            \Podlove\get_plugin_header('Version')
        );
    }

    public function screenBase(): string
    {
        return $this->screenBase;
    }

    public function isEpisodeEditScreen(): bool
    {
        return $this->isEpisodeEditScreen;
    }

    public function isPodloveSettingsScreen(): bool
    {
        return $this->isPodloveSettingsScreen;
    }

    public function postId(): int
    {
        return $this->postId;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function matchesScreenBases(array $screenBases): bool
    {
        return in_array($this->screenBase, $screenBases, true);
    }

    public function episode(): \Podlove\Model\Episode
    {
        if (!$this->episodeLoaded) {
            $this->episode = \Podlove\Model\Episode::find_or_create_by_post_id($this->postId);
            $this->episodeLoaded = true;
        }

        return $this->episode;
    }
}
