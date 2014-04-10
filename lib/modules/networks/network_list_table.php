<?php
namespace Podlove\Modules\Networks;
use \Podlove\Modules\Networks\Model\Network;

class Network_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'network',   // singular name of the listed records
		    'plural'    => 'networks',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	public function column_title( $network ) {
		$actions = array(
			'edit'   => Settings\Networks::get_action_link( $network, __( 'Edit', 'podlove' ) ),
			'delete' => Settings\Networks::get_action_link( $network, __( 'Delete', 'podlove' ), 'confirm_delete' )
		);
	
		return sprintf( '%1$s %2$s',
		    Settings\Networks::get_action_link( $network, $network->title ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="network_id" value="' . $network->id . '">';;
	}

	public function column_logo( $network ) {
		if( $network->logo == "" ) {
			return;
		} else {
			return "<img src='" . $network->logo . "' title='" . $network->title . "' alt='" . $network->title . "' />";
		}
	}	

	public function column_url( $network ) {
		return $network->url;
	}

	public function column_podcasts( $network ) {
		$list = '';
		foreach ( explode( ',', $network->podcasts ) as $podcast_id ) {
			$podcast = Network::fetch_podcast_by_id( $podcast_id );
			$list = $list . "<a href='" . site_url() . "'>" .  $podcast->title . "</a>, ";
		}
		return substr( $list, 0, -2 );
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
		$items = \Podlove\Modules\Networks\Model\Network::all();

		uasort( $items, function ( $a, $b ) {
			return strnatcmp( $a->title, $b->title );
		});

		$this->items = $items;
	}
}
