<div class="podlove-dashboard-statistics-wrapper">

	<h4><?php echo __('Episodes', 'podlove-podcasting-plugin-for-wordpress') ?></h4>
	<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
		<tr>
			<td class="podlove-dashboard-number-column">
				<a href="<?php echo $episode_edit_url; ?>&amp;post_status=publish"><?php echo $statistics['episodes']['publish']; ?></a>
			</td>
			<td>
				<span style="color: #2c6e36;"><?php echo __( 'Published', 'podlove-podcasting-plugin-for-wordpress' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<a href="<?php echo $episode_edit_url; ?>&amp;post_status=private"><?php echo $statistics['episodes']['private']; ?></a>
			</td>
			<td>
				<span style="color: #b43f56;"><?php echo __( 'Private', 'podlove-podcasting-plugin-for-wordpress' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<a href="<?php echo $episode_edit_url; ?>&amp;post_status=future"><?php echo $statistics['episodes']['future']; ?></a>
			</td>
			<td>
				<span style="color: #a8a8a8;"><?php echo __( 'To be published', 'podlove-podcasting-plugin-for-wordpress' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<a href="<?php echo $episode_edit_url; ?>&amp;post_status=draft"><?php echo $statistics['episodes']['draft']; ?></a>
			</td>
			<td>
				<span style="color: #c0844c;"><?php echo __( 'Drafts', 'podlove-podcasting-plugin-for-wordpress' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column podlove-dashboard-total-number">
				<a href="<?php echo $episode_edit_url; ?>"><?php echo $statistics['total_number_of_episodes']; ?></a>
			</td>
			<td class="podlove-dashboard-total-number">
				<?php echo __( 'Total', 'podlove-podcasting-plugin-for-wordpress' ); ?>
			</td>
		</tr>
	</table>
</div>
<div class="podlove-dashboard-statistics-wrapper">
	<h4><?php echo __('Statistics', 'podlove-podcasting-plugin-for-wordpress') ?></h4>
	<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo gmdate("H:i:s", $statistics['average_episode_length'] ); ?>
			</td>
			<td>
				<?php echo __( 'is the average length of an episode', 'podlove-podcasting-plugin-for-wordpress' ); ?>.
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php
				$days = round($statistics['total_episode_length'] / 3600 / 24, 1);
				echo sprintf(_n('%s day', '%s days', $days, 'podlove'), $days);
				?>
			</td>
			<td>
				<?php echo __( 'is the total playback time of all episodes', 'podlove-podcasting-plugin-for-wordpress' ); ?>.
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo \Podlove\format_bytes($statistics['average_media_file_size'], 1); ?>
			</td>
			<td>
				<?php echo __( 'is the average media file size', 'podlove-podcasting-plugin-for-wordpress' ); ?>.
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo \Podlove\format_bytes($statistics['total_media_file_size'], 1); ?>
			</td>
			<td>
				<?php echo __( 'is the total media file size', 'podlove-podcasting-plugin-for-wordpress' ); ?>.
			</td>
		</tr>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo sprintf(_n('%s day', '%s days', $statistics['days_between_releases'], 'podlove'), $statistics['days_between_releases']); ?>
			</td>
			<td>
				<?php echo __( 'is the average interval until a new episode is released', 'podlove-podcasting-plugin-for-wordpress' ); ?>.
			</td>
		</tr>
		<?php do_action('podlove_dashboard_statistics'); ?>
	</table>
</div>
<p>
	<?php echo sprintf( __('You are using %s', 'podlove-podcasting-plugin-for-wordpress'), '<strong>Podlove Publisher ' . \Podlove\get_plugin_header( 'Version' ) . '</strong>'); ?>.
</p>