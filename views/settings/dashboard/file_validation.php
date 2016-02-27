<?php
define('ASSET_STATUS_OK', '<i class="clickable podlove-icon-ok"></i>');
define('ASSET_STATUS_INACTIVE', '<i class="podlove-icon-minus"></i>');
define('ASSET_STATUS_ERROR', '<i class="clickable podlove-icon-remove"></i>');
?>

<div id="asset_validation">
	<input id="revalidate_assets" type="button" class="button button-primary" value="<?php echo __( 'Revalidate Assets', 'podlove-podcasting-plugin-for-wordpress' ); ?>">

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
						<td class="media_file_status" data-media-file-id="<?php echo $file ? $file['media_file_id'] : '' ?>">
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
.media_file_status {
	text-align: center;
	font-weight: bold; 
	font-size: 20px;
}
</style>