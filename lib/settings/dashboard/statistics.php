<?php
namespace Podlove\Settings\Dashboard;

class Statistics {

	public static function content() {
		$episode_edit_url = site_url() . '/wp-admin/edit.php?post_type=podcast';
		$statistics = self::prepare_statistics();
		?>
		<div class="podlove-dashboard-statistics-wrapper">
			<h4>Episodes</h4>
			<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
				<tr>
					<td class="podlove-dashboard-number-column">
						<a href="<?php echo $episode_edit_url; ?>&amp;post_status=publish"><?php echo $statistics['episodes']['publish']; ?></a>
					</td>
					<td>
						<span style="color: #2c6e36;"><?php echo __( 'Published', 'podlove' ); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<a href="<?php echo $episode_edit_url; ?>&amp;post_status=private"><?php echo $statistics['episodes']['private']; ?></a>
					</td>
					<td>
						<span style="color: #b43f56;"><?php echo __( 'Private', 'podlove' ); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<a href="<?php echo $episode_edit_url; ?>&amp;post_status=future"><?php echo $statistics['episodes']['future']; ?></a>
					</td>
					<td>
						<span style="color: #a8a8a8;"><?php echo __( 'To be published', 'podlove' ); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<a href="<?php echo $episode_edit_url; ?>&amp;post_status=draft"><?php echo $statistics['episodes']['draft']; ?></a>
					</td>
					<td>
						<span style="color: #c0844c;"><?php echo __( 'Drafts', 'podlove' ); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column podlove-dashboard-total-number">
						<a href="<?php echo $episode_edit_url; ?>"><?php echo $statistics['total_number_of_episodes']; ?></a>
					</td>
					<td class="podlove-dashboard-total-number">
						<?php echo __( 'Total', 'podlove' ); ?>
					</td>
				</tr>
			</table>
		</div>
		<div class="podlove-dashboard-statistics-wrapper">
			<h4><?php echo __('Statistics', 'podlove') ?></h4>
			<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo gmdate("H:i:s", $statistics['average_episode_length'] ); ?>
					</td>
					<td>
						<?php echo __( 'is the average length of an episode', 'podlove' ); ?>.
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
						<?php echo __( 'is the total playback time of all episodes', 'podlove' ); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo \Podlove\format_bytes($statistics['average_media_file_size'], 1); ?>
					</td>
					<td>
						<?php echo __( 'is the average media file size', 'podlove' ); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo \Podlove\format_bytes($statistics['total_media_file_size'], 1); ?>
					</td>
					<td>
						<?php echo __( 'is the total media file size', 'podlove' ); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo sprintf(_n('%s day', '%s days', $statistics['days_between_releases'], 'podlove'), $statistics['days_between_releases']); ?>
					</td>
					<td>
						<?php echo __( 'is the average interval until a new episode is released', 'podlove' ); ?>.
					</td>
				</tr>
				<?php do_action('podlove_dashboard_statistics'); ?>
			</table>
		</div>
		<p>
			<?php echo sprintf( __('You are using %s', 'podlove'), '<strong>Podlove Publisher ' . \Podlove\get_plugin_header( 'Version' ) . '</strong>'); ?>.
		</p>
		<?php
	}

	public static function prepare_statistics() {
		if ( ( $statistics = get_transient( 'podlove_dashboard_stats' ) ) !== FALSE ) {
			return $statistics;
		} else {
			$episodes = Model\Episode::find_all_by_time();

			$prev_post = 0;
			$counted_episodes = 0;
			$time_stamp_differences = array();
			$episode_durations = array();
			$episode_status_count = array(
				'publish' => 0,
				'private' => 0,
				'future' => 0,
				'draft' => 0,
			);

			$statistics = array(
					'episodes' => array(),
					'total_episode_length' => 0,
					'average_episode_length' => 0,
					'days_between_releases' => 0,
					'average_media_file_size' => 0,
					'total_media_file_size' => 0
				);

			foreach ( $episodes as $episode_key => $episode ) {
				$post = get_post( $episode->post_id );
				$counted_episodes++;

				// duration in seconds
				if ( self::duration_to_seconds( $episode->duration ) > 0 )
					$episode_durations[$post->ID] = self::duration_to_seconds( $episode->duration );

				// count by post status
				if (!isset($episode_status_count[$post->post_status])) {
					$episode_status_count[$post->post_status] = 1;
				} else {
					$episode_status_count[$post->post_status]++;
				}

				// determine time in days since last publication
				if ($prev_post) {
					$timestamp_current_episode = new \DateTime( $post->post_date );
					$timestamp_next_episode = new \DateTime( $prev_post->post_date );
					$time_stamp_differences[$post->ID] = $timestamp_current_episode->diff($timestamp_next_episode)->days;
				}

				$prev_post = $post;
			}

			// Episode Stati
			$statistics['episodes'] = $episode_status_count;
			// Number of Episodes
			$statistics['total_number_of_episodes'] = count($episodes);
			// Total Episode length
			$statistics['total_episode_length'] = array_sum($episode_durations);
			// Calculating average episode in seconds
			$statistics['average_episode_length'] = count($episode_durations) > 0 ? round(array_sum($episode_durations) / count($episode_durations)) : 0;
			// Calculate average time until next release in days
			$statistics['days_between_releases']   = count($time_stamp_differences) > 0 ? round(array_sum($time_stamp_differences) / count($time_stamp_differences)) : 0;			

			// Media Files
			$episodes_to_media_files = function ($media_files, $episode) {
				return array_merge($media_files, $episode->media_files());
			};
			$media_files       = array_reduce($episodes, $episodes_to_media_files, array());
			$valid_media_files = array_filter($media_files, function($m) { return $m->size > 0; });

			$sum_mediafile_sizes = function ($result, $media_file) {
				return $result + $media_file->size;
			};
			$statistics['total_media_file_size'] = array_reduce( $valid_media_files, $sum_mediafile_sizes, 0 );
			$mediafile_count      = count($valid_media_files);

			$statistics['average_media_file_size'] = $mediafile_count > 0 ? $statistics['total_media_file_size'] / $mediafile_count : 0;

			set_transient( 'podlove_dashboard_stats', $statistics, 3600 );
			return $statistics;
		}
	}	
	
	public static function duration_to_seconds( $timestring ) {
		return \Podlove\NormalPlayTime\Parser::parse( $timestring, 's' );
	}
}