<?php
namespace Podlove;

/**
 * Meta Box for Podcase Settings in Post Edit Screen.
 */
class Podcast_Post_Meta_Box {

	public function __construct() {
		add_action( 'save_post', array( $this, 'save_postdata' ) );
		add_action( 'save_post_podcast', function($post_id, $post, $_) {
			if ($episode = Model\Episode::find_one_by_where('post_id = ' . intval($post_id)))
				do_action( 'podlove_episode_content_has_changed', $episode->id );
		}, 10, 3);
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
		
		$post_id = $post->ID;

		$podcast = Model\Podcast::get();
		$episode = Model\Episode::find_or_create_by_post_id( $post_id );
			
		wp_nonce_field( \Podlove\PLUGIN_FILE, 'podlove_noncename' );
		?>

		<?php do_action('podlove_episode_meta_box_start'); ?>

		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $podcast->media_file_base_uri; ?>" />
		<div class="podlove-div-wrapper-form">
			<?php 
			$form_args = array(
				'context' => '_podlove_meta',
				'submit_button' => false,
				'form' => false,
				'is_table' => false
			);

			$form_data = self::get_form_data($episode);

			\Podlove\Form\build_for( $episode, $form_args, function ( $form ) use ( $podcast, $form_data ) {
				$wrapper = new \Podlove\Form\Input\DivWrapper( $form );
				$episode = $form->object;

				foreach ($form_data as $entry) {
					$wrapper->{$entry['type']}($entry['key'], $entry['options']);
				}

			} );
			?>
		</div>

		<?php do_action('podlove_episode_meta_box_end'); ?>

		<?php
	}

	private static function get_form_data($episode) {
		$form_data = array(
			array(
				'type' => 'string',
				'key'  => 'title',
				'options' => array(
					'label'       => __( 'Title', 'podlove' ),
					'description' => '',
					'html'        => array(
						'readonly' => 'readonly',
						'class'    => 'podlove-check-input'
					)
				),
				'position' => 1100
			), array(
				'type' => 'text',
				'key'  => 'subtitle',
				'options' => array(
					'label'       => __( 'Subtitle', 'podlove' ),
					'description' => '',
					'html'        => array(
						'class' => 'large-text autogrow podlove-check-input',
						'rows'  => 1
					)
				),
				'position' => 1000
			), array(
				'type' => 'text',
				'key'  => 'summary',
				'options' => array(
					'label'       => __( 'Summary', 'podlove' ),
					'description' => '',
					'html'        => array(
						'class' => 'large-text autogrow podlove-check-input',
						'rows'  => 3
					)
				),
				'position' => 900
			), array(
				'type' => 'string',
				'key'  => 'slug',
				'options' => array(
					'label'       => __( 'Episode Media File Slug', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text podlove-check-input' )
				),
				'position' => 510
			), array(
				'type' => 'string',
				'key'  => 'duration',
				'options' => array(
					'label'       => __( 'Duration', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text podlove-check-input' )
				),
				'position' => 400
			), array(
				'type' => 'multiselect',
				'key'  => 'episode_assets',
				'options' => Podcast_Post_Meta_Box::episode_assets_form( $episode ),
				'position' => 300
			)
		);

		// allow modules to add / change the form
		$form_data = apply_filters('podlove_episode_form_data', $form_data, $episode);

		// sort entities by position
		// TODO first sanitize position attribute, then I don't have to check on each comparison
		usort($form_data, array(__CLASS__, 'compare_by_position'));

		return $form_data;
	}

	public static function compare_by_position($a, $b) {
		$pos_a = isset($a['position']) ? (int) $a['position'] : 0;
		$pos_b = isset($b['position']) ? (int) $b['position'] : 0;

		if ($a == $b || $pos_a == $pos_b)
			return 0;

		return ($pos_a < $pos_b) ? 1 : -1;
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
						<?php echo __( 'Media File Base URL', 'podlove' ) . ': ' . \Podlove\Model\Podcast::get()->media_file_base_uri; ?>
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
				$format = $asset->file_type();
				$file = \Podlove\Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $asset->id );
				$size = is_object($file) ? (int) $file->size : 0;
				if ($size === 1) {
					$size = "unknown";
				}

				$attributes = array(
					'data-template'  => \Podlove\Model\Podcast::get()->get_url_template(),
					'data-size' => $size,
					'data-episode-asset-id' => $asset->id,
					'data-episode-id' => $episode->id,
					'data-file-url' => ( is_object( $file ) ) ? $file->get_file_url() : ''
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
		if ( 'podcast' !== $_POST['post_type'] || !current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['_podlove_meta'] ) || ! is_array( $_POST['_podlove_meta'] ) )
			return;

		do_action( 'podlove_save_episode', $post_id, $_POST['_podlove_meta'] );

		// sanitize data
		$episode_data = filter_input_array(INPUT_POST, [
			'_podlove_meta' => [ 'flags' => FILTER_REQUIRE_ARRAY ]
		]);
		$episode_data = $episode_data['_podlove_meta'];

		$episode_data_filter = [
			'title'          => [ 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES, 'filter' => FILTER_SANITIZE_STRING ],
			'subtitle'       => [ 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES, 'filter' => FILTER_SANITIZE_STRING ],
			'summary'        => [ 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES, 'filter' => FILTER_SANITIZE_STRING ],
			'slug'           => FILTER_SANITIZE_STRING,
			'duration'       => FILTER_SANITIZE_STRING,
			'episode_assets' => [ 'flags' => FILTER_REQUIRE_ARRAY, 'filter' => FILTER_SANITIZE_STRING ],
			'guid'           => FILTER_SANITIZE_STRING,
		];
		$episode_data_filter = apply_filters('podlove_episode_data_filter', $episode_data_filter);

		$episode_data = filter_var_array($episode_data, $episode_data_filter);

		// save changes
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$episode_slug_has_changed = isset( $episode_data['slug'] ) && $episode_data['slug'] != $episode->slug;
		$episode->update_attributes( $episode_data );

		if ( $episode_slug_has_changed )
			$episode->refetch_files();

		if ( isset( $episode_data['episode_assets'] ) )
			$this->save_episode_assets( $episode, $episode_data['episode_assets'] );
		else 
			$this->save_episode_assets( $episode, array() );
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