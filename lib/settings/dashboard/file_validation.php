<?php
namespace Podlove\Settings\Dashboard;

use \Podlove\Model;

class FileValidation {

	public static function content() {
		global $wpdb;

		$sql = "
		SELECT
			p.post_status,
			mf.episode_id,
			mf.episode_asset_id,
			mf.size,
			mf.id media_file_id
		FROM
			`" . Model\MediaFile::table_name() . "` mf
			JOIN `" . Model\Episode::table_name() . "` e ON e.id = mf.`episode_id`
			JOIN `" . $wpdb->posts . "` p ON e.`post_id` = p.`ID`
		WHERE
			p.`post_type` = 'podcast'
			AND p.post_status in ('private', 'draft', 'publish', 'pending', 'future')
		";

		$rows = $wpdb->get_results($sql, ARRAY_A);

		$media_files = [];
		foreach ($rows as $row) {
			
			if (!isset($media_files[$row['episode_id']])) {
				$media_files[$row['episode_id']] = [ 'post_status' => $row["post_status"] ];
			}

			$media_files[$row['episode_id']][$row['episode_asset_id']] = [
				'size'          => $row['size'],
				'media_file_id' => $row['media_file_id']
			];
		}
		
		$podcast  = Model\Podcast::get();
		$episodes = $podcast->episodes();
		$assets   = Model\EpisodeAsset::all();

		$header = [__('Episode', 'podlove')];
		foreach ( $assets as $asset ) {
			$header[] = $asset->title;
		}
		$header[] = __('Status', 'podlove');

		\Podlove\load_template('settings/dashboard/file_validation', [
			'episodes'    => $episodes,
			'assets'      => $assets,
			'media_files' => $media_files,
			'header'      => $header
		]);
	}
}
