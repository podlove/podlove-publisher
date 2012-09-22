<?php 
namespace Podlove\Modules\Contributors;
use \Podlove\Model;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';

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
			add_meta_box( 'tagsdiv-' . \Podlove\Modules\Contributors\Contributors::$taxonomy_name, __( 'Contributors', 'podlove' ), array( '\Podlove\Modules\Contributors\Contributors', 'metabox' ), 'podcast', 'side', 'default' );  
		});

		add_action( 'admin_init', function () {
			wp_enqueue_script( 'jquery-ui-autocomplete' );
		} );
	}

	public function metabox( $post ) {
		
		$contributors = get_the_terms( $post->ID, self::$taxonomy_name );
		?>
		<div id="add_contributors" class="tagsdiv">
			<p>
				<input type="text" class="newtag" id="add_contributors_input">
				<input type="button" class="button tagadd" value="Add">
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
							<a href="#" class="ntdelbutton">x</a>
							<div class="avatar">
								<?php echo get_avatar( $settings['contributor_email'], 24 ); ?>
							</div>
							<div class="name">
								<?php echo $contributor->name ?>
							</div>
						</span>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<style type="text/css">
		.contributor {
			clear: both;
			height: 24px;
			padding: 4px 0px;
		}
		.contributor > span {
			line-height: 24px;
		}
		.contributor > span a {
			margin-top: 8px;
		}
		.contributor .avatar {
			width:24px;
			height:24px;
			margin: 0px 4px;
			float: left;
			/*overflow: hidden;*/
		}
		.contributor .avatar img {
			border:1px solid #999;
		}
		.contributor .name {
			font-size: 14px;
			float: left;
			margin-left: 8px;
		}
		</style>
		<script type="text/javascript">
			jQuery(function($){

				$("#contributors").on("click", ".contributor a.ntdelbutton", function(e) {
					e.preventDefault();

					var $contributor = $(this).closest(".contributor");

					// actually remove contributor
					var slug = $contributor.data('termSlug');

					var $data_field = $("#tax-input-podlove-contributors");
					var contributors = $data_field.val().split(",");

					// remove by slug
					contributors.splice(contributors.indexOf(slug),1);
					$data_field.val(contributors.join(","));

					// visurally remove contributor
					$contributor.remove();

					return false;
				});

				var people = [
					{
						value: "eric-teubert",
						label: "Eric Teubert",
						id: 5,
						avatar: "http://0.gravatar.com/avatar/4cd08b6372aec4ca88d2decef27ea991?s=24&d=http%3A%2F%2F0.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D24&r=G"
					},
					{
						value: "tim-pritlove",
						label: "Tim Pritlove",
						id: 4,
						avatar: "http://1.gravatar.com/avatar/97391367796db965d19e63b690e72b3d?s=24&d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D24&r=G"
					}
				];

				$("#add_contributors_input").autocomplete({
					minLength: 0,
					source: people,
					focus: function(event, ui) {
						$("#add_contributors_input").val(ui.item.label);

						return false;
					},
					select: function(event, ui) {

						// visually add contributor
						$("#add_contributors_input").val('');

						var tpl = '';
						tpl += '<div class="contributor" data-term-slug="' + ui.item.value + '" data-term-id="' + ui.item.id + '">';
						tpl += '	<span>';
						tpl += '		<a href="#" class="ntdelbutton">x</a>';
						tpl += '		<div class="avatar">';
						tpl += '			<img src="' + ui.item.avatar + '" class="avatar avatar-24 photo" height="24" width="24">';
						tpl += '		</div>';
						tpl += '		<div class="name">';
						tpl += '			' + ui.item.label;
						tpl += '		</div>';
						tpl += '	</span>';
						tpl += '</div>';

						$("#contributors").append(tpl);

						// actually add contributor
						var $data_field = $("#tax-input-podlove-contributors");
						var contributors = $data_field.val();

						if (!contributors.length) {
							contributors = ui.item.value;
						} else {
							contributors = contributors + "," + ui.item.value;	
						}
						
						$data_field.val(contributors);

						return false;
					}
				});
			});
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

}