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

	public function show_template() {
		?>

		<h2><?php echo __("Podcast Analytics", "podlove"); ?> | Episode FOO!!</h2>

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
		function print_filter(filter){
			var f=eval(filter);
			if (typeof(f.length) != "undefined") {}else{}
			if (typeof(f.top) != "undefined") {f=f.top(Infinity);}else{}
			if (typeof(f.dimension) != "undefined") {f=f.dimension(function(d) { return "";}).top(Infinity);}else{}
			console.log(filter+"("+f.length+") = "+JSON.stringify(f).replace("[","[\n\t").replace(/}\,/g,"},\n\t").replace("]","\n]"));
		} 

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

}