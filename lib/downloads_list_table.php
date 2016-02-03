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
			"<a href=\"?page=%s&action=show&episode=%d\">%s</a><br>%s",
			$_REQUEST['page'],
			$episode['id'],
			$episode['title'],
			date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($episode['post_date']))
		);
	}

	public function column_downloads( $episode ) {
		return self::get_number_or_dash($episode['downloads']);
	}
	public function column_downloads_peak( $episode ) {
		$downloads = self::get_number_or_dash($episode['downloads_peak']['downloads']);
		$date = $episode['downloads_peak']['theday'];

		if (!$episode['downloads_peak']['downloads']) {
			return $downloads;
		} else {
			return sprintf("%s %son %s%s", $downloads, '<span style="color: #999">', date(get_option('date_format'), strtotime($date)), '</span>');
		}
	}

	public function column_downloads_avg( $episode ) {
		return self::get_number_or_dash($episode['downloads_avg']);
	}

	public static function get_number_or_dash($value) {
		return is_numeric($value) && $value ? number_format_i18n($value) : "–";
	}

	public function get_columns(){
		return array(
			'episode'        => __( 'Episode', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloads'      => __( 'Total Downloads', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloads_peak' => __( 'Downloads on Peak Day', 'podlove-podcasting-plugin-for-wordpress' ),
			'downloads_avg'  => __( 'Average per Day', 'podlove-podcasting-plugin-for-wordpress' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'episode'        => array('episode', true),
			'downloads'      => array('downloads', true),
			'downloads_peak' => array('downloads_peak', true),
			'downloads_avg'  => array('downloads_avg', true)
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

		// $data = \Podlove\Cache\TemplateCache::get_instance()->cache_for('podlove_analytics_downloads_table', function() {
		global $wpdb;

		$sql = "
			SELECT
				e.id,
				p.post_title title,
				p.post_date post_date,
				COUNT(di.id) downloads
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

		$data = $wpdb->get_results($sql, ARRAY_A);
		// }, HOUR_IN_SECONDS);

		foreach ($data as $row_id => $row) {
			
			$days_since_release = (new \DateTime())->diff(new \DateTime($row['post_date']))->days;
			if ($days_since_release) {
				$data[$row_id]['downloads_avg'] = round($row['downloads'] / $days_since_release);
			} else {
				$data[$row_id]['downloads_avg'] = 0;
			}

			$peak = Model\DownloadIntentClean::peak_download_by_episode_id($row['id']);
			$data[$row_id]['downloads_peak'] = $peak;
		}

		$valid_order_keys = array(
			'post_date',
			'downloads',
			'downloads_peak',
			'downloads_avg'
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
