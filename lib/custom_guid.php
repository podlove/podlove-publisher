<?php

namespace Podlove;

/**
 * Add custom GUID to episodes.
 * Display in all podcast feeds.
 */
class Custom_Guid
{
    /**
     * Register hooks.
     */
    public static function init()
    {
        add_action('wp_insert_post', [__CLASS__, 'generate_guid_for_episodes'], 10, 2);
        add_filter('get_the_guid', [__CLASS__, 'override_wordpress_guid'], 100, 2);
        add_action('podlove_save_episode', [__CLASS__, 'save_form'], 10, 2);

        add_action('add_meta_boxes_podcast', [__CLASS__, 'meta_box']);
    }

    public static function meta_box()
    {
        add_meta_box(
            // $id
            'podlove_podcast_guid',
            // $title
            __('Podcast Episode GUID', 'podlove-podcasting-plugin-for-wordpress'),
            // $callback
            '\Podlove\Custom_Guid::meta_box_callback',
            // $page
            'podcast'
        );
    }

    public static function meta_box_callback()
    {
        ?>
		<div>
			<span id="guid_preview"><?php echo get_the_guid(); ?></span>
			<a href="#" id="regenerate_guid"><?php echo __('regenerate', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
		</div>
		<span class="description">
			<?php echo __('Identifier for this episode. Change it to force podcatchers to redownload media files for this episode.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</span>

		<input type="hidden" name="_podlove_meta[guid]" id="_podlove_meta_guid" value="<?php echo get_the_guid(); ?>">

		<script type="text/javascript">
		jQuery(function($){
			$("#regenerate_guid").on('click', function(e) {
				e.preventDefault();

				var data = {
					action: 'podlove-get-new-guid',
					post_id: jQuery("#post_ID").val()
				};

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						if (result && result.guid) {
							$("#_podlove_meta_guid").val(result.guid);
							$("#guid_preview").html(result.guid);
							if ( ! $(".guid_warning").length ) {
								$(".row__podlove_meta_guid .description")
									.append("<br><strong class=\"guid_warning\">GUID regenerated. You still need to save the post.<br>Only regenerate if you messed up and need all clients to redownload all files!</strong>");
							}
						} else {
							alert("Sorry, couldn't generate new GUID.");
						}
					}
				});

				return false;
			});
		});
		</script>
		<?php
    }

    public static function save_form($post_id, $form_data)
    {
        if (isset($form_data['guid'])) {
            update_post_meta($post_id, '_podlove_guid', $form_data['guid']);
        }
    }

    /**
     * When an episode is created, generate and save a custom guid.
     *
     * @wp-hook wp_insert_post
     *
     * @param int    $post_id
     * @param object $post
     */
    public static function generate_guid_for_episodes($post_id, $post)
    {
        if ($post->post_type !== 'podcast') {
            return;
        }

        if (get_post_meta($post->ID, '_podlove_guid', true)) {
            return;
        }

        $guid = self::guid_for_post($post);
        update_post_meta($post->ID, '_podlove_guid', $guid);
    }

    /**
     * Generate a guid for a WordPress post object.
     *
     * @param object $post
     *
     * @return string the GUID
     */
    public static function guid_for_post($post)
    {
        $segments = [];

        $segments[] = apply_filters('podlove_guid_prefix', 'podlove');
        $segments[] = apply_filters('podlove_guid_time', gmdate('c'));
        $hash = substr(sha1($post->ID.$post->post_title.time()), 0, 15);
        $segments[] = apply_filters('podlove_guid_hash', $hash);

        return apply_filters('podlove_guid', strtolower(implode('-', $segments)));
    }

    /**
     * Whenever our GUID is available, use it. Fallback to WordPress GUID.
     *
     * @wp-hook get_the_guid
     *
     * @param string $guid    WordPress GUID
     * @param mixed  $post_id
     *
     * @return string
     */
    public static function override_wordpress_guid($guid, $post_id = null)
    {
        if ($podlove_guid = get_post_meta($post_id, '_podlove_guid', true)) {
            return $podlove_guid;
        }

        return $guid;
    }

    public static function find_duplicate_guids()
    {
        $published_post_ids = array_map(function ($e) {
            return $e->post_id;
        }, \Podlove\Model\Podcast::get()->episodes());

        $guids = [];

        foreach ($published_post_ids as $post_id) {
            $guid = get_the_guid($post_id);
            if (!array_key_exists($guid, $guids)) {
                $guids[$guid] = [$post_id];
            } else {
                $guids[$guid] = array_merge($guids[$guid], [$post_id]);
            }
        }

        return array_filter($guids, function ($values) {
            return count($values) > 1;
        });
    }
}
