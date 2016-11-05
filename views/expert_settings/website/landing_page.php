<select name="podlove_website[landing_page]" id="landing_page">
	<?php foreach ( $landing_page_options as $option ): ?>
		<option
			<?php if ( isset($option['value']) ): ?>
				value="<?php echo $option['value'] ?>"
				<?php if ( $landing_page == $option['value'] ): ?> selected<?php endif; ?>
			<?php endif; ?>
			<?php if ( isset($option['disabled']) && $option['disabled'] ): ?> disabled<?php endif; ?>
		>
			<?php echo $option['text'] ?>
		</option>
	<?php endforeach; ?>
</select>

<script type="text/javascript">
jQuery(function($) {
	$(document).ready(function() {
		var maybe_toggle_episode_archive_option = function() {
			var $archive = $("#episode_archive"),
				$archive_option = $("#landing_page option:eq(1)"),
				$home_option = $("#landing_page option:eq(0)");

			if ($archive.is(':checked')) {
				$archive_option.attr('disabled', false);
			} else {
				$archive_option.attr('disabled', 'disabled');
				// if it was selected before, unselect it
				if ($archive_option.attr('selected') == 'selected') {
					$archive_option.attr('selected', false);
					$home_option.attr('selected', 'selected');
				}
			}

		};

		$("#episode_archive").on("click", function(e) {
			maybe_toggle_episode_archive_option();
		});

		maybe_toggle_episode_archive_option();
	});
});
</script>
<?php 
echo __('This defines the landing page to your podcast. It is the site that your podcast feeds link to.', 'podlove-podcasting-plugin-for-wordpress');
