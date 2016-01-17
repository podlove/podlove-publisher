<?php
namespace Podlove\Modules\Networks;
use \Podlove\Modules\Networks\Model\PodcastList;

class PodcastList_List_Table extends \Podlove\List_Table {
	
	function __construct() {
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct(array(
		    'singular'  => 'list',   // singular name of the listed records
		    'plural'    => 'lists',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		));
	}
	
	public function column_title($list) {
		$actions = array(
			'edit'   => Settings\PodcastLists::get_action_link( $list, __( 'Edit', 'podlove-podcasting-plugin-for-wordpress' ) ),
			'delete' => Settings\PodcastLists::get_action_link( $list, __( 'Delete', 'podlove-podcasting-plugin-for-wordpress' ), 'confirm_delete' )
		);
	
		return sprintf( '%1$s %2$s',
		    Settings\PodcastLists::get_action_link( $list, $list->title ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="list_id" value="' . $list->id . '">';
	}

	public function column_logo($list) {
		if ($list->logo == "") {
			return;
		} else {
			return "<img src='" . $list->logo . "' title='" . $list->title . "' alt='" . $list->title . "' />";
		}
	}	

	public function column_url($list) {
		return "<a href='" . $list->url . "'>" . $list->url . "</a>";
	}

	public function column_podcasts($list) {
		return implode(', ', array_map(function($podcast) {
			return $this->podcast_admin_link($podcast);
		}, $list->podcasts()));
	}

	public function podcast_admin_link($podcast) {
		return sprintf(
			'<a href="%s">%s</a>', get_admin_url($podcast->blog_id), $podcast->title
		);
	}

	public function get_columns() {
		return [
			'logo'     => __('Logo'    , 'podlove-podcasting-plugin-for-wordpress'),
			'title'    => __('Title'   , 'podlove-podcasting-plugin-for-wordpress'),
			'url'      => __('URL'     , 'podlove-podcasting-plugin-for-wordpress'),
			'podcasts' => __('Podcasts', 'podlove-podcasting-plugin-for-wordpress')
		];
	}

	/**
	 * @override
	 */
	public function display() {
		parent::display();
		?>
		<style type="text/css">
		/* avoid mouseover jumping */
		#permanentcontributor { width: 160px; }
		</style>
		<?php
	}

	public function prepare_items() {

		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = false;
		$this->_column_headers = array( $columns, $hidden, $sortable );
		PodcastList::activate_network_scope();
		$items = \Podlove\Modules\Networks\Model\PodcastList::all();
		PodcastList::deactivate_network_scope();

		uasort( $items, function ( $a, $b ) {
			return strnatcmp( $a->title, $b->title );
		});

		$this->items = $items;
	}

	public function no_items() {
		?>
		<div style="margin: 20px 10px 10px 5px">
	 		<span class="add-new-h2" style="background: transparent">
			<?php _e( 'No items found.' ); ?>
			</span>
			<a href="?page=podlove_settings_list_handle&action=new" class="add-new-h2">
	 		<?php _e( 'Add New' ) ?>
	 		</a>
	 	</div>
	 	<?php
	 }
}
