<?php

/**
 * Capabilities.
 *
 * - podlove_read_analytics: can view analytics
 * - podlove_read_dashboard: can view analytics
 * - podlove_manage_contributors: can manage contributors
 * - podlove_use_auphonic: can use the shared Auphonic account
 */

/**
 * Initialize Capabilities.
 */
function podlove_init_capabilities()
{
    podlove_add_capability_to_roles('podlove_read_analytics', ['administrator', 'editor', 'author']);
    podlove_add_capability_to_roles('podlove_read_dashboard', ['administrator', 'editor', 'author']);

    podlove_add_capability_to_roles('podlove_manage_contributors', ['administrator']);
    podlove_add_capability_to_roles('podlove_use_auphonic', ['administrator', 'editor']);
}

/**
 * Add capability to a list of roles.
 *
 * @param string $capability wordPress capability
 * @param array  $roles      list of roles
 */
function podlove_add_capability_to_roles($capability, $roles = [])
{
    foreach ($roles as $role_name) {
        if ($role = get_role($role_name)) {
            $role->add_cap($capability);
        }
    }
}
