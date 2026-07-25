<?php

namespace Podlove\Api;

use Podlove\Model\Episode;

class EpisodeMutationAccess
{
    /**
     * Resolve an episode identifier and validate its backing post.
     *
     * @param mixed $episode_id
     *
     * @return null|array{episode: Episode, post: \WP_Post}
     */
    public static function resolve($episode_id)
    {
        return EpisodeReadAccess::resolve($episode_id);
    }

    /**
     * Resolve a WordPress post identifier to a Publisher episode.
     *
     * @param mixed $post_id
     *
     * @return null|array{episode: Episode, post: \WP_Post}
     */
    public static function resolve_by_post_id($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return null;
        }

        $episode = Episode::find_one_by_post_id($post_id);
        if (!$episode || (int) $episode->post_id !== $post_id) {
            return null;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'podcast') {
            return null;
        }

        return [
            'episode' => $episode,
            'post' => $post,
        ];
    }

    /**
     * Check REST edit access for an episode identifier.
     *
     * @param mixed $episode_id
     *
     * @return true|\WP_Error
     */
    public static function rest_check_edit($episode_id)
    {
        return self::rest_check(self::resolve($episode_id), 'edit_post');
    }

    /**
     * Check REST edit access for a WordPress post identifier.
     *
     * @param mixed $post_id
     *
     * @return true|\WP_Error
     */
    public static function rest_check_edit_by_post_id($post_id)
    {
        return self::rest_check(self::resolve_by_post_id($post_id), 'edit_post');
    }

    /**
     * Check REST delete access for an episode identifier.
     *
     * @param mixed $episode_id
     *
     * @return true|\WP_Error
     */
    public static function rest_check_delete($episode_id)
    {
        return self::rest_check(self::resolve($episode_id), 'delete_post');
    }

    /**
     * @param null|array{episode: Episode, post: \WP_Post} $resolved
     * @param string                                       $capability
     *
     * @return true|\WP_Error
     */
    private static function rest_check($resolved, $capability)
    {
        if (!$resolved) {
            return new Error\NotFound();
        }

        if (!current_user_can($capability, $resolved['post']->ID)) {
            return new Error\ForbiddenAccess();
        }

        return true;
    }
}
