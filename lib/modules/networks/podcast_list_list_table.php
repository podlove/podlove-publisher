<?php
namespace Podlove\Modules\Networks;
use \Podlove\Modules\Networks\Model\PodcastList;

class PodcastList_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'list',   // singular name of the listed records
		    'plural'    => 'lists',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	public function column_title( $list ) {
		$actions = array(
			'edit'   => Settings\PodcastLists::get_action_link( $list, __( 'Edit', 'podlove' ) ),
			'delete' => Settings\PodcastLists::get_action_link( $list, __( 'Delete', 'podlove' ), 'confirm_delete' )
		);
	
		return sprintf( '%1$s %2$s',
		    Settings\PodcastLists::get_action_link( $list, $list->title ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="list_id" value="' . $list->id . '">';
	}

	public function column_logo( $list ) {
		if( $list->logo == "" ) {
			return;
		} else {
			return "<img src='" . $list->logo . "' title='" . $list->title . "' alt='" . $list->title . "' />";
		}
	}	

	public function column_url( $list ) {
		return $list->url;
	}

	public function column_podcasts( $list ) {
		$podcasts = $list->get_podcasts();
		$podcasts_as_string = "";

		foreach ($podcasts as $podcast_list_key => $podcast ) {
			$podcasts_as_string .= '<a href="' . get_home_url( $podcast->blog_id ) .'">' . $podcast->title . '</a>' . ( $podcast_list_key == count( $podcasts ) - 1 ? "" : ", " );
		}

		return $podcasts_as_string;
	}

	public function get_columns(){
		$columns = array(
			'logo'             => __( 'Logo', 'podlove' ),
			'title'           => __( 'Title', 'podlove' ),
			'url'                 => __( 'URL', 'podlove' ),
			'podcasts'                 => __( 'Podcasts', 'podlove' )
		);

		return $columns;
	}

	public function search_form() {
		?>
		<form method="post">
		  <?php $this->search_box('search', 'search_id'); ?>
		</form>
		<?php
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
		$items = \Podlove\Modules\Networks\Model\PodcastList::all();

		uasort( $items, function ( $a, $b ) {
			return strnatcmp( $a->title, $b->title );
		});

		$this->items = $items;
	}
}
