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

	public function column_default($item, $column_name)
	{
		$aggregation_columns = self::aggregation_columns();

		$get_prev_column_index = function($col) {
			$aggregation_columns = self::aggregation_columns();
			$column_index = array_search($col, $aggregation_columns);

			if ($column_index < 9) {
				return $aggregation_columns[$column_index + 1];
			}

			return null;
		};

		if (in_array($column_name, $aggregation_columns)) {

			$prev_column_index = $get_prev_column_index($column_name);

			$factor = 0;
			if ($prev_column_index) {
				$prev = $item[$prev_column_index];
				if ($prev > 0) {
					$factor = round($item[$column_name] / $prev * 100 - 100);
				}
			}

			$out = self::get_number_or_dash($item[$column_name]);

			if ($factor) {
				$out .= " <em style=\"color: #999\">+$factor%</em>";
			}

			return $out;
		}
	}

	public static function get_number_or_dash($value) {
		return is_numeric($value) && $value ? number_format_i18n($value) : "–";
	}

	public function get_columns(){
		return array(
			'episode'   => __('Episode', 'podlove-podcasting-plugin-for-wordpress'),
			'downloads' => __('Total', 'podlove-podcasting-plugin-for-wordpress'),
			'4w' => __('4w', 'podlove-podcasting-plugin-for-wordpress'),
			'3w' => __('3w', 'podlove-podcasting-plugin-for-wordpress'),
			'2w' => __('2w', 'podlove-podcasting-plugin-for-wordpress'),
			'1w' => __('1w', 'podlove-podcasting-plugin-for-wordpress'),
			'6d' => __('6d', 'podlove-podcasting-plugin-for-wordpress'),
			'5d' => __('5d', 'podlove-podcasting-plugin-for-wordpress'),
			'4d' => __('4d', 'podlove-podcasting-plugin-for-wordpress'),
			'3d' => __('3d', 'podlove-podcasting-plugin-for-wordpress'),
			'2d' => __('2d', 'podlove-podcasting-plugin-for-wordpress'),
			'1d' => __('1d', 'podlove-podcasting-plugin-for-wordpress')
		);
	}


	public function get_sortable_columns() {
		return [
			'episode'   => ['episode', true],
			'downloads' => ['downloads', true],
			'4w'        => ['4w', true],
			'3w'        => ['3w', true],
			'2w'        => ['2w', true],
			'1w'        => ['1w', true],
			'6d'        => ['6d', true],
			'5d'        => ['5d', true],
			'4d'        => ['4d', true],
			'3d'        => ['3d', true],
			'2d'        => ['2d', true],
			'1d'        => ['1d', true]
		];
	}

	public static function aggregation_columns() {
		return ['4w','3w','2w','1w','6d','5d','4d','3d','2d','1d'];
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

		global $wpdb;

		$aggregation_columns = self::aggregation_columns();

		$select = array_map(function ($column) {
			return sprintf('pm_%1$s.meta_value %1$s', $column);
		}, $aggregation_columns);
		$select = implode(",\n\t", $select);

		$join = array_map(function ($column) {
			global $wpdb;
			return sprintf('LEFT JOIN ' . $wpdb->postmeta . ' pm_%1$s ON pm_%1$s.post_id = p.ID AND pm_%1$s.meta_key = \'_podlove_downloads_%1$s\'', $column);
		}, $aggregation_columns);
		$join = implode("\n\t", $join);

		$sql = "
SELECT
	e.id,
	p.post_title title,
	p.post_date post_date,
	COUNT(di.id) downloads,
	$select
FROM
	" . Model\Episode::table_name() . " e
	JOIN " . $wpdb->posts . " p ON e.post_id = p.ID
	JOIN " . Model\MediaFile::table_name() . " mf ON e.id = mf.episode_id
	LEFT JOIN " . Model\DownloadIntentClean::table_name() . " di ON di.media_file_id = mf.id
	$join
WHERE
	p.post_status IN ('publish', 'private')
GROUP BY
	e.id
		";

		$data = $wpdb->get_results($sql, ARRAY_A);

		$valid_order_keys = array(
			'post_date',
			'downloads'
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
