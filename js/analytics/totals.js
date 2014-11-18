jQuery(document).ready(function($) {
	var totalsRawData;

	var titleDateFormat = d3.time.format("%Y-%m-%d %H:%M %Z");

	var chart_width = $("#total-chart").closest(".wrap").width();
	var brush = { min: null, max: null };

	/**
	 * round to <digits> digits after comma.
	 *
	 * decimalRound(5.123,1) // => 5.1
	 * decimalRound(5.678,2) // => 5.68
	 */
	function decimalRound(number, digits) {
		var exp = Math.pow(10, digits);

		number *= exp;
		number = Math.round(number);
		number /= exp;

		return number;
	}

	var reduceAddFun = function (p, v) {
		
		p.downloads += v.downloads;

		p.weekday  = v.weekday;
		p.asset_id = v.asset_id;
		p.date     = v.date;
		p.client   = v.client;
		p.system   = v.system;
		p.source   = v.source;
		p.context  = v.context;

		return p;
	};
	var reduceSubFun = function (p, v) { 
		p.downloads -= v.downloads;
		return p;
	};
	var reduceBaseFun = function () {
		return {
			downloads: 0,
			weekday: 0,
			asset_id: 0,
			date: 0,
			client: "",
			system: ""
		};
	};

	var formatThousands = function(v) {
		if (v < 1000)
			return v;
		else
			return decimalRound(v/1000, 1) + "k";
	};

	function render_episode_performance_chart() {
		var xfilter = crossfilter(totalsRawData);

		// dimension: "hours since release"
		var dateDimension = xfilter.dimension(function(d) {
			// return d.date;
			return d3.time.day(d.date);
		});

		// group: downloads
		var downloadsGroup = dateDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		/**
		 * Charts
		 */
		var chartColor = '#69B3FF';
		
		var daysAgo = function(days) {
			return new Date(new Date().setDate(new Date().getDate()-30));
		};

		var titleDateFormat = d3.time.format("%Y-%m-%d");

		var downloadsChart = dc.barChart("#total-chart")
			.width(chart_width)
			.dimension(dateDimension)
			.group(downloadsGroup, "Total Downloads")
			.x(d3.time.scale().domain([daysAgo(30), new Date()]))
			.xUnits(d3.time.days)
			.brushOn(false)
			.renderTitle(true)
			.elasticY(true)
			.yAxisLabel("Downloads")
			.xAxisLabel("Last 30 days")
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.title(function(d) {
				return [
					titleDateFormat(d.key),
					"Downloads: " + d.value.downloads
				].join("\n");
			})
			.colors(chartColor)
			.renderHorizontalGridLines(true)
		;

		downloadsChart.yAxis().tickFormat(formatThousands);

		downloadsChart.render();
	}

	function load_episode_performance_chart() {

		if (totalsRawData) {
			render_episode_performance_chart();
		} else {
			$.when(
				$.ajax(ajaxurl + "?action=podlove-analytics-total-downloads-per-day")
			).done(function(csvTotals) {

				var csvMapper = function(d) {
					var parsed_date = new Date(+d.date * 1000);

					return {
						date: parsed_date,
						downloads: +d.downloads,
						hour: d3.time.hour(parsed_date),
						month: parsed_date.getMonth()+1,
						year: parsed_date.getFullYear(),
						weekday: parsed_date.getDay(),
						asset_id: +d.asset_id,
						client: d.client ? d.client : "Unknown",
						system: d.system ? d.system : "Unknown",
						source: d.source ? d.source : "Unknown",
						context: d.context ? d.context : "Unknown"
					};
				};

				totalsRawData = d3.csv.parse(csvTotals, csvMapper);

				render_episode_performance_chart();
			});
		}

	}

	load_episode_performance_chart();
});