<?php

add_action('quick_edit_custom_box',  'podlove_episodeno_quickedit_form');
add_action('save_post', 'podlove_episodeno_quickedit_save');
add_action('admin_footer', 'podlove_episodeno_quickedit_populate_form');
add_filter('post_row_actions', 'podlove_episodeno_quickedit_extend_action_items', 10, 2);

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
	if ( ! filter_input(INPUT_POST, "_podlove_meta_quickedit_episode_number_nonce_field") ) {
		return;
	}

	if ( wp_verify_nonce($_POST['_podlove_meta_quickedit_episode_number_nonce_field'], '_podlove_meta_quickedit_episode_number') ) {
		$episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);
		$episode->number = sanitize_text_field($_POST['_podlove_meta']['number']);

		if ( is_object($episode) && is_string($episode->number) )
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
		function podlove_update_episode_number_quick_edit_form() {
			jQuery('button.editinline').on('click', function() {
				episode_number = jQuery(this).data("podlove-update-quickedit-episode-number");

				if (typeof episode_number === "number") {
					jQuery('.podlove_meta_quickedit_episode_number').val(episode_number);
				} else {
					jQuery('.podlove_meta_quickedit_episode_number').val('');
				}
			});
		}

		jQuery(document).ready(function() {
			jQuery('body').on('DOMNodeInserted', function() {
				podlove_update_episode_number_quick_edit_form();
			});

			podlove_update_episode_number_quick_edit_form();
		});
	</script>
	<?php
}

function podlove_episodeno_quickedit_extend_action_items($actions, $post)
{
	if ($post->post_type !== 'podcast') {
		return $actions;
	}

	$episode = \Podlove\Model\Episode::find_or_create_by_post_id($post->ID);
	// Not nice but seems like there is no more elegant way to do this right now
	$actions["inline hide-if-no-js"] = str_replace( '<button', '<button data-podlove-update-quickedit-episode-number="' . $episode->number . '" ', $actions["inline hide-if-no-js"] );

	return $actions;
}
