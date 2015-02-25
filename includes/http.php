<?php

/**
 * Checks if the http status code is resolved and counts as "reachable".
 * 
 * Returns true for 2\d\d and 304.
 * 
 * @param  int $status
 * @return bool
 */
function podlove_is_resolved_and_reachable_http_status($status) {
	return $status >= 200 && $status < 300 || $status == 304;
}