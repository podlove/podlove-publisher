<?php 
use Dariuszp\CliProgressBar;

function podlove_rehash_tracking_request_ids() {
	global $wpdb;

	podlove_rehash_log("Fetching request_ids ...\n");

	$table = 'wp_5_podlove_downloadintent';
	$sql = "select distinct request_id from $table";
	$request_ids = $wpdb->get_col($sql);

	$total = count($request_ids);

	podlove_rehash_log("Found request ids: $total\n\n");

	$bar = new CliProgressBar($total);
	$bar->display();

	$counter = 0;
	foreach ($request_ids as $request_id) {
		$counter++;
		$bar->progress();

		$salt = podlove_rehash_get_random_string();
		$rehash = podlove_rehash_func($request_id, $salt);

		$prepared = $wpdb->prepare(
			"UPDATE $table SET request_id = %s WHERE request_id = %s",
			[
				$rehash,
				$request_id
			]
		);
		
		$wpdb->query($prepared);
	}
	$bar->end();
}

function podlove_rehash_log($message)
{
	print_r($message);
}

function podlove_rehash_get_random_string() {
	if (function_exists('random_bytes')) {
		return random_bytes(12);
	}
	if (function_exists('openssl_random_pseudo_bytes')) {
		return bin2hex(openssl_random_pseudo_bytes(12));
	} else {
		return dechex(mt_rand()) . dechex(mt_rand());
	}
}

function podlove_rehash_func($old_hash, $salt)
{
	if (function_exists('openssl_digest')) {
		return openssl_digest($old_hash . $salt, 'sha256');
	} else {
		return sha1($old_hash . $salt);
	}
}
