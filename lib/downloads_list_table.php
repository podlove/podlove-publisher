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
			'podlove_analytics',
			$episode['id'],
			$episode['title'],
			'<span style="color:#999; font-size: smaller">'.
			sprintf(
				__("%s days ago", "podlove-podcasting-plugin-for-wordpress"), 
				Model\Episode::find_by_id($episode['id'])->days_since_release()
			) . '</span>'
		);
	}

	public function column_downloads( $episode ) {
		return self::get_number_or_dash($episode['downloads']);
	}

	public function column_default($item, $column_name)
	{
		$aggregation_columns = self::aggregation_columns();

		if (in_array($column_name, $aggregation_columns)) {
			return self::get_number_or_dash($item[$column_name]);
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
		// number of items per page
		$per_page = 20;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$data = [];
		foreach (Model\Podcast::get()->episodes() as $episode) {
			$post = $episode->post();

			$data[] = [
				'id' => $episode->id,
				'title' => $post->post_title,
				'post_date' => $post->post_date,
				'downloads' => get_post_meta($post->ID, '_podlove_downloads_total', true),
				'4w' => get_post_meta($post->ID, '_podlove_downloads_4w', true),
				'3w' => get_post_meta($post->ID, '_podlove_downloads_3w', true),
				'2w' => get_post_meta($post->ID, '_podlove_downloads_2w', true),
				'1w' => get_post_meta($post->ID, '_podlove_downloads_1w', true),
				'6d' => get_post_meta($post->ID, '_podlove_downloads_6d', true),
				'5d' => get_post_meta($post->ID, '_podlove_downloads_5d', true),
				'4d' => get_post_meta($post->ID, '_podlove_downloads_4d', true),
				'3d' => get_post_meta($post->ID, '_podlove_downloads_3d', true),
				'2d' => get_post_meta($post->ID, '_podlove_downloads_2d', true),
				'1d' => get_post_meta($post->ID, '_podlove_downloads_1d', true) 
			];
		}

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

	protected function extra_tablenav( $which ) {

		if ($which !== 'bottom')
			return;

		$get_cron_info = function($cron_name) {
			$next_cron = wp_next_scheduled($cron_name);
			$interval  = wp_get_schedules()[wp_get_schedule($cron_name)]['interval'];
			$prev_cron = $next_cron - $interval;

			return [
				'interval' => $interval,
				'next' => $next_cron,
				'prev' => $prev_cron
			];
		};

		$sums_cron   = $get_cron_info("podlove_calc_download_sums");
		$totals_cron = $get_cron_info("podlove_calc_download_totals");

		?>
		<div class="alignleft actions">	
			<em>
				<?php
				echo sprintf(
					__('Sums data is %s old. Next update will be in %s.', 'podlove-podcasting-plugin-for-wordpress'), 
					human_time_diff($sums_cron['prev'], time()),
					human_time_diff(time(), $sums_cron['next'])
				); ?>
			</em><br>
			<em>
				<?php
				echo sprintf(
					__('Totals data is %s old. Next update will be in %s.', 'podlove-podcasting-plugin-for-wordpress'), 
					human_time_diff($totals_cron['prev'], time()),
					human_time_diff(time(), $totals_cron['next'])
				); ?>
			</em>	
		</div>
		<?php
	}

}
