<?php 
namespace Podlove\Modules\Contributors;
use \Podlove\Model;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';

	public static $taxonomy_name = 'podlove-contributors';

	public function load() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( self::$taxonomy_name . '_edit_form_fields', array( $this, 'custom_fields' ), 10, 2 );
		add_action( 'edited_' . self::$taxonomy_name , array( $this, 'save' ), 10, 2 );
	}

	public function register_taxonomy() {

		$labels = array(
		   'name'                       => __( 'Contributors', 'podlove' ),
		   'singular_name'              => __( 'Contributor', 'podlove' ),
		   'search_items'               => __( 'Search Contributors', 'podlove' ),
		   'popular_items'              => __( 'Popular Contributors', 'podlove' ),
		   'all_items'                  => __( 'All Contributors', 'podlove' ),
		   'edit_item'                  => __( 'Edit Contributor' , 'podlove'), 
		   'update_item'                => __( 'Update Contributor', 'podlove' ),
		   'add_new_item'               => __( 'Add New Contributor', 'podlove' ),
		   'new_item_name'              => __( 'New Contributor Name', 'podlove' ),
		   'separate_items_with_commas' => __( 'Separate Contributors with commas', 'podlove' ),
		   'add_or_remove_items'        => __( 'Add or remove Contributors', 'podlove' ),
		   'choose_from_most_used'      => __( 'Choose from the most used Contributors', 'podlove' ),
		   'menu_name'                  => __( 'Contributors', 'podlove' ),
		 ); 

		$args = array(
			'hierarchical'  => false,
			'labels'        => $labels,
			'show_ui'       => true,
			'show_tagcloud' => true,
			'query_var'     => true,
			'rewrite'       => array( 'slug' => 'contributor' ),
		);

		register_taxonomy( self::$taxonomy_name, 'podcast', $args );
	}

	public function custom_fields( $contributor, $taxonomy ) {

		$all_contributor_settings = get_option( 'podlove_contributors', array() );
		
		if ( ! isset( $all_contributor_settings[ $contributor->term_id ] ) )
			$all_contributor_settings[ $contributor->term_id ] = array();

		$settings = $all_contributor_settings[ $contributor->term_id ];
		$settings = wp_parse_args( $settings, array(
			'contributor_email' => ''
		) );
		
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="contributor_email">
					<?php echo __( 'E-Mail', 'podlove' ); ?>
				</label>
			</th>
			<td>
				<input type="text" value="<?php echo $settings['contributor_email'] ?>" class="large-text" id="contributor_email" name="contributor_email">
			</td>
		</tr>
		<?php
	}

	public function save( $term_id, $taxonomy_id ) {

		if ( ! isset( $_POST['contributor_email'] ) )
			return;

		$all_contributor_settings = get_option( 'podlove_contributors', array() );
		
		if ( ! isset( $all_contributor_settings[ $term_id ] ) )
			$all_contributor_settings[ $term_id ] = array();

		$all_contributor_settings[ $term_id ]['contributor_email'] = $_POST['contributor_email'];

		update_option( 'podlove_contributors', $all_contributor_settings );
	}

}