<?php
/**
 * Update all User Agents
 * 
 * Uses WP Cron system to refresh UAs as it can take a while.
 * This way, the user doesn't have to wait.
 * 
 * Usage:
 * 
 * ```php
 * podlove_init_user_agent_refresh();
 * ```
 */
use \Podlove\Model\UserAgent;
use \Podlove\Model\DownloadIntentClean;

add_action('podlove_parse_user_agents', 'podlove_refresh_user_agents');
add_action('podlove_delete_bots_from_clean_downloadintents', 'podlove_delete_bots_from_clean_downloadintents');

function podlove_init_user_agent_refresh() {
	
	foreach (range(0, floor(UserAgent::count()/500)*500, 500) as $start_id) {
		wp_schedule_single_event( time() + mt_rand(2, 30), 'podlove_parse_user_agents', [$start_id] );
	}

	// must be done after user agent refresh is finished
	wp_schedule_single_event( time() + 180, 'podlove_delete_bots_from_clean_downloadintents' );
}

function podlove_refresh_user_agents($start_id) {

	$agents = UserAgent::find_all_by_where(sprintf("id >= %d ORDER BY id ASC LIMIT 500", $start_id));

	foreach ($agents as $ua) {
        $ua->parse()->save();
    }
}

/**
 * Delete bot-entries from "clean" DownloadIntents
 * 
 * If a UserAgent is declared as bot "after" it has already been accepted as 
 * clean, it needs to be deleted.
 */
function podlove_delete_bots_from_clean_downloadintents() {
	global $wpdb;

	$sql = "DELETE FROM `" . DownloadIntentClean::table_name() . "` WHERE `user_agent_id` IN (
		SELECT id FROM `" . UserAgent::table_name() . "` ua WHERE ua.bot
	)";

	$wpdb->query($sql);
}
