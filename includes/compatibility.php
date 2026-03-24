<?php

/**
 * Detect plugin updates and flush rewrite rules.
 *
 * There is a known issue where upgrading YOAST SEO breaks permalinks. This
 * function detects the update and schedules a flush of rewrite rules. We flush
 * immediately on update and schedule a one-time flush for auto-updates.
 *
 * @param object $upgrader_object the upgrader object
 * @param array  $options         the options array
 */
function podlove_detect_plugin_updates($upgrader_object, $options)
{
    if ($options['action'] != 'update' || $options['type'] != 'plugin') {
        return;
    }

    foreach ($options['plugins'] as $plugin) {
        if (strpos($plugin, 'wordpress-seo') !== false) {
            set_transient('podlove_needs_to_flush_rewrite_rules', true);

            if (!wp_next_scheduled('podlove_flush_rewrite_rules')) {
                wp_schedule_single_event(time() + 3, 'podlove_flush_rewrite_rules');
            }

            break;
        }
    }
}

function podlove_do_flush_rewrite_rules()
{
    flush_rewrite_rules();
}

add_action('upgrader_process_complete', 'podlove_detect_plugin_updates', 10, 2);
add_action('podlove_flush_rewrite_rules', 'podlove_do_flush_rewrite_rules');
