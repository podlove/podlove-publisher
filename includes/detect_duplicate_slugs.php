<?php
use \Podlove\Model\Episode;

add_action('admin_head-post.php', 'podlove_check_for_duplicate_episode_slug');

function podlove_check_for_duplicate_episode_slug() {

	if (get_post_type() != 'podcast')
		return;

	if (!$episode = Episode::find_one_by_property('post_id', get_the_ID()))
		return;

	if (!$duplicate_id = podlove_get_duplicate_episode_id($episode))
		return;

	podlove_duplicate_episode_slug_notice($episode, $duplicate_id);
}

function podlove_get_duplicate_episode_id(Episode $current_episode) {
	global $wpdb;

	$sql = $wpdb->prepare('
		SELECT
		  p.ID
		FROM
		  `' . $wpdb->posts . '` p
		JOIN
		  `' . Episode::table_name() . '` e ON e.`post_id` = p.`ID`
		WHERE
		  p.`post_status` IN (\'publish\', \'private\')
		  AND p.post_type = "podcast"
		  AND p.ID != %d
		  AND e.slug = %s
		LIMIT 0, 1',
	$current_episode->post_id, $current_episode->slug);
	
	return $wpdb->get_var($sql);
}

function podlove_duplicate_episode_slug_notice(Episode $episode, $duplicate_id) {
	add_action('admin_notices', function () use ($episode, $duplicate_id) {
		?>
		<div class="error">
			<p>
				<?php
				echo sprintf(
					__('Watch out, an episode with the slug "%s" already exists! %s', 'podlove'),
					$episode->slug,
					sprintf('<a href="%s">%s</a>', get_edit_post_link($duplicate_id), get_the_title($duplicate_id))
				)
				?>
			</p>
		</div>
		<?php
	});
}
