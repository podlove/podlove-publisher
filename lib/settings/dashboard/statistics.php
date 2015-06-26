<?php
namespace Podlove\Settings\Dashboard;

use Podlove\Model;

class Statistics {

	public static function content() {
		$episode_edit_url = site_url('/wp-admin/edit.php?post_type=podcast');
		$statistics = self::prepare_statistics();

		\Podlove\load_template('settings/dashboard/statistics', [
			'episode_edit_url' => $episode_edit_url,
			'statistics'       => $statistics
		]);
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