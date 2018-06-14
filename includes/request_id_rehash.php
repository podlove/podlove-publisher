<?php 

add_action('admin_init', 'podlove_rehash_init_tools_section', 20);
add_action('admin_init', 'podlove_rehash_process_actions');

function podlove_rehash_init_tools_section()
{
	\Podlove\add_tools_section('dsgvo', __('DSGVO', 'podlove-podcasting-plugin-for-wordpress'));

	\Podlove\add_tools_field('dsgvo-rehash-request_ids', __('Rehash Request IDs', 'podlove-podcasting-plugin-for-wordpress'), function() {
		?>
		<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=podlove_rehash_request_ids') ?>" class="button">
			<?php echo __('Rehash Request IDs', 'podlove-podcasting-plugin-for-wordpress') ?>
		</a>
		<p class="description">
			<?php echo __('Podlove Publisher tracking uses "request ids", which are hashes of request IP and User Agent. For better anonymity, IPs are truncated before they get hashed starting Podlove Publisher Version 2.7.4. To guarantee the anonymity of existing tracking data, request_ids must be rehashed here with a random salt.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<p class="description">
			<?php echo __('Depending on your system and how much tracking data you have gathered, this might take a few hours. If you have hundreds of thousands of tracked downloads or more, you can speed up the process by doing the conversion over command line. See DSVGO guide for details.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<?php
	}, 'dsgvo');	
}

function podlove_rehash_process_actions()
{
	if (filter_input(INPUT_GET, 'page') != 'podlove_tools_settings_handle')
		return;

	if (filter_input(INPUT_GET, 'action') != 'podlove_rehash_request_ids')
		return;

	\Podlove\Jobs\CronJobRunner::create_job('\Podlove\Jobs\RequestIdRehashJob');

	wp_redirect(admin_url('admin.php?page=' . $_REQUEST['page']));
}

class PodloveSilentProgressBar
{
	public function __construct($x = null) { }
	public function display() { }
	public function progress() { }
	public function end() { }
}

/**
 * Rehash Tracking request_ids
 * 
 * This function is intended to be used by command line interface.
 * For use within Publisher, job class RequestIdRehashJob is preferred.
 * 
 * Example
 * 
 *     wp eval 'podlove_rehash_tracking_request_ids();'
 * 
 * @param  int $blog_id optional blog id for multisite
 */
function podlove_rehash_tracking_request_ids($blog_id = null)
{
	if ($blog_id) {
		
		if (!function_exists('switch_to_blog')) {
			die("You set a blog_id but this does not appear to be a multisite setup.\n");
		}

		switch_to_blog($blog_id);
	}

	$table = \Podlove\Model\DownloadIntent::table_name();

	podlove_rehash_log("Fetching request_ids from $table ...\n");

	$request_ids = podlove_rehash_fetch_some_request_ids($table);

	$total = count($request_ids);

	if (!$total) {
		podlove_rehash_log("Nothing to do.\n");
		return;
	}

	podlove_rehash_log("Found request ids: $total\n\n");

	$progress_class = podlove_rehash_progress_class();
	$bar = new $progress_class($total);
	$bar->display();

	$counter = 0;
	foreach ($request_ids as $request_id) {
		$counter++;
		$bar->progress();

		podlove_rehash_replace_request_id($table, $request_id);
	}
	$bar->end();

	if ($blog_id) {
		restore_current_blog();
	}	
}

/**
 * Rehashes a request id.
 * 
 * Until v2.7.3 it was possible with reasonable effort to brute force the IP
 * address from a request_id. To anonymize them, the following is done:
 * 
 * - each unique request id is rehashed with a random salt
 * - rehashed request IDs are prefixed with "DSGVO" to mark them a "ok"
 */
function podlove_rehash_replace_request_id($table, $request_id)
{
	global $wpdb;

	$salt   = podlove_rehash_get_random_string();
	$rehash = podlove_rehash_func($request_id, $salt);

	$prepared = $wpdb->prepare(
		"UPDATE $table SET request_id = %s WHERE request_id = %s AND accessed_at < \"%s\"",
		[
			$rehash,
			$request_id,
			podlove_rehash_unsalted_time()
		]
	);
	
	$wpdb->query($prepared);
}

function podlove_rehash_fetch_some_request_ids($table, $limit = null)
{
	global $wpdb;

	if ($limit) {
		$limit_component = "LIMIT " . (int) $limit;
	} else {
		$limit_component = "";
	}

	$sql = sprintf(
		'SELECT DISTINCT request_id 
		FROM `%s` 
		WHERE 
		  request_id NOT LIKE "%s" 
		  AND accessed_at < "%s"
		%s', 
		$table, 
		podlove_rehash_prefix() . "%",
		podlove_rehash_unsalted_time(),
		$limit_component
	);

	return $wpdb->get_col($sql);	
}

/**
 * Returns upper time point for salting download intents.
 * 
 * @return string DateTime in mysql format
 */
function podlove_rehash_unsalted_time() {
	$duration = strtotime("-1 day");
	$duration = apply_filters("podlove_rehash_unsalted_duration", $duration);

	return date("Y-m-d H:i:s", $duration);
}

function podlove_rehash_total_remaining($table)
{
	global $wpdb;

	$sql = sprintf(
		'select COUNT(distinct request_id) from %s WHERE request_id NOT LIKE "%s"', 
		$table,
		podlove_rehash_prefix() . "%"
	);

	return (int) $wpdb->get_var($sql);
}

function podlove_rehash_progress_class()
{
	if (php_sapi_name() == 'cli')
		return "\Dariuszp\CliProgressBar";
	else
		return "\PodloveSilentProgressBar";
}

function podlove_rehash_log($message)
{
	if (php_sapi_name() == 'cli')
		print_r($message);
}

function podlove_rehash_get_random_string()
{
	if (function_exists('random_bytes'))
		return random_bytes(12);

	if (function_exists('openssl_random_pseudo_bytes'))
		return bin2hex(openssl_random_pseudo_bytes(12));

	return dechex(mt_rand()) . dechex(mt_rand());
}

function podlove_rehash_func($old_hash, $salt)
{
	if (function_exists('openssl_digest'))
		return podlove_rehash_prefix() . openssl_digest($old_hash . $salt, 'sha256');

	if (function_exists('crypt'))
		return podlove_rehash_prefix() . crypt($old_hash, $salt);

	return podlove_rehash_prefix() . sha1($old_hash . $salt);
}

function podlove_rehash_prefix()
{
	return 'DSGVO';
}
