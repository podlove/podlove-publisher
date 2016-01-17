<?php
namespace Podlove;

class Downloads_List_Table extends \Podlove\List_Table {

	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'download',   // singular name of the listed records
		    'plural'    => 'downloads',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	public function column_episode( $episode ) {
		return sprintf(
			"<a href=\"?page=%s&action=show&episode=%d\">%s</a>",
			$_REQUEST['page'],
			$episode['id'],
			$episode['title']
		);
	}

	public function column_downloads( $episode ) {
		return self::get_number_or_dash($episode['downloads']);
	}

	public function column_downloadsMonth( $episode ) {
		return self::get_number_or_dash($episode['downloadsMonth']);
	}

	public function column_downloadsWeek( $episode ) {
		return self::get_number_or_dash($episode['downloadsWeek']);
	}

	public function column_downloadsYesterday( $episode ) {
		return self::get_number_or_dash($episode['downloadsYesterday']);
	}

	public function column_downloadsToday( $episode ) {
		return self::get_number_or_dash($episode['downloadsToday']);
	}

	public static function get_number_or_dash($value) {
		return is_numeric($value) && $value ? number_format_i18n($value) : "–";
	}

	public function get_columns(){
		return array(
			'episode'            => __( 'Episode', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloads'          => __( 'Total Downloads', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloadsMonth'     => __( '28 Days', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloadsWeek'      => __( '7 Days', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloadsYesterday' => __( 'Yesterday', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloadsToday'     => __( 'Today', 'podlove-podcasting-plugin-for-wordpress' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'episode'            => array('episode', true),
			'downloads'          => array('downloads', true),
			'downloadsMonth'     => array('downloadsMonth', true),
			'downloadsWeek'      => array('downloadsWeek', true),
			'downloadsYesterday' => array('downloadsYesterday', true),
			'downloadsToday'     => array('downloadsToday', true),
		);
	}	

	public function prepare_items() {
		global $wpdb;

		// number of items per page
		$per_page = 20;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$data = \Podlove\Cache\TemplateCache::get_instance()->cache_for('podlove_analytics_downloads_table', function() {
			global $wpdb;

			// retrieve data
			$subSQL = function($start = null, $end = null) {

				$strToMysqlDate = function($s) { return date('Y-m-d', strtotime($s)); };

				if ($start && $end) {
					$timerange = " AND di2.accessed_at BETWEEN '{$strToMysqlDate($start)}' AND '{$strToMysqlDate($end)}'";
				} elseif ($start) {
					$timerange = " AND DATE(di2.accessed_at) = '{$strToMysqlDate($start)}'";
				} else {
					$timerange = "";
				}

				return "
					SELECT
						COUNT(di2.id) downloads
					FROM
						" . Model\MediaFile::table_name() . " mf2
						LEFT JOIN " . Model\DownloadIntentClean::table_name() . " di2 ON di2.media_file_id = mf2.id
					WHERE
						mf2.episode_id = e.id
						$timerange
				";
			};

			$sql = "
				SELECT
					e.id,
					p.post_title title,
					p.post_date post_date,
					COUNT(di.id) downloads,
					(" . $subSQL('28 days ago', 'now') . ") downloadsMonth,
					(" . $subSQL('7 days ago', 'now') . ") downloadsWeek,
					(" . $subSQL('1 day ago') . ") downloadsYesterday,
					(" . $subSQL('now') . ") downloadsToday
				FROM
					" . Model\Episode::table_name() . " e
					JOIN " . $wpdb->posts . " p ON e.post_id = p.ID
					JOIN " . Model\MediaFile::table_name() . " mf ON e.id = mf.episode_id
					LEFT JOIN " . Model\DownloadIntentClean::table_name() . " di ON di.media_file_id = mf.id
				WHERE
					p.post_status IN ('publish', 'private')
				GROUP BY
					e.id
			";

			return $wpdb->get_results($sql, ARRAY_A);
		}, HOUR_IN_SECONDS);

		$valid_order_keys = array(
			'post_date',
			'downloads',
			'downloadsMonth',
			'downloadsWeek',
			'downloadsYesterday',
			'downloadsToday'
		);

		// look for order options
		if ( isset($_GET['orderby']) && in_array($_GET['orderby'], $valid_order_keys) ) {
			$orderby = $_GET['orderby'];
		} else {
			$orderby = 'post_date';
		}

		// look how to sort
		if( isset($_GET['order'])  ) {
			$order = strtoupper($_GET['order']) == 'ASC' ? SORT_ASC : SORT_DESC;
		} else{
			$order = SORT_DESC;
		}

		array_multisort(
			\array_column($data, $orderby), $order,
			$data
		);

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
