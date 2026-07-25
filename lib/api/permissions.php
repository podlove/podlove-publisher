<?php

namespace Podlove\Api;

use Podlove\Model\Episode;

class Permissons
{
    public static function authorization_status_code()
    {
        $status = 401;

        if (is_user_logged_in()) {
            $status = 403;
        }

        return $status;
    }
}

class EpisodeReadAccess
{
    /**
     * Resolve an episode and validate its backing post.
     *
     * @param mixed $episode_id
     *
     * @return null|array{episode: Episode, post: \WP_Post}
     */
    public static function resolve($episode_id)
    {
        $episode = Episode::find_by_id((int) $episode_id);
        if (!$episode) {
            return null;
        }

        $post = $episode->post();
        if (!$post || $post->post_type !== 'podcast') {
            return null;
        }

        return [
            'episode' => $episode,
            'post' => $post,
        ];
    }

    public static function is_public(Episode $episode)
    {
        $post = $episode->post();

        return $post
            && $post->post_type === 'podcast'
            && $post->post_status === 'publish'
            && empty($post->post_password);
    }

    public static function can_read(Episode $episode)
    {
        $post = $episode->post();
        if (!$post || $post->post_type !== 'podcast') {
            return false;
        }

        if ($post->post_status === 'publish' && empty($post->post_password)) {
            return true;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        // WordPress' read_post meta capability does not enforce post passwords.
        if (!empty($post->post_password)) {
            return current_user_can('edit_post', $post->ID);
        }

        return current_user_can('read_post', $post->ID);
    }

    /**
     * Non-REST preview endpoints use cookie authentication without a REST nonce,
     * so unpublished data must not be made available through credentialed CORS.
     */
    public static function is_same_origin_request()
    {
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            return true;
        }

        $request_origin = wp_parse_url(wp_unslash($_SERVER['HTTP_ORIGIN']));
        $site_origin = wp_parse_url(home_url());
        if (!$request_origin || !$site_origin) {
            return false;
        }

        $request_scheme = strtolower($request_origin['scheme'] ?? '');
        $site_scheme = strtolower($site_origin['scheme'] ?? '');
        $request_host = strtolower($request_origin['host'] ?? '');
        $site_host = strtolower($site_origin['host'] ?? '');

        $default_port = static fn ($scheme) => $scheme === 'https' ? 443 : 80;
        $request_port = (int) ($request_origin['port'] ?? $default_port($request_scheme));
        $site_port = (int) ($site_origin['port'] ?? $default_port($site_scheme));

        return $request_scheme === $site_scheme
            && $request_host === $site_host
            && $request_port === $site_port;
    }

    /**
     * Check REST read access for an episode identifier.
     *
     * @param mixed $episode_id
     *
     * @return true|\WP_Error
     */
    public static function rest_check($episode_id)
    {
        $resolved = self::resolve($episode_id);
        if (!$resolved) {
            return new Error\NotFound();
        }

        return self::rest_check_episode($resolved['episode']);
    }

    /**
     * Check REST read access for an already resolved episode.
     *
     * @return true|\WP_Error
     */
    public static function rest_check_episode(Episode $episode)
    {
        if (self::can_read($episode)) {
            return true;
        }

        if (!is_user_logged_in()) {
            return new Error\NotFound();
        }

        return new \WP_Error(
            'rest_forbidden',
            esc_html__('sorry, you do not have permissions to read this episode', 'podlove-podcasting-plugin-for-wordpress'),
            ['status' => 403]
        );
    }
}
