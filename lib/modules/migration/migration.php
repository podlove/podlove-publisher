<?php

namespace Podlove\Modules\Migration;

use Podlove\Model;
use Podlove\Modules\Migration\Settings\Assistant;

class Migration extends \Podlove\Modules\Base
{
    protected $module_name = 'Migration';
    protected $module_description = 'Helps you migrate from PodPress/PowerPress/... to Podlove.';
    protected $module_group = 'system';

    public function load()
    {
        add_action('admin_enqueue_scripts', [$this, 'register_admin_styles']);
        add_action('admin_menu', [$this, 'register_menu'], 20);

        add_action('admin_notices', [$this, 'migration_teaser']);
    }

    public function migration_teaser()
    {
        if (get_option('_podlove_hide_teaser')) {
            return;
        } ?>
			<div id="podlove_welcome">
				<h3>
					<?php echo __('This is the Podlove Publisher. Welcome!', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</h3>
				<p>
					<?php
                    echo sprintf(
            __('Do you have an existing podcast here which you\'d like to convert to Podlove Publisher?%sPlease use the %sMigration Assistant%s.', 'podlove-podcasting-plugin-for-wordpress'),
            '<br>',
            '<a href="'.admin_url('admin.php'.\Podlove\Modules\Migration\Settings\Assistant::get_page_link()).'">',
            '</a>'
        ); ?>
				</p>
				<p>
					<?php echo __('Have fun!', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</p>
				<div class="dismiss">
					<a href="#">
						<?php echo __('Dismiss this message', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</a>
				</div>
			</div>

			<script type="text/javascript">
				jQuery(function($){
					$("#podlove_welcome .dismiss a").on("click", function(e) {

						var data = {
							action: 'podlove-hide-teaser'
						};

						$.ajax({
							url: ajaxurl,
							data: data,
							dataType: 'json'
						});

						$("#podlove_welcome").slideUp();

						return false;
					});
				});
			</script>
			<?php
    }

    public function register_admin_styles()
    {
        if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'podlove_settings_migration_handle') {
            wp_register_script('twitter-bootstrap-script', $this->get_module_url().'/js/bootstrap.min.js');
            wp_enqueue_script('twitter-bootstrap-script', 'jquery');

            wp_register_style('twitter-bootstrap-style', $this->get_module_url().'/css/bootstrap.min.css');
            wp_enqueue_style('twitter-bootstrap-style');
        }

        wp_register_style('podlove-migration-style', $this->get_module_url().'/css/migration_assistant.css');
        wp_enqueue_style('podlove-migration-style');
    }

    public function register_menu()
    {
        new Settings\Assistant(\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE);
    }
}

function get_podcast_settings()
{
    $migration_settings = get_option('podlove_migration', []);
    $migration_settings = (isset($migration_settings['podcast'])) ? $migration_settings['podcast'] : [];

    $itunes_summary_default = '';
    $itunes_subtitle_default = get_bloginfo('description');

    // harvest low hanging podPress fruits
    if ($podPress_config = get_option('podPress_config')) {
        if (isset($podPress_config['iTunes']['summary']) && $podPress_config['iTunes']['summary']) {
            $itunes_summary_default = $podPress_config['iTunes']['summary'];
        }
        if (isset($podPress_config['iTunes']['subtitle']) && $podPress_config['iTunes']['subtitle']) {
            $itunes_subtitle_default = $podPress_config['iTunes']['subtitle'];
        }
    }

    // harvest low hanging podPress fruits
    if ($powerPress_config = get_option('powerpress_feed')) {
        if (isset($powerPress_config['itunes_summary']) && $powerPress_config['itunes_summary']) {
            $itunes_summary_default = $powerPress_config['itunes_summary'];
        }
        if (isset($powerPress_config['itunes_subtitle']) && $powerPress_config['itunes_subtitle']) {
            $itunes_subtitle_default = $powerPress_config['itunes_subtitle'];
        }
    }

    $defaults = [
        'title' => get_bloginfo('name'),
        'subtitle' => $itunes_subtitle_default,
        'summary' => $itunes_summary_default,
        'media_file_base_url_option' => 'preset',
        'media_file_base_url_preset' => null,
        'media_file_base_url_custom' => '',
    ];

    return wp_parse_args($migration_settings, $defaults);
}

function get_media_file_base_url()
{
    $podcast = get_podcast_settings();

    if (isset($podcast['media_file_base_url_option']) && $podcast['media_file_base_url_option'] == 'preset') {
        return $podcast['media_file_base_url_preset'];
    }

    return $podcast['media_file_base_url_custom'];
}

function migrate_post($post_id)
{
    $post = get_post($post_id);
    $migration_settings = get_option('podlove_migration', []);

    $post_content = $post->post_content;

    if ($migration_settings['cleanup']['player']) {
        $post_content = preg_replace('/\[(powerpress|podloveaudio|podlovevideo|display_podcast)[^\]]*\]/', '', $post_content);
    }

    $new_post = [
        'menu_order' => $post->menu_order,
        'comment_status' => $post->comment_status,
        'ping_status' => $post->ping_status,
        'post_author' => $post->post_author,
        'post_content' => $post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_mime_type' => $post->post_mime_type,
        'post_parent' => $post_id,
        'post_password' => $post->post_password,
        'post_status' => 'pending',
        'post_title' => $post->post_title,
        'post_type' => 'podcast',
        'post_date' => $post->post_date,
        'post_date_gmt' => get_gmt_from_date($post->post_date),
    ];

    $new_slug = null;
    switch ($migration_settings['post_slug']) {
        case 'wordpress':
            $new_slug = $post->post_name;

            break;
        case 'file':
            $new_slug = Assistant::get_file_slug($post);

            break;
        case 'number':
            $new_slug = Assistant::get_number_slug($post);

            break;
    }

    $override_slug = function ($data, $postarr) use ($new_slug) {
        if ($new_slug) {
            $data['post_name'] = $new_slug;
        }

        return $data;
    };

    add_filter('wp_insert_post_data', $override_slug, 10, 2);
    $new_post_id = wp_insert_post($new_post);
    remove_filter('wp_insert_post_data', $override_slug, 10, 2);

    $new_post = get_post($new_post_id);

    // update guid
    update_post_meta($new_post_id, '_podlove_guid', $post->guid);

    // add redirect from previous url
    add_post_meta($new_post_id, 'podlove_alternate_url', get_permalink($post_id));

    // prevent adn module from triggering a post
    update_post_meta($new_post_id, '_podlove_episode_was_published', true);

    // migrate taxonomies
    $taxonomies = get_object_taxonomies(get_post_type($post_id));

    foreach ($taxonomies as $tax) {
        $terms = wp_get_object_terms($post_id, $tax);
        $term = [];
        foreach ($terms as $t) {
            $term[] = $t->slug;
        }

        wp_set_object_terms($new_post_id, $term, $tax);
    }

    $post_data = new Legacy_Post_Parser($post_id);

    $episode = Model\Episode::find_or_create_by_post_id($new_post_id);
    $episode->slug = Assistant::get_episode_slug($post, $migration_settings['slug']);
    $episode->duration = $post_data->get_duration();
    $episode->subtitle = $post_data->get_subtitle();
    $episode->summary = $post_data->get_summary();
    $episode->save();

    foreach (Model\EpisodeAsset::all() as $asset) {
        Model\MediaFile::find_or_create_by_episode_id_and_episode_asset_id($episode->id, $asset->id);
    }

    // copy all meta
    $meta = get_post_meta($post_id);
    foreach ($meta as $key => $values) {
        if (!in_array($key, ['enclosure', '_podPressPostSpecific', '_podPressMedia']) || !$migration_settings['cleanup']['enclosures']) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, $value);
            }
        }
    }

    // copy all comments
    $comments_map = []; // map old comment IDs to new comment IDs
    foreach (get_comments(['post_id' => $post_id, 'order' => 'ASC']) as $comment) {
        $old_comment_id = $comment->comment_ID;
        $comment->comment_post_ID = $new_post_id;

        if ($comment->comment_parent && isset($comments_map[$comment->comment_parent])) {
            $comment->comment_parent = $comments_map[$comment->comment_parent];
        }

        $new_comment_id = wp_insert_comment((array) $comment);
        $comments_map[$old_comment_id] = $new_comment_id;
    }

    return $new_post_id;
}

function ajax_migrate_post()
{
    $new_post_id = migrate_post((int) $_REQUEST['post_id']);

    $migration_cache = get_option('podlove_migrated_posts_cache', []);
    $migration_cache[(int) $_REQUEST['post_id']] = (int) $new_post_id;
    update_option('podlove_migrated_posts_cache', $migration_cache);

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');
    echo json_encode(['url' => get_edit_post_link($new_post_id)]);

    die();
}
add_action('wp_ajax_podlove-migrate-post', '\Podlove\Modules\Migration\ajax_migrate_post');

function update_migration_settings()
{
    $migration_settings = get_option('podlove_migration', []);

    if (isset($_REQUEST['file_types'])) {
        $file_type_id = (int) $_REQUEST['file_types'][0];
        $is_checked = $_REQUEST['file_types'][1] == 'true';

        if (!isset($migration_settings['file_types'])) {
            $migration_settings['file_types'] = [];
        }

        if ($is_checked) {
            $migration_settings['file_types'][$file_type_id] = 'on';
        } else {
            unset($migration_settings['file_types'][$file_type_id]);
        }
    }

    if (isset($_REQUEST['post_slug'])) {
        $migration_settings['post_slug'] = $_REQUEST['post_slug'];
    }

    if (isset($_REQUEST['cleanup'])) {
        $cleanup_key = $_REQUEST['cleanup'][0];
        $cleanup_val = $_REQUEST['cleanup'][1];

        if (!isset($migration_settings['cleanup'])) {
            $migration_settings['cleanup'] = [];
        }

        $migration_settings['cleanup'][$cleanup_key] = $cleanup_val;
    }

    update_option('podlove_migration', $migration_settings);
    die();
}
add_action('wp_ajax_podlove-update-migration-settings', '\Podlove\Modules\Migration\update_migration_settings');
