<?php
namespace Podlove\Settings;

use \Podlove\Model;

class Analytics {
	
	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __( 'Analytics', 'podlove' ),
			/* $menu_title */ __( 'Analytics', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_analytics',
			/* $function   */ array( $this, 'page' )
		);

		// add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'admin_init', array( $this, 'scripts_and_styles' ) );	
	}

	public function scripts_and_styles() {
		if ( ! isset( $_REQUEST['page'] ) )
			return;

		if ( $_REQUEST['page'] != 'podlove_analytics' )
			return;

		// wp_register_script('podlove-highcharts-js', \Podlove\PLUGIN_URL . '/js/highcharts.js', array('jquery'));
		// wp_enqueue_script('podlove-highcharts-js');
	}

	public function page() {

		$days = 30;

		$start = "$days days ago";
		$end   = "now";

		$startDay = date('Y-m-d', strtotime($start));
		$endDay   = date('Y-m-d', strtotime($end));

		$top_episode_ids = Model\DownloadIntent::top_episode_ids($start, $end);

		$days_data_from_query_result = function($totals) use ($start, $endDay) {
			
			$dayTotals = array();
			foreach ($totals as $download) {
				$dayTotals[$download->theday] = $download->downloads;
			}

			$days = array();
			$day = 0;

			do {
				$currentDay = date('Y-m-d', strtotime($start . " +$day days"));

				if (isset($dayTotals[$currentDay])) {
					$days[$currentDay] = $dayTotals[$currentDay];
				} else {
					$days[$currentDay] = 0;	
				}

				$day++;
			} while ($currentDay < $endDay);

			return $days;
		};

		$top_episode_data = array();
		foreach ($top_episode_ids as $episode_id) {
			$totals = Model\DownloadIntent::daily_episode_totals($episode_id, $start, $end);

			if (!$episode = Model\Episode::find_one_by_id($episode_id))
				continue;

			$post = get_post($episode->post_id);

			$top_episode_data[] = array(
				'days'  => $days_data_from_query_result($totals),
				'title' => $post->post_title
			);
		}

		$other_totals = Model\DownloadIntent::daily_totals($start, $end, $top_episode_ids);
		$other_episode_data = array(
			'days'  => $days_data_from_query_result($other_totals),
			'title' => "Other"
		);

		?>

		<div class="wrap">
			<h2><?php echo __("Podcast Analytics", "podlove"); ?></h2>

			<div id="total_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

		</div>

		<script type="text/javascript">
		(function ($) {

		})(jQuery);
		</script>

		<?php
	}

}