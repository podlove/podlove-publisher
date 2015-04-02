<?php
namespace Podlove\Modules\Networks\Settings;
use \Podlove\Modules\Networks\Model\PodcastList;

class PodcastLists {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		PodcastLists::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Lists',
			/* $menu_title */ 'Lists',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_list_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {

		if ( ! isset( $_REQUEST['list'] ) )
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
	 * Process form: save/update a list
	 */
	private function save() {
		if ( ! isset( $_REQUEST['list'] ) )
			return;

		$podcasts = array();
		foreach ($_POST['podlove_list']['podcasts'] as $podcast) {
			$podcasts[] = $podcast;
		}

		$_POST['podlove_list']['podcasts'] = json_encode( $podcasts );

		PodcastList::activate_network_scope();
		$list = PodcastList::find_by_id( $_REQUEST['list'] );
		$list->update_attributes( $_POST['podlove_list'] );
		PodcastList::deactivate_network_scope();
		
		$this->redirect( 'index', $list->id );
	}
	
	/**
	 * Process form: create a list
	 */
	private function create() {
		global $wpdb;

		$podcasts = array();
		foreach ($_POST['podlove_list']['podcasts'] as $podcast) {
			$podcasts[] = $podcast;
		}

		$_POST['podlove_list']['podcasts'] = json_encode( $podcasts );
		
		PodcastList::activate_network_scope();
		$list = new PodcastList;
		$list->update_attributes( $_POST['podlove_list'] );
		PodcastList::deactivate_network_scope();

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a list
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['list'] ) )
			return;

		PodcastList::activate_network_scope();
		PodcastList::find_by_id( $_REQUEST['list'] )->delete();
		PodcastList::deactivate_network_scope();
		
		$this->redirect( 'index' );
	}

	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $list_id = NULL ) {
		$page   = 'network/admin.php?page=' . $_REQUEST['page'];
		$show   = ( $list_id ) ? '&list=' . $list_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}

	public static function get_action_link( $list, $title, $action = 'edit', $class = 'link' ) {
		return sprintf(
			'<a href="?page=%s&amp;action=%s&amp;list=%s" class="%s">' . $title . '</a>',
			'podlove_settings_list_handle',
			$action,
			$list->id,
			$class
		);
	}

	private function view_template() {
		echo __( 'If you have configured a <a href="http://codex.wordpress.org/Create_A_Network">
				WordPress Network</a>, Podlove allows you to configure Podcast lists.', 'podlove' );
		$table = new \Podlove\Modules\Networks\PodcastList_List_Table();
		$table->prepare_items();
		$table->display();
	}

	private function new_template() {
		PodcastList::activate_network_scope();
		$list = new PodcastList;
		?>
		<h3><?php echo __( 'Add New list', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $list, 'create', __( 'Add New list', 'podlove' ) );
		PodcastList::deactivate_network_scope();
	}
	
	private function edit_template() {
		PodcastList::activate_network_scope();
		$list = PodcastList::find_by_id( $_REQUEST['list'] );
		echo '<h3>' . sprintf( __( 'Edit list: %s', 'podlove' ), $list->title ) . '</h3>';
		$this->form_template( $list, 'save' );
		PodcastList::deactivate_network_scope();
	}

	private function form_template( $list, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_list',
			'hidden'  => array(
				'list' => $list->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $list, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$list = $form->object;

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

			$wrapper->string( 'slug', array(
				'label'       => __( 'Slug', 'podlove' ),
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->image( 'logo', array(
				'label'        => __( 'Logo', 'podlove' ),
				'description'  => __( 'JPEG or PNG.', 'podlove' ),
				'html'         => array( 'class' => 'regular-text' ),
				'image_width'  => 300,
				'image_height' => 300
			) );

			$wrapper->string( 'url', array(
				'label'       => __( 'list URL', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$wrapper->callback( 'podcasts', array(
				'label'       => __( 'Podcasts', 'podlove' ),
				'callback'	  => function() use ( $list ) {
					$form_base_name = "podlove_list";
					?>
					<div id="podcast_lists">
						<table class="podlove_alternating" border="0" cellspacing="0">
							<thead>
								<tr>
									<th><?php echo __('Source', 'podlove') ?></th>
									<th><?php echo __('Podcast/URL', 'podlove') ?></th>
									<th style="width: 60px"><?php echo __('Remove', 'podlove') ?></th>
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
									<option value="wplist" selected><?php echo __('WordPress Network', 'podlove') ?></option>
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
						<script type="text/template" id="podcast-select-type-wplist">
						<select name="<?php echo $form_base_name ?>[podcasts][{{id}}][podcast]" class="podlove-podcast chosen-image">
							<?php
								foreach ( PodcastList::get_all_podcast_ids_ordered() as $blog_id => $podcast ) {
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
							var existing_podcasts = <?php echo ( is_null( $list->podcasts ) ? "[]" : $list->podcasts ); ?>;
							var podcasts = [];

							function update_chosen() {
								$(".chosen").chosen();
								$(".chosen-image").chosenImage();
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
										row_as_object.find('select.podlove-podcast-dropdown option[value="' + o.entry.type + '"]').attr('selected', 'selected');

										switch ( o.entry.type ) {
											default: case 'wplist':
												row_as_object.find('select.podlove-podcast option[value="' + o.entry.podcast + '"]').attr('selected', true);
											break;
										}

										o.row = row_as_object[0].outerHTML.replace(/\{\{id\}\}/g, i);

										i++;
									},
									onRowAdd: function(o) {
										o.row = o.row.replace(/\{\{id\}\}/g, i);

										row = $(".podcasts_table_body tr:last .podlove-podcast-dropdown").focus();

										update_chosen();
										row.change();
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
		if ( isset($_GET["action"]) AND $_GET["action"] == 'confirm_delete' AND isset( $_REQUEST['list'] ) ) {
			 PodcastList::activate_network_scope();
			 $list = PodcastList::find_by_id( $_REQUEST['list'] );
			 PodcastList::deactivate_network_scope();
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf( __( 'You selected to delete the list "%s". Please confirm this action.', 'podlove' ), $list->title ) ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link( $list, __( 'Delete list permanently', 'podlove' ), 'delete', 'button' ) ?>
					<?php echo self::get_action_link( $list, __( 'Don\'t change anything', 'podlove' ), 'keep', 'button-primary' ) ?>
				</p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Lists', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
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