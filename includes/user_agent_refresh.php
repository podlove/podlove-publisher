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

add_action('podlove_parse_user_agents', 'podlove_refresh_user_agents');

function podlove_init_user_agent_refresh() {
	foreach (range(0, floor(UserAgent::count()/1000)*1000, 1000) as $start_id) {
		wp_schedule_single_event( time() + mt_rand(2, 10), 'podlove_parse_user_agents', [$start_id] );
	}
}

function podlove_refresh_user_agents($start_id) {

	$agents = UserAgent::find_all_by_where(sprintf("id >= %d ORDER BY id ASC LIMIT 1000", $start_id));

	foreach ($agents as $ua) {
        $ua->parse()->save();
    }
}

