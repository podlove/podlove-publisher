<input name="podlove_website[use_post_permastruct]" id="use_post_permastruct" type="checkbox" <?php checked( $use_post_permastruct, 'on' ) ?>> <?php _e( 'Use the same permalink structure as posts', 'podlove-podcasting-plugin-for-wordpress' ); ?>
<div id="custom_podcast_permastruct"<?php if ( $use_post_permastruct ) echo ' style="display:none;"' ?>>
	<code><?php echo get_option('home'); ?></code>
	<input name="podlove_website[custom_episode_slug]" id="custom_episode_slug" type="text" value="<?php echo $custom_episode_slug ?>">
	<p><span class="description">
		<?php _e( 'Placeholders', 'podlove-podcasting-plugin-for-wordpress' ); ?>: %podcast% (<?php _e( 'episode slug', 'podlove-podcasting-plugin-for-wordpress' ); ?>), %post_id%, %year%, %monthnum%, %day%, %hour%, %minute%, %second%, %category%, %author%<br>
		<?php _e( 'Example schemes', 'podlove-podcasting-plugin-for-wordpress' ); ?>: <code>/%podcast%</code>, <code>/episode/%podcast%</code>, <code>/%year%/%monthnum%/%podcast%</code>
	</span></p>
</div>

<script type="text/javascript">
jQuery(function($) {
	$(document).ready(function() {

		function handle_permastruct_settings() {
			if ( $("#use_post_permastruct").is( ':checked' ) ) {
				$("#custom_podcast_permastruct").slideUp();
			} else {
				$("#custom_podcast_permastruct").slideDown();
			}
		}

		$("#use_post_permastruct").on("click", function(e) {
			handle_permastruct_settings();
		});

		handle_permastruct_settings();
	});
});
</script>

<style type="text/css">
#custom_podcast_permastruct {
	margin-top: 10px;
}
</style>
