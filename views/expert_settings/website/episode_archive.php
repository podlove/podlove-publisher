<input name="podlove_website[episode_archive]" id="episode_archive" type="checkbox" <?php checked( $enable_episode_archive, 'on' ) ?>> <?php _e( 'Enable episode pages: a complete, paginated list of episodes, sorted by publishing date.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
<div id="episode_archive_slug_edit"<?php if ( !$enable_episode_archive ) echo ' style="display:none;"' ?>>
	<code><?php echo get_option('siteurl') . $blog_prefix; ?></code>
	<input class="podlove-check-input" name="podlove_website[episode_archive_slug]" id="episode_archive_slug" type="text" value="<?php echo $episode_archive_slug ?>">
</div>

<script type="text/javascript">
jQuery(function($) {
	$(document).ready(function() {
		$("#episode_archive").on("click", function(e) {
			if ( $(this).is( ':checked' ) ) {
				$("#episode_archive_slug_edit").slideDown();
			} else {
				$("#episode_archive_slug_edit").slideUp();
			}
		});
	});
});
</script>

<style type="text/css">
#episode_archive_slug_edit {
	margin-top: 10px;
}
</style>
