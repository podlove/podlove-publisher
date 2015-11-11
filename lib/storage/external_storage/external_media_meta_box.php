<?php
namespace Podlove\Storage\ExternalStorage;

use \Podlove\Model;

class ExternalMediaMetaBox {

	public function __construct() {
		add_action('add_meta_boxes_podcast', [$this, 'add_meta_box']);
		add_action('save_post_podcast', [$this, 'save_post']);
	}

	public function add_meta_box() {
		add_meta_box(
			/* $id       */ 'podlove_podcast_media_files',
			/* $title    */ __( 'Podcast Media Files', 'podlove' ),
			/* $callback */ [$this, 'meta_box_callback'],
			/* $page     */ 'podcast',
			/* $context  */ 'advanced',
			/* $priority */ 'high'
		);
	}

	public function save_post($post_id)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
			return;
		
		if (empty($_POST['podlove_noncename']) || !wp_verify_nonce($_POST['podlove_noncename'], \Podlove\PLUGIN_FILE))
			return;
		
		if ('podcast' !== $_POST['post_type'])
			return;

		if (!current_user_can('edit_post', $post_id))
			return;

		if (!isset($_POST['_podlove_meta']))
			return;

		// sanitize data
		$data = filter_input_array(INPUT_POST, [
			'_podlove_meta' => [ 'flags' => FILTER_REQUIRE_ARRAY ]
		]);
		$data = $data['_podlove_meta'];

		$episode_data_filter = [
			'slug'           => FILTER_SANITIZE_STRING,
			'episode_assets' => [ 'flags' => FILTER_REQUIRE_ARRAY, 'filter' => FILTER_SANITIZE_STRING ],
		];

		$data = filter_var_array($data, $episode_data_filter);

		// save changes
		$episode = Model\Episode::find_or_create_by_post_id($post_id);
		$episode_slug_has_changed = isset($data['slug']) && $data['slug'] != $episode->slug;
		$episode->update_attributes($data);

		if ($episode_slug_has_changed)
			$episode->refetch_files();

		if (isset($data['episode_assets']))
			$this->save_episode_assets($episode, $data['episode_assets']);
		else 
			$this->save_episode_assets($episode, []);
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

	public function meta_box_callback($post) {
		
		$post_id = $post->ID;

		$podcast = Model\Podcast::get();
		$episode = Model\Episode::find_or_create_by_post_id( $post_id );
			
		?>

		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $podcast->media_file_base_uri; ?>" />
		<div class="podlove-div-wrapper-form">
			<?php 
			$form_args = array(
				'context' => '_podlove_meta',
				'submit_button' => false,
				'form' => false,
				'is_table' => false
			);

			$form_data = [
				array(
					'type' => 'string',
					'key'  => 'slug',
					'options' => array(
						'label'       => __( 'Episode Media File Slug', 'podlove' ),
						'description' => '',
						'html'        => array( 'class' => 'regular-text podlove-check-input' )
					),
					'position' => 510
				), $this->episode_form($episode)
			];

			\Podlove\Form\build_for( $episode, $form_args, function ( $form ) use ( $podcast, $form_data ) {
				$wrapper = new \Podlove\Form\Input\DivWrapper( $form );
				$episode = $form->object;

				foreach ($form_data as $entry) {
					$wrapper->{$entry['type']}($entry['key'], $entry['options']);
				}

			} );
			?>
		</div>

<script type="text/javascript">
(function($) {

function enhance_slug_field() {
	var slug = $("#_podlove_meta_slug")
		base_uri = $("[name=show-media-file-base-uri]").val()
		;

	slug.before('<span id="_podlove_meta_slug_prefix" class="podlove-input-prepend"></span>');
	slug.after('<span id="_podlove_meta_slug_suffix" class="podlove-input-append"></span>');

	var prefix = $("#_podlove_meta_slug_prefix"),
		suffix = $("#_podlove_meta_slug_suffix")
		;

	prefix.html(base_uri);
	suffix.html(".ext");

	slug
		.css({width: '30px'})
		.attr({autocorrect: 'off'})
		.autoGrowInput({ minWidth: 30, maxWidth: 200, comfortZone: 30 })
		.blur();
}

$(document).ready(function () {
	enhance_slug_field();
});

}(jQuery));
</script>
		<?php
	}

	public function episode_form($episode) {
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
				<!-- 
				<p>
					<span class="description">
						<?php echo __( 'Media File Base URL', 'podlove' ) . ': ' . \Podlove\Model\Podcast::get()->media_file_base_uri; ?>
					</span>
				</p>
				 -->
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

		return [
			'type' => 'multiselect',
			'key'  => 'episode_assets',
			'options' => $episode_assets_form,
			'position' => 300
		];
	}
}