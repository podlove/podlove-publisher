<?php
namespace Podlove\Modules\Seasons\Settings;

use \Podlove\Modules\Seasons\Model\Season;

class SeasonListTable extends \Podlove\List_Table {

	function __construct(){
		parent::__construct( array(
		    'singular'  => 'season',   // singular name of the listed records
		    'plural'    => 'seasons',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	public function column_title($season) {

		$link = function ( $title, $action = 'edit' ) use ( $season ) {
			return sprintf(
				'<a href="?page=%s&action=%s&season=%s">' . $title . '</a>',
				$_REQUEST['page'],
				$action,
				$season->id
			);
		};

		$actions = [
			'edit'   => $link( __( 'Edit', 'podlove' ) ),
			'delete' => $link( __( 'Delete', 'podlove' ), 'delete' )
		];
	
		return sprintf(
			'%1$s %2$s',
		    $link($season->title()),
		    $this->row_actions($actions)
		);
	}

	public function column_time($season) {
		$start = $season->start_date();
		$end   = $season->end_date();

		if (!$end)
			return sprintf(__('since %s (currently running)', 'podlove'), $start);

		return $start . ' ' . __('until', 'podlove') . ' ' . $end;
	}

	public function column_episodes($season) {
		$episodes = $season->episodes();

		$count = count($episodes);
		$first = reset($episodes);
		$last  = end($episodes);

		$totals = function($count) {
			return '<br><span style="font-size: 1.6em; vertical-align: middle; padding: 5px 10px; display: inline-block;">&#x2193;</span> <small>total: ' . $count . ' episodes</small><br>';
		};

		$link = function($episode) {
			return '<a href="' . get_edit_post_link($episode->post_id) . '">' . $episode->title() . '</a>';
		};

		return $link($first) . $totals($count) . $link($last);
	}

	public function get_columns(){
		return array(
			'title' => __( 'Season', 'podlove' ),
			'time' => __( 'Time', 'podlove' ),
			'episodes' => __( 'Episodes', 'podlove' )
		);
	}

	public function prepare_items() {
		// number of items per page
		$per_page = 50;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		$data = Season::find_all_by_where("1 = 1 ORDER BY start_date ASC");

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