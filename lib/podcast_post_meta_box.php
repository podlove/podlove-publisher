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

		// Move all "advanced" metaboxes above the default editor
		add_action('edit_form_after_title', function() {
		    global $post, $wp_meta_boxes;
		    do_meta_boxes(get_current_screen(), 'advanced', $post);
		    unset($wp_meta_boxes[get_post_type($post)]['advanced']);
		});
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
				'key'  => 'duration',
				'options' => array(
					'label'       => __( 'Duration', 'podlove' ),
					'description' => '',
					'html'        => array( 'class' => 'regular-text podlove-check-input' )
				),
				'position' => 400
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
			'duration'       => FILTER_SANITIZE_STRING,
			'guid'           => FILTER_SANITIZE_STRING,
		];
		$episode_data_filter = apply_filters('podlove_episode_data_filter', $episode_data_filter);

		$episode_data = filter_var_array($episode_data, $episode_data_filter);

		// save changes
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$episode->update_attributes($episode_data);
	}

}