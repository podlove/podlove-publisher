<?php

namespace Podlove;

/**
 * Get Podlove episode template object.
 *
 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
 * @param null|mixed  $id
 *
 * @return \Podlove\Template\Episode
 */
function get_episode($id = null)
{
    $post = get_post($id);

    if (!$post) {
        return null;
    }

    $episode = Model\Episode::find_one_by_property('post_id', $post->ID);

    if (!$episode) {
        return null;
    }

    return new Template\Episode($episode);
}

/**
 * Get Podlove podcast template object.
 *
 * @param int $blog_id Optional. Blog ID. Defaults to global $blog_id.
 *
 * @return \Podlove\Template\Podcast
 */
function get_podcast($blog_id = null)
{
    return new Template\Podcast(Model\Podcast::get($blog_id));
}

/**
 * Get Podlove network template object.
 *
 * Only available in WordPress Multisite environments.
 *
 * @return \Podlove\Modules\Networks\Template\Network
 */
function get_network()
{
    return new \Podlove\Modules\Networks\Template\Network();
}
