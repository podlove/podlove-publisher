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
		
		$podcast = Model\Podcast::get();
		?>
		<div id="asset_validation">
			<?php
			$episodes = Model\Episode::all( 'ORDER BY slug DESC' );
			$assets   = Model\EpisodeAsset::all();

			$header = array( __( 'Episode', 'podlove' ) );
			foreach ( $assets as $asset ) {
				$header[] = $asset->title;
			}
			$header[] = __( 'Status', 'podlove' );

			define( 'ASSET_STATUS_OK', '<i class="clickable podlove-icon-ok"></i>' );
			define( 'ASSET_STATUS_INACTIVE', '<i class="podlove-icon-minus"></i>' );
			define( 'ASSET_STATUS_ERROR', '<i class="clickable podlove-icon-remove"></i>' );
			?>

			<h4><?php echo $podcast->title ?></h4>

			<input id="revalidate_assets" type="button" class="button button-primary" value="<?php echo __( 'Revalidate Assets', 'podlove' ); ?>">

			<table id="asset_status_dashboard">
				<thead>
					<tr>
						<?php foreach ( $header as $column_head ): ?>
							<th><?php echo $column_head ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $episodes as $episode ): ?>
						<?php 
						// skip invalid episodes
						if (!isset($media_files[$episode->id]))
							continue;
						?>
						<tr>
							<td>
								<a href="<?php echo admin_url('post.php?post=' . $episode->post_id . '&amp;action=edit') ?>"><?php echo $episode->slug ?></a>
							</td>
							<?php foreach ( $assets as $asset ): ?>
								<?php 
								if (isset($media_files[$episode->id][$asset->id])) {
									$file = $media_files[$episode->id][$asset->id];
								} else {
									$file = false;
								}
								?>
								<td style="text-align: center; font-weight: bold; font-size: 20px" data-media-file-id="<?php echo $file ? $file['media_file_id'] : '' ?>">
									<?php
									if ( ! $file ) {
										echo ASSET_STATUS_INACTIVE;
									} elseif ( $file['size'] > 0 ) {
										echo ASSET_STATUS_OK;
									} else {
										echo ASSET_STATUS_ERROR;
									}
									?>
								</td>
							<?php endforeach; ?>
							<td>
								<?php echo $media_files[$episode->id]['post_status'] ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<style type="text/css">
		#validation h4 {
			font-size: 20px;
		}

		#validation .episode {
			margin: 0 0 15px 0;
		}

		#validation .slug {
			font-size: 18px;
			margin: 0 0 5px 0;
		}

		#validation .warning {
			color: maroon;
		}

		#validation .error {
			color: red;
		}
		</style>
		<?php
	}
}
