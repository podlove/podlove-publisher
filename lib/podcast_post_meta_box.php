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

		$post_id = $post->ID;

		$podcast = Model\Podcast::get_instance();
		$episode = Model\Episode::find_or_create_by_post_id( $post_id );
			
		wp_nonce_field( \Podlove\PLUGIN_FILE, 'podlove_noncename' );
		?>
		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $podcast->media_file_base_uri; ?>" />
		<table class="form-table">
			<?php 
			$form_args = array(
				'context' => '_podlove_meta',
				'submit_button' => false,
				'form' => false
			);

			\Podlove\Form\build_for( $episode, $form_args, function ( $form ) use ( $podcast ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
				$episode = $form->object;

				$wrapper->checkbox( 'active', array(
					'label'       => __( 'Post Episode to Show', 'podlove' ), // todo: hide/show rest of the form
					'description' => '',
					'default'  => true
				));

				$wrapper->string( 'slug', array(
					'label'       => __( 'Episode Media File Slug', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text' )
				));

				// TODO: validate and parse
				$wrapper->string( 'duration', array(
					'label'       => __( 'Duration', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text' )
				));

				$wrapper->string( 'subtitle', array(
					'label'       => __( 'Subtitle', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'large-text' )
				));

				$wrapper->text( 'summary', array(
					'label'       => __( 'Summary', 'podlove' ),
					'description' => '',
					'html'        => array(
						'class' => 'large-text',
						'rows'  => max( 2, count( explode( "\n", $episode->summary ) ) )
					)
				));

				if ( $podcast->supports_cover_art === 'manual' ) {
					$wrapper->string( 'cover_art', array(
						'label'       => __( 'Episode Cover Art URL', 'podlove' ),
						'description' => __( 'JPEG or PNG. At least 1400 x 1400 pixels.', 'podlove' ),
						'html'        => array( 'class' => 'regular-text' )
					));
				}

				$wrapper->text( 'chapters', array(
					'label'       => __( 'Chapter Marks', 'podlove' ),
					'description' => __( 'One timepoint (hh:mm:ss[.mmm]) and the chapter title per line.', 'podlove' ),
					'html'        => array(
						'class'       => 'large-text code',
						'placeholder' => '00:00:00.000 Intro',
						'rows'        => max( 2, count( explode( "\n", $episode->chapters ) ) )
					)
				));

				$wrapper->checkbox( 'enable', array(
					'label'       => __( 'Enable?', 'podlove' ),
					'description' => __( 'Allow this episode to appear in podcast directories.', 'podlove' ),
					'default'     => true
				));

				// TODO: button to update
				// TODO: pretty display
				// TODO: don't display link
				// TODO: display last modified from header
				$wrapper->multiselect( 'media_locations', Podcast_Post_Meta_Box::media_locations_form( $episode ) );

			} );
			?>
		</table>
		<?php
	}

	/**
	 * Fetch form data for MediaLocations multiselect.
	 * 
	 * @param  \Podlove\Model\Episode $episode
	 * @return array
	 */
	public static function media_locations_form( $episode ) {
		$media_locations = Model\MediaLocation::all();

		// field to generate option list
		$location_options = array();
		// values for option list
		$location_values = array();

		foreach ( $media_locations as $location ) {

			if ( ! $media_format = $location->media_format() )
				continue;

			// get formats configured for this show
			$location_options[ $location->id ] = $location->title;
			// find out which formats are active
			$location_values[ $location->id ] = NULL !== Model\MediaFile::find_by_episode_id_and_media_location_id( $episode->id, $location->id );
		}

		// FIXME: empty checkbox -> no file id
		// solution: when one checks the box, an AJAX request has to create and validate the file
		$media_locations_form = array(
			'label'       => __( 'Media Files', 'podlove' ),
			'description' => '',
			'options'     => $location_options,
			'default'      => true,
			'multi_values' => $location_values,
			'multiselect_callback' => function ( $location_id ) use ( $episode ) {
				$location = \Podlove\Model\MediaLocation::find_by_id( $location_id );
				$format   = $location->media_format();
				$file     = \Podlove\Model\MediaFile::find_by_episode_id_and_media_location_id( $episode->id, $location->id );
				
				$attributes = array(
					'data-template'  => $location->url_template,
					'data-extension' => $format->extension,
					'data-size' => ( is_object( $file ) ) ? $file->size : 0,
					'data-media-location-id' => $location->id,
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

		if ( empty( $location_options ) ) {
			$media_locations_form['description'] =
				sprintf(
					'<span style="color: red">%s</span>',
					__( 'You need to configure feeds for this show. No feeds, no fun.', 'podlove' )
				)
				. ' '
			    . sprintf(
			    	'<a href="%s">%s</a>',
			    	admin_url( 'admin.php?page=podlove_shows_settings_handle&action=edit&show=' . $show->id ),
			    	__( 'Edit this show', 'podlove' )
			    );
		}

		return $media_locations_form;
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

		// save changes
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$episode->update_attributes( $_POST['_podlove_meta'] );

		// copy chapter info into custom meta for webplayer compatibility
		update_post_meta( $post_id, '_podlove_chapters', $episode->chapters );

		if ( isset( $_REQUEST['_podlove_meta']['media_locations'] ) )
			$this->save_media_locations( $episode, $_REQUEST['_podlove_meta']['media_locations'] );
		else 
			$this->save_media_locations( $episode, array() );
	}

	/**
	 * Save media locations based on checkbox data.
	 *
	 * @param \Podlove\Model\Episode $episode
	 * @param  array $checkbox_data Raw form data for checkboxes.
	 *               Contains 'on' for checked boxes and no entry at all for unchecked ones.
	 */
	function save_media_locations( $episode, $checkbox_data ) {

		// create array where the keys are location_ids and values false
		$locations = array_map(
			function( $_ ){ return false; },
			array_flip(
				array_map(
					function( $l ) { return $l->id; },
					Model\MediaLocation::all()
				)
			)
		);

		// set those location to true where the checkbox is set
		foreach ( $locations as $id => $_ ) {
			if ( isset( $checkbox_data[ $id ] ) && $checkbox_data[ $id ] === 'on' ) {
				$locations[ $id ] = true;
			}
		}

		// create new ones, delete unchecked ones
		foreach ( $locations as $media_location_id => $media_location_value ) {
			$file = Model\MediaFile::find_by_episode_id_and_media_location_id( $episode->id, $media_location_id );

			if ( $file === NULL && $media_location_value ) {
				$file = new Model\MediaFile();
				$file->episode_id = $episode->id;
				$file->media_location_id = $media_location_id;
				$file->save();
			} elseif ( $file !== NULL && ! $media_location_value ) {
				$file->delete();
			}
		}
	}

}