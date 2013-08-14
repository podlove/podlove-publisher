<?php 
namespace Podlove\Modules\Contributors;
use \Podlove\Model;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';
	protected $module_group = 'metadata';

	public static $taxonomy_name = 'podlove-contributors';

	public function load() {

		// register taxonomy
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		// add custom fields
		add_action( self::$taxonomy_name . '_edit_form_fields', array( $this, 'custom_fields' ), 10, 2 );
		add_action( 'edited_' . self::$taxonomy_name , array( $this, 'save' ), 10, 2 );

		// add custom meta box to manage taxonomy
		add_action( 'admin_menu', function () {
			remove_meta_box( 'tagsdiv-' . \Podlove\Modules\Contributors\Contributors::$taxonomy_name, 'podcast', 'normal' ); 
		} );

		add_action( 'add_meta_boxes', function () {
			add_meta_box( 'tagsdiv-' . \Podlove\Modules\Contributors\Contributors::$taxonomy_name, __( 'Contributors', 'podlove' ), array( $this, 'metabox' ), 'podcast', 'side', 'default' );  
		});

		add_action( 'admin_init', function () {
			wp_enqueue_script( 'jquery-ui-autocomplete' );
		} );

		add_action( 'admin_print_styles', array( $this, 'scripts_and_styles' ) );

		add_shortcode( 'podlove-contributors', array( $this, 'shortcode' ) );
	}

	public function scripts_and_styles() {

		wp_register_script( 'podlove-contributors-admin-script', $this->get_module_url() . '/js/admin.js', array( 'jquery-ui-autocomplete' ) );
		wp_enqueue_script( 'podlove-contributors-admin-script' );

		wp_register_style( 'podlove-contributors-admin-style', $this->get_module_url() . '/css/admin.css' );
		wp_enqueue_style( 'podlove-contributors-admin-style' );
	}

	public function shortcode( $attributes ) {

		$post_id = get_the_ID();
		$contributors = get_the_terms( $post_id, self::$taxonomy_name );
		
		if ( ! $contributors )
			return;

		$defaults = array(
			'separator' => ', '
		);

		$attributes = shortcode_atts( $defaults, $attributes );

		$html  = '';
		$html .= '<span class="podlove-contributors">';
		$html .= implode( $attributes['separator'], array_map( function ( $contributor ) {
			$settings = \Podlove\Modules\Contributors\Contributors::get_additional_settings( $contributor->term_id );
			$avatar = isset( $settings['contributor_email'] ) ? get_avatar( $settings['contributor_email'], 18 ) : get_avatar( null, 18 );
			return
				'<span class="contributor">'
				. $avatar
				. ' '
				. $contributor->name
				. '</span>';
		}, $contributors ) );
		$html .= '</span>';

		$html .= '<style type="text/css">';
		$html .= '.podlove-contributors .contributor img {';
		$html .= '	margin: 0px;';
		$html .= '	vertical-align: text-bottom;';
		$html .= '}';
		$html .= '</style>';
		
		return apply_filters( 'podlove_contributors_shortcode', $html );
	}

	public function metabox( $post ) {
		
		$contributors = get_the_terms( $post->ID, self::$taxonomy_name );
		?>
		<div id="add_contributors" class="tagsdiv">
			<p>
				<input type="text" class="newtag" id="add_contributors_input">
				<input type="button" class="button tagadd" id="add_contributors_submit" value="Add">
			</p>
		</div>
		<div id="contributors" class="tagchecklist">
			<div class="nojs-tags hide-if-js">
				<p><?php echo __( 'Add or remove contributors', 'podlove' ) ?></p>
				<textarea name="tax_input[<?php echo self::$taxonomy_name ?>]" rows="3" cols="20" class="the-contributors" id="tax-input-podlove-contributors"><?php 
				if ( $contributors && count( $contributors ) ) {
					echo implode( ',', array_map(function($c){return $c->slug;}, $contributors) );
				}
				?></textarea>
			</div>
			<?php if ( $contributors && count( $contributors ) ): ?>
				<?php foreach ( $contributors as $contributor ): ?>
					<?php $settings = self::get_additional_settings( $contributor->term_id ) ?>
					<div class="contributor" data-term-slug="<?php echo $contributor->slug ?>" data-term-id="<?php echo $contributor->term_id ?>">
						<span>
							<a href="#" class="ntdelbutton" title="<?php echo __( 'remove', 'podlove' ) ?>">x</a>
							<div class="avatar">
								<?php if ( isset( $settings['contributor_email'] ) ): ?>
									<?php echo get_avatar( $settings['contributor_email'], 24 ); ?>
								<?php else: ?>
									<?php echo get_avatar( null, 24 ); ?>
								<?php endif; ?>
							</div>
							<div class="name">
								<a href="<?php echo get_edit_term_link( $contributor->term_id, self::$taxonomy_name, 'podcast' ) ?>" target="_blank" title="<?php echo __( 'edit', 'podlove' ) ?>">
									<?php echo $contributor->name ?>
								</a>
							</div>
						</span>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<script type="text/javascript">
		<?php 
		$people = get_terms(self::$taxonomy_name, array('hide_empty' => false) );
		$people = array_map( function( $person ) {
			$settings = \Podlove\Modules\Contributors\Contributors::get_additional_settings( $person->term_id );
			$email = isset( $settings['contributor_email'] ) ? $settings['contributor_email'] : null;
			return array(
				'value'  => $person->slug,
				'label'  => $person->name,
				'id'     => $person->term_id,
				'avatar' => \Podlove\Modules\Contributors\Contributors::get_gravatar_url( $email, 24 )
			);
		}, $people );
		if ( ! $people )
			$people = array();
		?>

		var PODLOVE = PODLOVE || {};
		PODLOVE.people = <?php echo json_encode($people); ?>;
		</script>
		<?php
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

	public static function get_additional_settings( $term_id ) {

		$all_contributor_settings = get_option( 'podlove_contributors', array() );
		
		if ( ! isset( $all_contributor_settings[ $term_id ] ) )
			$all_contributor_settings[ $term_id ] = array();

		return $all_contributor_settings[ $term_id ];
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

	/**
	 * Save settings for a single contributor.
	 * 
	 * @param  int $term_id    
	 * @param  int $taxonomy_id
	 */
	public function save( $term_id, $taxonomy_id ) {

		if ( ! isset( $_POST['contributor_email'] ) )
			return;

		$all_contributor_settings = get_option( 'podlove_contributors', array() );
		
		if ( ! isset( $all_contributor_settings[ $term_id ] ) )
			$all_contributor_settings[ $term_id ] = array();

		$all_contributor_settings[ $term_id ]['contributor_email'] = $_POST['contributor_email'];

		update_option( 'podlove_contributors', $all_contributor_settings );
	}

	/**
	 * Get Gravatar URL for a specified email address.
	 *
	 * Yes, I know there is get_avatar() but that returns the img tag and I need the URL.
	 *
	 * @param string $email The email address
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
	 * @return String containing either just a URL or a complete image tag
	 * @source http://gravatar.com/site/implement/images/php/
	 */
	public static function get_gravatar_url( $email, $s = 80, $d = 'mm', $r = 'g', $atts = array() ) {
		
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		return $url;
	}

}