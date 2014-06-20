<?php
namespace Podlove\Modules\Networks\Settings;
use \Podlove\Modules\Networks\Model\Network;

class Networks {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Networks::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Lists',
			/* $menu_title */ 'Lists',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_network_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {

		if ( ! isset( $_REQUEST['network'] ) )
			return;

		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;

		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		
		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}


	/**
	 * Process form: save/update a network
	 */
	private function save() {
		if ( ! isset( $_REQUEST['network'] ) )
			return;

		$podcasts = array();
		foreach ($_POST['podlove_network']['podcasts'] as $podcast) {
			$podcasts[] = $podcast;
		}

		$_POST['podlove_network']['podcasts'] = json_encode( $podcasts );

		$network = Network::find_by_id( $_REQUEST['network'] );
		$network->update_attributes( $_POST['podlove_network'] );
		
		$this->redirect( 'index', $network->id );
	}
	
	/**
	 * Process form: create a network
	 */
	private function create() {
		global $wpdb;
		
		self::manage_multiselect();
		$network = new Network;
		$network->update_attributes( $_POST['podlove_network'] );
		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a network
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['network'] ) )
			return;

		Network::find_by_id( $_REQUEST['network'] )->delete();
		
		$this->redirect( 'index' );
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $network_id = NULL ) {
		$page   = 'network/admin.php?page=' . $_REQUEST['page'];
		$show   = ( $network_id ) ? '&network=' . $network_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}

	public static function get_action_link( $network, $title, $action = 'edit', $class = 'link' ) {
		return sprintf(
			'<a href="?page=%s&amp;action=%s&amp;network=%s" class="%s">' . $title . '</a>',
			'podlove_settings_network_handle',
			$action,
			$network->id,
			$class
		);
	}

	private function view_template() {
		echo __( 'If you have configured a <a href="http://codex.wordpress.org/Create_A_Network">
				WordPress Network</a>, Podlove allows you to configure Podcast networks.', 'podlove' );
		$table = new \Podlove\Modules\Networks\Network_List_Table();
		$table->prepare_items();
		$table->display();
	}

	private function new_template() {
		$network = new Network;
		?>
		<h3><?php echo __( 'Add New Network', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $network, 'create', __( 'Add New Network', 'podlove' ) );
	}
	
	private function edit_template() {
		$network = Network::find_by_id( $_REQUEST['network'] );
		echo '<h3>' . sprintf( __( 'Edit Network: %s', 'podlove' ), $network->title ) . '</h3>';
		$this->form_template( $network, 'save' );
	}

	private function form_template( $network, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_network',
			'hidden'  => array(
				'network' => $network->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $network, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$network = $form->object;

			$wrapper->string( 'title', array(
				'label'       => __( 'Title', 'podlove' ),
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->string( 'subtitle', array(
				'label'       => __( 'Subtitle', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->text( 'description', array(
				'label'       => __( 'Description', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html'        => array( 'rows' => 3, 'cols' => 40, 'class' => 'autogrow' )
			) );

			$wrapper->image( 'logo', array(
				'label'        => __( 'Logo', 'podlove' ),
				'description'  => __( 'JPEG or PNG.', 'podlove' ),
				'html'         => array( 'class' => 'regular-text' ),
				'image_width'  => 300,
				'image_height' => 300
			) );

			$wrapper->string( 'url', array(
				'label'       => __( 'Network URL', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->callback( 'podcasts', array(
				'label'       => __( 'Podcasts', 'podlove' ),
				'callback'	  => function() use ( $network ) {
					$form_base_name = "podlove_network";
					?>
					<div id="podcast_lists">
						<table class="podlove_alternating" border="0" cellspacing="0">
							<thead>
								<tr>
									<th>Podcast Source</th>
									<th>Podcast/URL</th>
									<th style="width: 60px">Remove</th>
									<th style="width: 30px"></th>
								</tr>
							</thead>
							<tbody class="podcasts_table_body" style="min-height: 50px;">
								<tr class="podcasts_table_body_placeholder" style="display: none;">
									<td><em><?php echo __('No Podcasts were added yet.', 'podlove') ?></em></td>
								</tr>
							</tbody>
						</table>

						<div id="add_new_podcasts_wrapper">
							<input class="button" id="add_new_podcast" value="+" type="button" />
						</div>

						<script type="text/template" id="podcast-row-template">
						<tr class="media_file_row podlove-podcast-table" data-id="{{id}}">
							<td class="podlove-podcast-column">
								<select name="<?php echo $form_base_name ?>[podcasts][{{id}}][type]" class="podlove-podcast-dropdown">
									<option value=""><?php echo __('Select Source', 'podlove') ?></option>
									<option value="wpnetwork"><?php echo __('WordPress Network', 'podlove') ?></option>
								</select>
							</td>
							<td class="podlove-podcast-value"></td>
							<td>
								<span class="podcast_remove">
									<i class="clickable podlove-icon-remove"></i>
								</span>
							</td>
							<td class="move column-move"><i class="reorder-handle podlove-icon-reorder"></i></td>
						</tr>
						</script>
						<script type="text/template" id="podcast-select-type-wpnetwork">
						<select name="<?php echo $form_base_name ?>[podcasts][{{id}}][podcast]" class="podlove-podcast chosen-image">
							<?php
								foreach ( Network::all_podcasts_ordered() as $blog_id => $podcast ) {
									if ( $podcast->title )
										printf( "<option value='%s' data-img-src='%s'>%s</option>\n", $blog_id, $podcast->cover_image ,$podcast->title );
								}
							?>
						</select>
						</script>
					</div>

					<script type="text/javascript">

						var PODLOVE = PODLOVE || {};

						(function($) {
							var i = 0;
							var existing_podcasts = <?php echo $network->podcasts; ?>;
							var podcasts = [];

							function update_chosen() {
								$(".chosen").chosen();
								$(".chosen-image").chosenImage();
							}

							function podcast_dropdown_handler() {
								$('select.podlove-podcast-dropdown').change(function() {
									row = $(this).closest("tr");
									podcast_source = $(this).val();

									// Check for empty podcast / for new field
									if( podcast_source === '' ) {
										row.find(".podlove-podcast-value").html(""); // Empty podcast column and hide edit button
										row.find(".podlove-podcast-edit").hide();
										return;
									}

									template_id = "#podcast-select-type-" + podcast_source;
									template = $( template_id ).html();
									template = template.replace(/\{\{id\}\}/g, row.data('id') );

									row.find(".podlove-podcast-value").html( template );
									update_chosen();

									i++; // continue using "i" which was already used to add the existing contributions
								});
							}

							$(document).ready(function() {
								$("#podcast_lists table").podloveDataTable({
									rowTemplate: "#podcast-row-template",
									deleteHandle: ".podcast_remove",
									sortableHandle: ".reorder-handle",
									addRowHandle: "#add_new_podcast",
									data: existing_podcasts,
									dataPresets: podcasts,
									onRowLoad: function(o) {
										template_id = "#podcast-select-type-" + o.entry.type;
										template = $( template_id ).html();
										row_as_object = $(o.row)
										
										row_as_object.find(".podlove-podcast-value").html( template );
										row_as_object.find('select.podlove-podcast-dropdown option[value="' + o.entry.type + '"]').attr('selected',true);

										switch ( o.entry.type ) {
											default: case 'wpnetwork':
												row_as_object.find('select.podlove-podcast option[value="' + o.entry.podcast + '"]').attr('selected',true);
											break;
										}

										o.row = row_as_object[0].outerHTML;
										o.row = o.row.replace(/\{\{id\}\}/g, i);

										i++;
									},
									onRowAdd: function(o) {
										o.row = o.row.replace(/\{\{id\}\}/g, i);

										podcast_dropdown_handler();
										update_chosen();
									},
									onRowDelete: function(tr) {
										
									}
								});
							});

						}(jQuery));

					</script>
					<?php	
				}
			) );

		} );
	}

	function page() {
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['network'] ) ) {
			 $network = Network::find_by_id( $_REQUEST['network'] );
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the network "%s". Please confirm this action.', 'podlove' ), $network->title ) ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $network, __( 'Delete network permanently', 'podlove' ), 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $network, __( 'Don\'t change anything', 'podlove' ), 'keep', 'button-primary' ) ?>
				</p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Networks', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
				if(isset($_GET["action"])) {
					switch ( $_GET["action"] ) {
						case 'new':   $this->new_template();  break;
						case 'edit':  $this->edit_template(); break;
						default:      $this->view_template(); break;
					}
				} else {
					$this->view_template();
				}
			?>
		</div>	
		<?php
	}
}