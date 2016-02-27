<?php

add_filter( 'template_redirect', 'podlove_handle_user_redirects' );
add_filter( 'template_redirect', 'podlove_handle_episode_redirects' );

/**
 * Handle configured redirects
 */
function podlove_handle_user_redirects() {
	global $wpdb, $wp_query;

	if ( is_admin() )
		return;

	// check for global redirects
	$parsed_request = parse_url($_SERVER['REQUEST_URI']);
	$parsed_request_url = $parsed_request['path'];
	if ( isset( $parsed_request['query'] ) )
		$parsed_request_url .= "?" . $parsed_request['query'];

	$redirects = \Podlove\get_setting( 'redirects', 'podlove_setting_redirect' );

	if (!is_array($redirects))
		return;

	foreach ( $redirects as $index => $redirect ) {

		if ( ! isset( $redirect['active'] ) )
			continue;

		if ( ! strlen( trim( $redirect['from'] ) ) || ! strlen( trim( $redirect['to'] ) ) )
			continue;

		$parsed_url = parse_url($redirect['from']);
		$parsed_redirect_url = $parsed_url['path'];

		if ( isset( $parsed_url['query'] ) )
			$parsed_redirect_url .= "?" . $parsed_url['query'];

		if ( untrailingslashit( $parsed_redirect_url ) === untrailingslashit( $parsed_request_url ) ) {
			
			if ($redirect['code']) {
				$http_code = (int) $redirect['code'];
			} else {
				$http_code = 301; // default to permanent
			}

			// fallback for HTTP/1.0 clients
			if ($http_code == 307 && $_SERVER['SERVER_PROTOCOL'] == "HTTP/1.0") {
				$http_code = 302;
			}

			// increment redirection counter
			$redirects[$index]['count'] += 1;
			\Podlove\save_setting( 'redirects', 'podlove_setting_redirect', $redirects );

			// redirect
			status_header( $http_code );
			$wp_query->is_404 = false;
			wp_redirect( $redirect['to'], $http_code );
			exit;
		}
	}
}

/**
 * Simple method to allow support for multiple urls per post.
 *
 * Add custom post meta 'podlove_alternate_url' with old url part to match.
 */
function podlove_handle_episode_redirects($value='') {
	global $wpdb, $wp_query;

	if ( is_admin() )
		return;

	if ( ! $wp_query->is_404 )
		return;

	// check for episode redirects
	$rows = $wpdb->get_results( "
		SELECT
			post_id, meta_value url
		FROM
			" . $wpdb->postmeta . "
		WHERE
			meta_key = 'podlove_alternate_url'
	", ARRAY_A );

	$request_uri = untrailingslashit( $_SERVER['REQUEST_URI'] );
	foreach ( $rows as $row ) {
		if ( false !== stripos( $row['url'], $request_uri ) ) {
			status_header( 301 );
			$wp_query->is_404 = false;
			wp_redirect( get_permalink( $row['post_id'] ), 301 );
			exit;
		}
	}
}

