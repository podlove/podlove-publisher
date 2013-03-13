<?php
namespace Podlove;

/**
 * Meta Box for Podcase Settings in Post Edit Screen.
 */
class Podcast_Post_Meta_Box {

	public function __construct() {
		add_action( 'save_post', array( $this, 'save_postdata' ) );
	}

	public static function add_meta_box() {
		add_meta_box(
			/* $id       */ 'podlove_podcast',
			/* $title    */ __( 'Podcast Episode', 'podlove' ),
			/* $callback */ '\Podlove\Podcast_Post_Meta_Box::post_type_meta_box_callback',
			/* $page     */ 'podcast',
			/* $context  */ 'normal',
			/* $priority */ 'high'
		);
	}

	/**
	 * Meta Box Template
	 */
	public static function post_type_meta_box_callback( $post ) {

		\Podlove\require_code_mirror();
		
		$post_id = $post->ID;

		$podcast = Model\Podcast::get_instance();
		$episode = Model\Episode::find_or_create_by_post_id( $post_id );
			
		wp_nonce_field( \Podlove\PLUGIN_FILE, 'podlove_noncename' );
		?>
		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $podcast->media_file_base_uri; ?>" />
		<style type="text/css">
		.podlove-div-wrapper-form > div > span > label {
			display: inline-block;
			padding: 15px 0 6px 0;
			font-size: 1.2em;
		}
		.podlove-div-wrapper-form textarea, .podlove-div-wrapper-form input, .podlove-div-wrapper-form select {
			margin: 0px;
			width: 100%;
		}
		.podlove-div-wrapper-form .character_counter {
			text-align: right;
		}
		</style>
		<div class="podlove-div-wrapper-form">
			<?php 
			$form_args = array(
				'context' => '_podlove_meta',
				'submit_button' => false,
				'form' => false
			);

			\Podlove\Form\build_for( $episode, $form_args, function ( $form ) use ( $podcast ) {
				$wrapper = new \Podlove\Form\Input\DivWrapper( $form );
				$episode = $form->object;

				$wrapper->text( 'subtitle', array(
					'label'       => __( 'Subtitle', 'podlove' ),
					'description' => '',
					'html'        => array(
						'class' => 'large-text',
						'rows'  => 1
					)
				));

				$wrapper->text( 'summary', array(
					'label'       => __( 'Summary', 'podlove' ),
					'description' => '',
					'html'        => array(
						'class' => 'large-text',
						'rows'  => 3
					)
				));

				// TODO: validate and parse
				$wrapper->string( 'duration', array(
					'label'       => __( 'Duration', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text' )
				));

				$asset_assignments = Model\AssetAssignment::get_instance();
				if ( $asset_assignments->image === 'manual' ) {
					$wrapper->string( 'cover_art', array(
						'label'       => __( 'Episode Cover Art URL', 'podlove' ),
						'description' => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
						'html'        => array( 'class' => 'regular-text' )
					));
				}

				if ( $asset_assignments->chapters === 'manual' ) {
					$wrapper->text( 'chapters', array(
						'label'       => __( 'Chapter Marks', 'podlove' ),
						'description' => __( 'One timepoint (hh:mm:ss[.mmm]) and the chapter title per line.', 'podlove' ),
						'html'        => array(
							'class'       => 'large-text code',
							'placeholder' => '00:00:00.000 Intro',
							'rows'        => max( 2, count( explode( "\n", $episode->chapters ) ) )
						)
					));
				}

				$wrapper->string( 'slug', array(
					'label'       => __( 'Episode Media File Slug', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text' )
				));

				// inactive until there is a way to deactivate it
				// $wrapper->checkbox( 'enable', array(
				// 	'label'       => __( 'Enable?', 'podlove' ),
				// 	'description' => __( 'Allow this episode to appear in podcast directories.', 'podlove' ),
				// 	'default'     => true
				// ));

				$wrapper->multiselect( 'episode_assets', Podcast_Post_Meta_Box::episode_assets_form( $episode ) );

				if ( \Podlove\get_setting( 'enable_episode_record_date' ) ) {
					$wrapper->string( 'record_date', array(
						'label'       => __( 'Record Date', 'podlove' ),
						'description' => '',
						'html'        => array( 'class' => 'regular-text' )
					));
				}

				if ( \Podlove\get_setting( 'enable_episode_publication_date' ) ) {
					$wrapper->string( 'publication_date', array(
						'label'       => __( 'Publication Date', 'podlove' ),
						'description' => '',
						'html'        => array( 'class' => 'regular-text' )
					));
				}

				do_action( 'podlove_episode_form', $wrapper, $episode );

			} );
			?>
		</div>

		<style type="text/css">
		.media_file_table {
			width: 100%;
			border-bottom: 1px solid #999;
		}
		.media_file_table th {
			text-align: left;
			border-bottom: 1px solid #999;
		}
		.media_file_table td {
			padding: 5px;
		}
		.media_file_table tr:nth-child(even) {
			background: #EAEAEA;
		}
		#update_all_media_files {
			display: inline-block;
			vertical-align: middle;
			padding: 15px 0 6px 0;
		}
		.base_url {
			color: #777;
			font-size: 0.9em;
		}
		.media_file_row .enable {
			text-align: center;
		}
		.subtitle_warning {
			float: left;
			font-weight: bold;
			padding-right: 10px;
		}
		.subtitle_warning .close {
			cursor: pointer;
		}
		</style>
		<?php
	}

	/**
	 * Fetch form data for EpisodeAssets multiselect.
	 * 
	 * @param  \Podlove\Model\Episode $episode
	 * @return array
	 */
	public static function episode_assets_form( $episode ) {
		$episode_assets = Model\EpisodeAsset::all();

		// field to generate option list
		$asset_options = array();
		// values for option list
		$asset_values = array();

		foreach ( $episode_assets as $asset ) {

			if ( ! $file_type = $asset->file_type() )
				continue;

			// get formats configured for this show
			$asset_options[ $asset->id ] = $asset->title;
			// find out which formats are active
			$asset_values[ $asset->id ] = NULL !== Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );
		}

		// FIXME: empty checkbox -> no file id
		// solution: when one checks the box, an AJAX request has to create and validate the file
		$episode_assets_form = array(
			'label'       => __( 'Media Files', 'podlove' ),
			'description' => '',
			'options'     => $asset_options,
			'default'      => true,
			'multi_values' => $asset_values,
			'before' => function() {
				?>
				<table class='media_file_table' border="0" cellspacing="0">
					<tr>
						<th><?php echo __( 'Enable', 'podlove' ) ?></th>
						<th><?php echo __( 'Asset', 'podlove' ) ?></th>
						<th><?php echo __( 'Asset File Name', 'podlove' ) ?></th>
						<th><?php echo __( 'Filesize', 'podlove' ) ?></th>
						<th><?php echo __( 'Status', 'podlove' ) ?></th>
						<th></th>
					</tr>
				<?php
			},
			'after' => function() {
				?>
				</table>
				<p>
					<span class="description">
						<?php echo __( 'Media File Base URL', 'podlove' ) . ': ' . \Podlove\Model\Podcast::get_instance()->media_file_base_uri; ?>
					</span>
				</p>
				<?php
			},
			'around_each' => function ( $callback ) {
				?>
				<tr class="media_file_row">
					<td class="enable">
					</td>
					<td class="asset">
						<?php call_user_func( $callback ); ?>
					</td>
					<td class="url"></td>
					<td class="size"></td>
					<td class="status"></td>
					<td class="update"></td>
				</tr>
				<?php
			},
			'multiselect_callback' => function ( $asset_id ) use ( $episode ) {
				$asset = \Podlove\Model\EpisodeAsset::find_by_id( $asset_id );
				$format   = $asset->file_type();
				$file     = \Podlove\Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );
				
				$attributes = array(
					'data-template'  => \Podlove\Model\Podcast::get_instance()->get_url_template(),
					'data-extension' => $format->extension,
					'data-size' => ( is_object( $file ) ) ? $file->size : 0,
					'data-suffix' => $asset->suffix,
					'data-episode-asset-id' => $asset->id,
					'data-episode-id' => $episode->id
				);

				if ( $file )
					$attributes['data-id'] = $file->id;

				$out = '';
				foreach ( $attributes as $key => $value ) {
					$out .= sprintf( '%s="%s" ', $key, $value );
				}

				return $out;
			}
		);

		if ( empty( $asset_options ) ) {
			$episode_assets_form['description'] =
				sprintf(
					'<span style="color: red">%s</span>',
					__( 'You need to configure assets for this show. No assets, no fun.', 'podlove' )
				)
				. ' '
			    . sprintf(
			    	'<a href="%s">%s</a>',
			    	admin_url( 'admin.php?page=podlove_episode_assets_settings_handle' ),
			    	__( 'Configure Assets', 'podlove' )
			    );
		}

		return $episode_assets_form;
	}

	/**
	 * Save post data on WordPress callback.
	 * 
	 * @param  int $post_id
	 */
	public function save_postdata( $post_id ) {
		global $wpdb;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		
		if ( empty( $_POST['podlove_noncename'] ) || ! wp_verify_nonce( $_POST['podlove_noncename'], \Podlove\PLUGIN_FILE ) )
			return;
		
		// Check permissions
		if ( 'podcast' == $_POST['post_type'] ) {
		  if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
		} else {
			return;
		}

		if ( ! isset( $_POST['_podlove_meta'] ) || ! is_array( $_POST['_podlove_meta'] ) )
			return;

		do_action( 'podlove_save_episode', $post_id, $_POST['_podlove_meta'] );

		// save changes
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$episode_slug_has_changed = isset( $_POST['_podlove_meta']['slug'] ) && $_POST['_podlove_meta']['slug'] != $episode->slug;
		$episode->update_attributes( $_POST['_podlove_meta'] );

		if ( $episode_slug_has_changed )
			$episode->refetch_files();

		// copy chapter info into custom meta for webplayer compatibility
		update_post_meta( $post_id, '_podlove_chapters', $episode->chapters );

		if ( isset( $_REQUEST['_podlove_meta']['episode_assets'] ) )
			$this->save_episode_assets( $episode, $_REQUEST['_podlove_meta']['episode_assets'] );
		else 
			$this->save_episode_assets( $episode, array() );

		\Podlove\clear_all_caches(); // mainly for feeds
	}

	/**
	 * Save episode assets based on checkbox data.
	 *
	 * @param \Podlove\Model\Episode $episode
	 * @param  array $checkbox_data Raw form data for checkboxes.
	 *               Contains 'on' for checked boxes and no entry at all for unchecked ones.
	 */
	function save_episode_assets( $episode, $checkbox_data ) {

		// create array where the keys are asset_ids and values false
		$assets = array_map(
			function( $_ ){ return false; },
			array_flip(
				array_map(
					function( $l ) { return $l->id; },
					Model\EpisodeAsset::all()
				)
			)
		);

		// set those assets to true where the checkbox is set
		foreach ( $assets as $id => $_ ) {
			if ( isset( $checkbox_data[ $id ] ) && $checkbox_data[ $id ] === 'on' ) {
				$assets[ $id ] = true;
			}
		}

		// create new ones, delete unchecked ones
		foreach ( $assets as $episode_asset_id => $episode_asset_value ) {
			$file = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $episode_asset_id );

			if ( $file === NULL && $episode_asset_value ) {
				$file = new Model\MediaFile();
				$file->episode_id = $episode->id;
				$file->episode_asset_id = $episode_asset_id;
				$file->save();
			} elseif ( $file !== NULL && ! $episode_asset_value ) {
				$file->delete();
			}
		}
	}

}