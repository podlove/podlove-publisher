<?php

add_filter('manage_edit-podcast_columns', 'podlove_add_episodeno_column_to_episodes_table' );
add_action('manage_podcast_posts_custom_column', 'podlove_add_episodeno_column_content_to_episodes_table' );
add_action('quick_edit_custom_box',  'podlove_episodeno_quickedit_form');
add_action('save_post', 'podlove_episodeno_quickedit_save');
add_action('admin_footer', 'podlove_episodeno_quickedit_populate_form');
add_filter('post_row_actions', 'podlove_episodeno_quickedit_extend_action_items', 10, 2);

function podlove_add_episodeno_column_to_episodes_table($columns)
{
	$keys = array_keys($columns);
	$insertIndex = array_search('title', $keys); // before title column

	// insert downloads at that index
	$columns = array_slice($columns, 0, $insertIndex, true) +
	       array("episode_number" => __('Ep. #', 'podlove-podcasting-plugin-for-wordpress')) +
	       array_slice($columns, $insertIndex, count($columns) - 1, true);

	return $columns;
}

function podlove_add_episodeno_column_content_to_episodes_table($column_name)
{
	if ($column_name === 'episode_number') {
	    //check for null to prevent fatal error
        if(\Podlove\get_episode() != null) {
            echo \Podlove\get_episode()->number();
        }
	}
}

function podlove_episodeno_quickedit_form($column_name)
{
    if ($column_name === 'episode_number') {
			?>
			<fieldset class="inline-edit-col-left">
		    <div class="inline-edit-col">
					<label class="alignleft">
						<span class="title"><?php _e('Episode number', 'podlove-podcasting-plugin-for-wordpress') ?></span>
						<input type="number" name="_podlove_meta[number]" class="podlove_meta_quickedit_episode_number" />
					</label>
					<?php wp_nonce_field( '_podlove_meta_quickedit_episode_number', '_podlove_meta_quickedit_episode_number_nonce_field' ); ?>
		    </div>
	    </fieldset>
	    <?php
		}
}

function podlove_episodeno_quickedit_save($post_id)
{
	if ( wp_verify_nonce($_POST['_podlove_meta_quickedit_episode_number_nonce_field'], '_podlove_meta_quickedit_episode_number') ) {
		$episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);
		$episode->number = sanitize_text_field($_POST['_podlove_meta']['number']);

		if ( is_string($episode->number) )
			$episode->save();
	}
}

function podlove_episodeno_quickedit_populate_form()
{
	global $current_screen;

  if ($current_screen->post_type !== 'podcast') {
  	return;
  }

	?>
	<script type="text/javascript">
		function podlove_update_quickedit_episode_number(number) {
			jQuery('.podlove_meta_quickedit_episode_number').val(number);
		}
	</script>
	<?php
}

function podlove_episodeno_quickedit_extend_action_items($actions, $post)
{
  if ($post->post_type !== 'podcast') {
		return $actions;
  }

	$episode = \Podlove\Model\Episode::find_or_create_by_post_id($post->ID);
	// Not nice but seems like there is in a more elegant way to do this right now
	$actions["inline hide-if-no-js"] = str_replace( '<button', '<button onclick="podlove_update_quickedit_episode_number(\'' . $episode->number . '\')"', $actions["inline hide-if-no-js"] );

	return $actions;
}
