<?php
namespace Podlove;

class Feed_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'feed',   // singular name of the listed records
		    'plural'    => 'feeds',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	public function column_name( $feed ) {

		$link = function ( $title, $action = 'edit' ) use ( $feed ) {
			return sprintf(
				'<a href="?page=%s&action=%s&feed=%s">' . $title . '</a>',
				$_REQUEST['page'],
				$action,
				$feed->id
			);
		};

		$actions = array(
			'edit'   => $link( __( 'Edit', 'podlove' ) ),
			'delete' => $link( __( 'Delete', 'podlove' ), 'delete' )
		);
	
		return sprintf( '%1$s %2$s',
		    $link( $feed->name ),
		    $this->row_actions( $actions )
		);
	}
	
	public function column_discoverable( $feed ) {
		return $feed->discoverable ? '✓' : '×';
	}

	public function column_url( $feed ) {
		return $feed->get_subscribe_link();
	}

	public function column_format( $feed ) {
		return $feed->format;
	}

	public function column_media( $feed ) {
		$episode_asset = $feed->episode_asset();

		return ( $episode_asset ) ? $episode_asset->title() : __( 'not set', 'podlove' );
	}

	public function get_columns(){
		$columns = array(
			'name'         => __( 'Feed', 'podlove' ),
			'url'          => __( 'Subscribe URL', 'podlove' ),
			'format'       => __( 'Format', 'podlove' ),
			'media'        => __( 'Media', 'podlove' ),
			'discoverable' => __( 'Discoverable', 'podlove' )
		);
		return $columns;
	}
	
	public function prepare_items() {
		// number of items per page
		$per_page = 10;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		$data = \Podlove\Model\Feed::all();
		
		// get current page
		$current_page = $this->get_pagenum();
		// get total items
		$total_items = count( $data );
		// extrage page for current page only
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ) , $per_page );
		// add items to table
		$this->items = $data;
		
		// register pagination options & calculations
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page,
		    'total_pages' => ceil( $total_items / $per_page )
		) );
	}

}
