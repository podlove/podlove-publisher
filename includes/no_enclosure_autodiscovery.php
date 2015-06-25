<?php
/**
 * Don't autodiscover enclosures in posts.
 * 
 * WordPress tries to find enclosures in posts. It happens for all posts with a
 * meta entry "_encloseme". Solution: Delete that entry once it's created.
 * 
 * @param int $meta_id
 * @param int $post_id
 * @param string $meta_key
 * @param mixed $meta_value
 */
function podlove_no_enclosure_autodiscovery($meta_id, $post_id, $meta_key, $meta_value) {
	global $wpdb;
	
	if ($meta_key != '_encloseme')
		return;
	
	$sql = "
		DELETE FROM
			$wpdb->postmeta 
		WHERE
			post_id = '$post_id'
			AND meta_key = '_encloseme'
			AND meta_id = '$meta_id'
		";

	$wpdb->query($sql);
}
add_action('added_post_meta', 'podlove_no_enclosure_autodiscovery', 10, 4);
// legacy support
add_action('added_postmeta', 'podlove_no_enclosure_autodiscovery', 10, 4);
