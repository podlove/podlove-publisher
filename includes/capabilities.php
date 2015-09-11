<?php
/**
 * Capabilities
 * 
 * - podlove_read_analytics: can view analytics
 * - podlove_read_dashboard: can view analytics
 */

/**
 * Initialize Capabilities.
 */
function podlove_init_capabilities() {
	podlove_add_capability_to_roles('podlove_read_analytics', ['administrator', 'editor', 'author']);
	podlove_add_capability_to_roles('podlove_read_dashboard', ['administrator', 'editor', 'author']);
}

/**
 * Add capability to a list of roles.
 * 
 * @param  string $capability WordPress capability.
 * @param  array  $roles      List of roles.
 */
function podlove_add_capability_to_roles($capability, $roles = []) {
	foreach ($roles as $role) {
		get_role($role)->add_cap($capability);
	}
}
