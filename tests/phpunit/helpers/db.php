<?php

function podlove_test_truncate_seasons_table(): void
{
    if (!\Podlove\Modules\Seasons\Model\Season::table_exists()) {
        return;
    }

    global $wpdb;
    $wpdb->query('TRUNCATE TABLE '.\Podlove\Modules\Seasons\Model\Season::table_name());
}

function podlove_test_truncate_locations_table(): void
{
    if (!class_exists(\Podlove\Modules\Locations\Model\Location::class)) {
        return;
    }

    global $wpdb;
    $table = \Podlove\Modules\Locations\Model\Location::table_name();
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        return;
    }

    $wpdb->query('DELETE FROM '.$table);
}

function podlove_test_reset_podcast_episodes(): void
{
    $posts = get_posts([
        'post_type' => 'podcast',
        'post_status' => 'any',
        'numberposts' => -1,
    ]);

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }

    if (!\Podlove\Model\Episode::table_exists()) {
        return;
    }

    global $wpdb;
    $wpdb->query('TRUNCATE TABLE '.\Podlove\Model\Episode::table_name());
}
