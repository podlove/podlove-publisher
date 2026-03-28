<?php

namespace Podlove\Modules\Plus;

use Podlove\Model\Podcast;
use Podlove\Modules\Onboarding\Onboarding;

class PromotionCoordinator
{
    public const STATE_OPTION = 'podlove_plus_promo_state';
    public const LEGACY_GROWTH_DISMISSED_OPTION = 'podlove_plus_growth_banner_dismissed';
    public const DEFAULT_COOLDOWN_DAYS = 30;
    public const EARLY_FILE_HOSTING_BANNER = 'early_file_hosting';
    public const GROWTH_BANNER = 'growth';

    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function should_render(string $banner): bool
    {
        return $this->winner() === $banner;
    }

    public function has_active_banner(): bool
    {
        return $this->winner() !== null;
    }

    public function dismiss(string $banner): void
    {
        update_option(self::STATE_OPTION, [
            'banner' => $banner,
            'dismissed_at' => time(),
        ]);
    }

    public function winner(): ?string
    {
        if (!$this->base_conditions_met()) {
            return null;
        }

        if ($this->growth_conditions_met()) {
            return self::GROWTH_BANNER;
        }

        if ($this->early_file_hosting_conditions_met()) {
            return self::EARLY_FILE_HOSTING_BANNER;
        }

        return null;
    }

    public function is_plus_configured(): bool
    {
        $podcast = Podcast::get();
        $token = trim((string) $this->module->get_module_option('plus_api_token'));

        return $token !== '' || $podcast->plus_enable_proxy || $podcast->plus_enable_storage;
    }

    private function base_conditions_met(): bool
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return false;
        }

        if (!$this->is_supported_admin_page()) {
            return false;
        }

        if ($this->is_plus_configured()) {
            return false;
        }

        if ($this->is_in_cooldown()) {
            return false;
        }

        if ($this->current_page() === 'publisher_plus_settings') {
            return false;
        }

        if ($this->current_page() === 'podlove_settings_onboarding_handle') {
            return false;
        }

        return true;
    }

    private function growth_conditions_met(): bool
    {
        return $this->published_episode_count() >= GrowthBanner::MIN_EPISODES;
    }

    private function early_file_hosting_conditions_met(): bool
    {
        if ($this->published_episode_count() >= 1) {
            return true;
        }

        return in_array(Onboarding::get_onboarding_type(), ['start', 'import'], true);
    }

    private function is_in_cooldown(): bool
    {
        if (get_option(self::LEGACY_GROWTH_DISMISSED_OPTION)) {
            return true;
        }

        $state = get_option(self::STATE_OPTION, []);
        $dismissed_at = (int) ($state['dismissed_at'] ?? 0);

        if ($dismissed_at < 1) {
            return false;
        }

        $cooldown_days = (int) apply_filters('podlove_plus_promo_cooldown_days', self::DEFAULT_COOLDOWN_DAYS);

        if ($cooldown_days < 1) {
            return false;
        }

        return (time() - $dismissed_at) < ($cooldown_days * DAY_IN_SECONDS);
    }

    private function published_episode_count(): int
    {
        $counts = wp_count_posts('podcast');

        if (!$counts) {
            return 0;
        }

        return (int) ($counts->publish ?? 0) + (int) ($counts->private ?? 0);
    }

    private function is_supported_admin_page(): bool
    {
        if ($this->current_admin_file() === 'edit.php' && $this->current_post_type() === 'podcast') {
            return true;
        }

        $page = $this->current_page();

        return $page !== '' && strpos($page, 'podlove_') === 0;
    }

    private function current_admin_file(): string
    {
        global $pagenow;

        if (!is_string($pagenow)) {
            return '';
        }

        return $pagenow;
    }

    private function current_page(): string
    {
        if (!isset($_GET['page'])) {
            return '';
        }

        return sanitize_key(wp_unslash($_GET['page']));
    }

    private function current_post_type(): string
    {
        if (!isset($_GET['post_type'])) {
            return '';
        }

        return sanitize_key(wp_unslash($_GET['post_type']));
    }
}
