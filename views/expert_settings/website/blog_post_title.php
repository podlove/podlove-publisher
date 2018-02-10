<input name="podlove_website[enable_generated_blog_post_title]" id="enable_generated_blog_post_title" type="checkbox" <?php checked( $enable_generated_blog_post_title, 'on' ) ?>> <?php _e( 'Generate automatically', 'podlove-podcasting-plugin-for-wordpress' ); ?>
<div id="custom_episode_blog_post_title"<?php if ( !$enable_generated_blog_post_title ) echo ' style="display:none;"' ?>>
	<input name="podlove_website[blog_title_template]" id="blog_title_template" type="text" value="<?php echo $blog_title_template ?>" class="large-text">
	<p class="description">
		<?php _e( 'Placeholders', 'podlove-podcasting-plugin-for-wordpress' ); ?>: %mnemonic%, %episode_number%, %season_number%, %episode_title%
	</p>
</div>

<script type="text/javascript">
jQuery(function($) {
	$(document).ready(function() {

		function handle_permastruct_settings() {
			if ( $("#enable_generated_blog_post_title").is( ':checked' ) ) {
				$("#custom_episode_blog_post_title").slideDown();
			} else {
				$("#custom_episode_blog_post_title").slideUp();
			}
		}

		$("#enable_generated_blog_post_title").on("click", function(e) {
			handle_permastruct_settings();
		});

		handle_permastruct_settings();
	});
});
</script>

<style type="text/css">
#custom_episode_blog_post_title {
	margin-top: 10px;
}
</style>
