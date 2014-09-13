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

		wp_register_script('podlove-d3-js',          \Podlove\PLUGIN_URL . '/node_modules/d3/d3.min.js');
		wp_register_script('podlove-crossfilter-js', \Podlove\PLUGIN_URL . '/node_modules/crossfilter/crossfilter.min.js');
		wp_register_script('podlove-dc-js',          \Podlove\PLUGIN_URL . '/node_modules/dc/dc.min.js', array('podlove-d3-js', 'podlove-crossfilter-js'));
	
		wp_enqueue_script('podlove-dc-js');

		wp_register_style( 'podlove-dc-css', \Podlove\PLUGIN_URL . '/node_modules/dc/dc.css', array(), \Podlove\get_plugin_header( 'Version' ) );
		wp_enqueue_style( 'podlove-dc-css' );
	}

	public function page() {

		?>
		<div class="wrap">
			<?php
			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;
			switch ( $action ) {
				case 'show':
					$this->show_template();
					break;
				case 'index':
				default:
					$this->view_template();
					break;
			}
			?>
		</div>	
		<?php
	}

	public function view_template() {
		?>

		<h2><?php echo __("Podcast Analytics", "podlove"); ?></h2>

		<div style="width: 100%; height: 260px">
			<div id="total-chart"></div>
			<div id="month-chart"></div>
		</div>
		
		<?php 
		$table = new \Podlove\Downloads_List_Table();
		$table->prepare_items();
		$table->display();
		?>

		<script type="text/javascript">
		(function ($) {

			var dateFormat = d3.time.format("%Y-%m-%d");

			d3.csv(ajaxurl + "?action=podlove-analytics-downloads-per-day", function(data) {
				data.forEach(function(d) {
					d.dd = dateFormat.parse(d.date);
					d.downloads = +d.downloads;
					d.month = dateFormat.parse(d.date).getMonth()+1;
					d.year = dateFormat.parse(d.date).getFullYear();
				});
				
				var ndx = crossfilter(data);
				var all = ndx.groupAll();

				// var downloadsDim = ndx.dimension(function(d){ return d.downloads; });
				var dateDim = ndx.dimension(function(d){ return d.dd; });
				var downloadsTotal = dateDim.group().reduceSum(dc.pluck("downloads"));

				var minDate = dateDim.bottom(1)[0].dd;
				var maxDate = dateDim.top(1)[0].dd;

				var totalDownloadsChart = dc.barChart("#total-chart")
					.width(800).height(250)
					.dimension(dateDim)
					.group(downloadsTotal)
					.x(d3.time.scale().domain([minDate,maxDate]))
					.elasticX(true)
					.centerBar(true)
					.gap(1)
					.brushOn(false)
					.yAxisLabel("Total Downloads per day")
				;

				totalDownloadsChart.yAxis().tickFormat(function(v) {
					if (v < 1000)
						return v;
					else
						return (v/1000) + "k";
				});

				var monthDim  = ndx.dimension(function(d) {return +d.month;});
				var monthTotal = monthDim.group().reduceSum(function(d) {return d.downloads;});

				var monthRingChart = dc.pieChart("#month-chart")
				    .width(150).height(150)
				    .dimension(monthDim)
				    .group(monthTotal)
				    .innerRadius(30)
				    .label(function(d) {
				    	switch (d.key) {
				    	  case 1:
				    	    return "Jan"; break;
				    	  case 2:
				    	    return "Feb"; break;
				    	  case 3:
				    	    return "Mar"; break;
				    	  case 4:
				    	    return "Apr"; break;
				    	  case 5:
				    	    return "May"; break;
				    	  case 6:
				    	    return "Jun"; break;
				    	  case 7:
				    	    return "Jul"; break;
				    	  case 8:
				    	    return "Aug"; break;
				    	  case 9:
				    	    return "Sep"; break;
				    	  case 10:
				    	    return "Oct"; break;
				    	  case 11:
				    	    return "Nov"; break;
				    	  case 12:
				    	    return "Dec"; break;
				    	}
				    });

				dc.renderAll();

			});

		})(jQuery);
		</script>

		<?php
	}

	private function render_episode_data_table() {

		$episode = Model\Episode::find_one_by_id((int) $_REQUEST['episode']);

		$cache = \Podlove\Cache\TemplateCache::get_instance();
		echo $cache->cache_for('podlove_analytics_episode' . $episode->id, function() use ($episode) {
	
			$post = get_post( $episode->post_id );

			$releaseDate = new \DateTime($post->post_date);
			$releaseDate->setTime(0, 0, 0);

			$diff = $releaseDate->diff(new \DateTime());
			$daysSinceRelease = $diff->days;

			$downloads = array(
				'total'     => Model\DownloadIntent::total_by_episode_id($episode->id, "1000 years ago", "now"),
				'month'     => Model\DownloadIntent::total_by_episode_id($episode->id, "28 days ago", "yesterday"),
				'week'      => Model\DownloadIntent::total_by_episode_id($episode->id, "7 days ago", "yesterday"),
				'yesterday' => Model\DownloadIntent::total_by_episode_id($episode->id, "1 day ago"),
				'today'     => Model\DownloadIntent::total_by_episode_id($episode->id, "now")
			);

			$peak = Model\DownloadIntent::peak_download_by_episode_id($episode->id);

			ob_start();
			?>
			<table>
				<tbody>
					<tr>
						<td>Total Downloads</td>
						<td><?php echo number_format_i18n($downloads['total']) ?></td>
					</tr>
					<tr>
						<td>28 Days*</td>
						<td><?php echo number_format_i18n($downloads['month']) ?></td>
					</tr>
					<tr>
						<td>7 Days*</td>
						<td><?php echo number_format_i18n($downloads['week']) ?></td>
					</tr>
					<tr>
						<td>Yesterday</td>
						<td><?php echo number_format_i18n($downloads['yesterday']) ?></td>
					</tr>
					<tr>
						<td>Today</td>
						<td><?php echo number_format_i18n($downloads['today']) ?></td>
					</tr>
					<tr>
						<td>Release Date</td>
						<td><?php echo mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $post->post_date) ?></td>
					</tr>
					<tr>
						<td>Peak Downloads/Day</td>
						<td><?php echo sprintf(
							"%s (%s)",
							number_format_i18n($peak['downloads']),
							mysql2date(get_option('date_format'), $peak['theday'])
						) ?></td>
					</tr>
					<tr>
						<td>Average Downloads/Day</td>
						<td><?php echo number_format_i18n($downloads['total'] / ($daysSinceRelease+1), 1) ?></td>
					</tr>
					<tr>
						<td>Days since Release</td>
						<td><?php echo number_format_i18n($daysSinceRelease) ?></td>
					</tr>
					<tr>
						<td colspan="2">
							<em>* excluding today</em>
						</td>
					</tr>
				</tbody>
			</table>
			<?php

			$html = ob_get_contents();
			ob_end_clean();
			return $html;

		}, 600); // 10 minutes


	}

	public function show_template() {
		$episode = Model\Episode::find_one_by_id((int) $_REQUEST['episode']);
		$post    = get_post( $episode->post_id );

		$topEpisodeIds = Model\DownloadIntent::top_episode_ids("1000 years ago", "now", 1);
		$topEpisodeId  = $topEpisodeIds[0];
		$topEpisode    = Model\Episode::find_one_by_id($topEpisodeId);
		$topPost       = get_post( $topEpisode->post_id );
		?>

		<h2>
			<?php echo sprintf(
				__("Analytics: %s", "podlove"),
				$post->post_title
			);
			?>
		</h2>

		<div id="episode-performance-chart" data-top-episode="<?php echo $topEpisode->id ?>" data-episode="<?php echo $episode->id ?>"></div>

		<?php echo $this->render_episode_data_table(); ?>

		<script type="text/javascript">
		function print_filter(filter){
			var f=eval(filter);
			if (typeof(f.length) != "undefined") {}else{}
			if (typeof(f.top) != "undefined") {f=f.top(Infinity);}else{}
			if (typeof(f.dimension) != "undefined") {f=f.dimension(function(d) { return "";}).top(Infinity);}else{}
			console.log(filter+"("+f.length+") = "+JSON.stringify(f).replace("[","[\n\t").replace(/}\,/g,"},\n\t").replace("]","\n]"));
		} 

		(function ($) {

			var dateFormat = d3.time.format("%Y-%m-%d");
			var episode_id     = $("#episode-performance-chart").data("episode");
			var top_episode_id = $("#episode-performance-chart").data("top-episode");

			$.when(
				$.ajax(ajaxurl + "?action=podlove-analytics-downloads-per-day&episode=" + episode_id),
				$.ajax(ajaxurl + "?action=podlove-analytics-downloads-per-day&episode=" + top_episode_id)
			).done(function(csv1, csv2) {

				var csvMapper = function(d) {
					return {
						date: d.date,
						downloads: +d.downloads,
						month: dateFormat.parse(d.date).getMonth()+1,
						year: dateFormat.parse(d.date).getFullYear(),
						days: +d.days
					};
				};

				data1 = d3.csv.parse(csv1[0], csvMapper);
				data2 = d3.csv.parse(csv2[0], csvMapper);
				
				// chart 1: current episode
				var ndx               = crossfilter(data1);
				var curDateDim        = ndx.dimension(function(d){ return d.days; });
				var curDownloadsTotal = curDateDim.group().reduceSum(dc.pluck("downloads"));

				// filter after grouping
				// @see http://stackoverflow.com/a/22018053/72448
				var curFilteredDownloadsTotal = {
				    all: function () {
				        return curDownloadsTotal.all().filter(function(d) { return d.key < 14; });
				    }
				}
				
				// chart 2: top episode
				var ndx            = crossfilter(data2);
				var dateDim        = ndx.dimension(function(d){ return d.days; });
				var downloadsTotal = dateDim.group().reduceSum(dc.pluck("downloads"));

				var filteredDownloadsTotal = {
				    all: function () {
				        return downloadsTotal.all().filter(function(d) { return d.key < 14; });
				    }
				};

				var compChart = dc.compositeChart("#episode-performance-chart")
					.width(450).height(250)
					.x(d3.scale.linear())
					.legend(dc.legend().x(300).y(20).itemHeight(13).gap(5))
					.elasticX(true)
					.brushOn(false)
					.yAxisLabel("Downloads per day")
					.xAxisLabel("Days since release")
					.compose([
						dc.barChart(compChart)
							.dimension(curDateDim)
							.group(curFilteredDownloadsTotal, "Current Episode")
							.centerBar(true)
							.xAxisPadding(0.6)
							.renderTitle(true)
							.title(function(d) {
								return [
									"Current Episode",
									"Downloads: " + d.value
								].join("\n");
							}),
						dc.lineChart(compChart)
							.dimension(dateDim)
							.group(filteredDownloadsTotal, "Top Episode")
							.renderTitle(true)
							.colors('red')
							.title(function(d) {
								return [
									"Top Episode",
									"Downloads: " + d.value
								].join("\n");
							})
					]);

				compChart.yAxis().tickFormat(function(v) {
					if (v < 1000)
						return v;
					else
						return (v/1000) + "k";
				});

				compChart.render();
			});

		})(jQuery);
		</script>

		<?php
	}

}