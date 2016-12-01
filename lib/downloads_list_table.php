<?php
namespace Podlove;

use Podlove\Jobs\DownloadTimedAggregatorJob;
use Podlove\Model\Job;

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
			"<a href=\"?page=%s&action=show&episode=%d\">%s</a> %s",
			'podlove_analytics',
			$episode['id'],
			'<span class="dashicons dashicons-chart-bar"></span> ' . $episode['title'],
			'<span style="color:#999; font-size: smaller" title="' . esc_attr(mysql2date(get_option('date_format'), $episode['post_date'])) . '">'
			. sprintf(__('%s ago'), human_time_diff(strtotime($episode['post_date']))) 
			. '</span>'
		);
	}

	public function column_downloads( $episode ) {
		return self::get_number_or_dash($episode['downloads']);
	}

	public function column_default($item, $column_name)
	{
		$aggregation_columns = self::aggregation_columns();

		if (in_array($column_name, $aggregation_columns)) {

			// completed aggregate number
			if (is_numeric($item[$column_name]) && $item[$column_name]) {
				return number_format_i18n($item[$column_name]);	
			}

			// show grayed out total as temporary number
			$group = DownloadTimedAggregatorJob::current_time_group($item);
			if ($column_name == $group) {
				return '<span style="color:#999;">(' . self::get_number_or_dash($item['downloads']) . ')</span>';
			}

			// otherwise a dash -
			return "–";
		}
	}

	public static function get_number_or_dash($value) {

		if (is_numeric($value) && $value)
			return number_format_i18n($value);	

		return "–";
	}

	public function get_columns() {
		return array(
			// 'episode'   => __('Episode', 'podlove-podcasting-plugin-for-wordpress'),
			'downloads' => __('Total', 'podlove-podcasting-plugin-for-wordpress'),
			'3y' => __('3y', 'podlove-podcasting-plugin-for-wordpress'),
			'2y' => __('2y', 'podlove-podcasting-plugin-for-wordpress'),
			'1y' => __('1y', 'podlove-podcasting-plugin-for-wordpress'),
			'3q' => __('3q', 'podlove-podcasting-plugin-for-wordpress'),
			'2q' => __('2q', 'podlove-podcasting-plugin-for-wordpress'),
			'1q' => __('1q', 'podlove-podcasting-plugin-for-wordpress'),
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
			'3y'        => ['3y', true],
			'2y'        => ['2y', true],
			'1y'        => ['1y', true],
			'3q'        => ['3q', true],
			'2q'        => ['2q', true],
			'1q'        => ['1q', true],
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
		$columns = array_keys(DownloadTimedAggregatorJob::groupings());
		array_shift($columns); // remove 'total' column

		return $columns;
	}

	public function single_row( $item ) {
		$hidden_columns = count(get_hidden_columns(get_current_screen()));
		$columns = count($this->get_columns()) - $hidden_columns;

		echo '<tr>';
		echo "<td colspan=\"{$columns}\">";
		echo $this->column_episode($item);
		echo "</td>";
		echo '</tr>';
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	public function prepare_items() {
		// number of items per page
		$per_page = get_user_meta( get_current_user_id(), podlove_episodes_per_page_option_name(), true);
		if (!$per_page) {
			$per_page = 20;
		}
	
		// define column headers
		$this->_column_headers = $this->get_column_info();

		$data = [];
		foreach (Model\Podcast::get()->episodes() as $episode) {
			$post = $episode->post();

			$data[] = [
				'days_since_release' => $episode->days_since_release(),
				'hours_since_release' => $episode->hours_since_release(),
				'id' => $episode->id,
				'title' => $post->post_title,
				'post_date' => $post->post_date,
				'downloads' => get_post_meta($post->ID, '_podlove_downloads_total', true),
				'3y' => get_post_meta($post->ID, '_podlove_downloads_3y', true),
				'2y' => get_post_meta($post->ID, '_podlove_downloads_2y', true),
				'1y' => get_post_meta($post->ID, '_podlove_downloads_1y', true),
				'3q' => get_post_meta($post->ID, '_podlove_downloads_3q', true),
				'2q' => get_post_meta($post->ID, '_podlove_downloads_2q', true),
				'1q' => get_post_meta($post->ID, '_podlove_downloads_1q', true),
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
		global $wpdb;

		if ($which !== 'bottom')
			return;

		?>
		<div class="alignleft actions">
			<em><?php echo $this->data_age() ?></em>
		</div>

<script type="text/javascript">
jQuery("#adv-settings input[type=checkbox]").on('change', function() {
	var visibleCols = jQuery("#adv-settings input[type=checkbox]:checked").length;
	jQuery("table.downloads td[colspan]").attr('colspan', visibleCols);
});
</script>
		<?php
	}

	private function data_age() {
		global $wpdb;

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

		$totals_cron = $get_cron_info("podlove_calc_download_sums");
		$prev_totals_job = Job::find_one_recent_finished_job('Podlove\Jobs\DownloadTimedAggregatorJob');

		echo sprintf(
			__('Analytics data is %s old.', 'podlove-podcasting-plugin-for-wordpress'), 
			human_time_diff(max($totals_cron['prev'], strtotime($prev_totals_job->updated_at)), time())
		);
		echo ' ';
		echo sprintf(
			__('Next update will be in %s.', 'podlove-podcasting-plugin-for-wordpress'), 
			human_time_diff(time(), $totals_cron['next'])
		);
	}
}
