<?php
namespace Podlove;

/**
 * Add custom GUID to episodes.
 * Display in all podcast feeds.
 */
class Custom_Guid {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_insert_post', array( __CLASS__, 'generate_guid_for_episodes' ), 10, 2 );
		add_filter( 'get_the_guid', array( __CLASS__, 'override_wordpress_guid' ), 100 );
		add_filter( 'podlove_episode_form_data', array( __CLASS__, 'add_guid_form_element' ), 10, 2 );
		add_action( 'podlove_save_episode', array( __CLASS__, 'save_form'), 10, 2 );
	}

	public static function add_guid_form_element( $form_data, $episode )
	{
		$form_data[] = array(
			'type' => 'callback',
			'key'  => 'GUID',
			'options' => array(
				'label'    => 'GUID',
				'callback' => function() use ( $episode ) {
				?>
				<div>
					<span id="guid_preview"><?php echo get_the_guid() ?></span>
					<a href="#" id="regenerate_guid"><?php echo __( 'regenerate', 'podlove' ) ?></a>
				</div>
				<span class="description">
					<?php echo __( 'Identifier for this episode. Change it to force podcatchers to redownload media files for this episode.', 'podlove' ) ?>
				</span>

				<input type="hidden" name="_podlove_meta[guid]" id="_podlove_meta_guid" value="<?php echo get_the_guid() ?>">

				<script type="text/javascript">
				jQuery(function($){
					$("#regenerate_guid").on('click', function(e) {
						e.preventDefault();

						var data = {
							action: 'podlove-get-new-guid',
							post_id: jQuery("#post_ID").val()
						};

						$.ajax({
							url: ajaxurl,
							data: data,
							dataType: 'json',
							success: function(result) {
								if (result && result.guid) {
									$("#_podlove_meta_guid").val(result.guid);
									$("#guid_preview").html(result.guid);
									if ( ! $(".guid_warning").length ) {
										$(".row__podlove_meta_guid .description")
											.append("<br><strong class=\"guid_warning\">GUID regenerated. You still need to save the post.<br>Only regenerate if you messed up and need all clients to redownload all files!</strong>");
									}
								} else {
									alert("Sorry, couldn't generate new GUID.");
								}
							}
						});

						return false;
					});
				});
				</script>
				<?php
			} ),
			'position' => 100
		);

		return $form_data;
	}

	public static function save_form( $post_id, $form_data ) {
		
		if ( isset( $form_data[ 'guid' ] ) )
			update_post_meta( $post_id, '_podlove_guid', $form_data[ 'guid' ] );
	}

	/**
	 * When an episode is created, generate and save a custom guid.
	 *
	 * @wp-hook wp_insert_post
	 * @param  int $post_id
	 * @param  object $post
	 */
	public static function generate_guid_for_episodes( $post_id, $post ) {
		
		if ( $post->post_type !== 'podcast' )
			return;

		if ( get_post_meta( $post->ID, '_podlove_guid', true ) )
			return;

		$guid = self::guid_for_post( $post );
		update_post_meta( $post->ID, '_podlove_guid', $guid );
	}

	/**
	 * Generate a guid for a WordPress post object.
	 *
	 * @param  object $post
	 * @return string The GUID.
	 */
	public static function guid_for_post( $post ) {

		$segments = array();

		$segments[] = apply_filters( 'podlove_guid_prefix', 'podlove' );
		$segments[] = apply_filters( 'podlove_guid_time', gmdate( 'c' ) );
		$hash = substr( sha1( $post->ID . $post->post_title . time() ), 0, 15 );
		$segments[] = apply_filters( 'podlove_guid_hash', $hash );

		return apply_filters( 'podlove_guid', strtolower( implode( '-', $segments ) ) );
	}

	/**
	 * Whenever our GUID is available, use it. Fallback to WordPress GUID.
	 *
	 * @wp-hook get_the_guid
	 * @param  string $guid WordPress GUID
	 * @return string
	 */
	public static function override_wordpress_guid( $guid ) {
		global $post;

		if ( is_object( $post ) && $podlove_guid = get_post_meta( $post->ID, '_podlove_guid', true ) )
			return $podlove_guid;

		return $guid;
	}

}