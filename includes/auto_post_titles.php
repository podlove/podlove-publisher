<?php 

use Podlove\Model\Podcast;

use Podlove\Model\Episode;

add_filter('the_title', 'podlove_maybe_override_post_titles', 10, 2);
// low prio is important so the_title_rss sanitizer filters work
add_filter('the_title_rss', 'podlove_maybe_override_rss_post_titles', 3);
add_action('admin_print_scripts', 'podlove_override_post_title_script');

function podlove_maybe_override_post_titles($original_title, $post_id = null) 
{
	if (is_null($post_id))
		$post_id = get_the_ID();

	if (get_post_type($post_id) !== 'podcast')
		return $original_title;

	if (!podlove_is_title_autogen_enabled())
		return $original_title;

	$episode_title = podlove_generated_post_title($post_id);

	if ($episode_title) {
		return $episode_title;
	} else {
		return $original_title;
	}
}

function podlove_maybe_override_rss_post_titles($original_title)
{
	$post_id = get_the_ID();
	$podcast = Podcast::get();

	if (get_post_type($post_id) !== 'podcast')
		return $original_title;

	switch ($podcast->get_feed_episode_title_variant()) {
		case 'blog':
			return $original_title;
			break;
		case 'episode':
			$episode = Episode::find_one_by_post_id($post_id);

			if ($episode && $episode->title) {
				return trim(strip_tags($episode->title));
			} else {
				return $original_title;
			}
			break;
		case 'template':
			$title = podlove_generated_feed_post_title($post_id);
			if ($title) {
				return $title;
			} else {
				return $original_title;
			}
			break;
		default:
			return $original_title;
	}
}

function podlove_generated_post_title($post_id)
{
	$template = \Podlove\get_setting( 'website', 'blog_title_template' );
	return podlove_get_episode_title_by_template($post_id, $template);
}

function podlove_generated_feed_post_title($post_id)
{
	$template = Podcast::get()->get_feed_episode_title_template();
	return podlove_get_episode_title_by_template($post_id, $template);
}

function podlove_get_episode_title_by_template($post_id, $template)
{
	$episode = Episode::find_one_by_post_id($post_id);

	if (!$template || !$episode)
		return false;

	$title = $template;
	$title = str_replace('%mnemonic%', strip_tags(podlove_get_mnemonic($post_id)), $title);
	$title = str_replace('%episode_number%', $episode->number_padded(), $title);
	$title = str_replace('%episode_title%', trim(strip_tags($episode->title)), $title);

	$title = apply_filters('podlove_generated_post_title', $title, $episode);

	return trim($title);	
}

function podlove_override_post_title_script()
{
	if (!\Podlove\is_episode_edit_screen())
		return;

	$data = [
		'enabled' => podlove_is_title_autogen_enabled(),
		'template' => \Podlove\get_setting( 'website', 'blog_title_template' ),
		'episode_padding' => \Podlove\get_setting( 'website', 'episode_number_padding' ),
		'mnemonic' => podlove_get_mnemonic(),
		'placeholder' => __('Fill in episode title below', 'podlove-podcasting-plugin-for-wordpress')
	];

	$data = apply_filters('podlove_js_data_for_post_title', $data, get_the_ID());
?>
<script type="text/javascript">
var PODLOVE = PODLOVE || {};
PODLOVE.override_post_title = <?php echo json_encode($data) ?>;
</script>	
<?php	
}

function podlove_get_mnemonic($post_id = null)
{
	$podcast = Podcast::get();
	return $podcast->mnemonic;
}

function podlove_is_title_autogen_enabled()
{
	return (bool) \Podlove\get_setting( 'website', 'enable_generated_blog_post_title' );
}
