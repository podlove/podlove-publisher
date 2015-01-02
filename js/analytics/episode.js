jQuery(document).ready(function($) {
	var csvCurEpisodeRawData, csvAvgEpisodeRawData;

	var titleDateFormat = d3.time.format("%Y-%m-%d %H:%M %Z");

	var episode_id = jQuery("#episode-performance-chart").data("episode");
	var chart_width = $("#episode-performance-chart").closest(".inside").width();
	var brush = { min: null, max: null };

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

	function render_episode_performance_chart(options) {
		var hours_per_unit = options.hours_per_unit;

		var xfilter    = crossfilter(csvCurEpisodeRawData);
		var xfilterAvg = crossfilter(csvAvgEpisodeRawData);
		var all = xfilter.groupAll().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		var addPercentageLabels = function(chart) {
			var data = chart.data();

			data.forEach(function(d, index) {
				var row = chart.selectAll("g.row._" + index);
				var label = chart.selectAll("g.row._" + index + " text");
				
				if (!row.selectAll(".subLabel").size()) {
					row.append("text")
						.attr('class', 'subLabel')
						.attr('text-anchor', 'end')
						.attr('x', -10)
						.attr('y', label.attr('y'))
					;
				}

				row.selectAll(".subLabel")
					.html(Math.round(d.value.downloads / all.value().downloads * 100) + "%");
			});
	    };

		/**
		 * Dimensions & Groups
		 */
		var dimRelativeHoursSinceRelease = function(d) {
			return Math.floor(d.hoursSinceRelease / hours_per_unit);
		};

		// dimension: "hours since release"
		var hoursDimension = xfilter.dimension(dimRelativeHoursSinceRelease);

		// dimension: "hours since release"
		var avgEpisodeHoursDimension = xfilterAvg.dimension(dimRelativeHoursSinceRelease);

		// dimension: day of week
		var dayOfWeekDimension = xfilter.dimension(function (d) { return d.weekday; });

		// dimension: asset id
		var assetDimension = xfilter.dimension(function (d) {
			return d.asset_id;
		});

		// dimension: client
		var clientDimension = xfilter.dimension(function (d) {
			return d.client;
		});

		// dimension: operating system
		var systemDimension = xfilter.dimension(function (d) {
			return d.system;
		});
		
		// dimension: download source
		var sourceDimension = xfilter.dimension(function (d) {
			return d.source;
		});

		// dimension: download context
		var contextDimension = xfilter.dimension(function (d) {
			return d.context;
		});

		// group: downloads
		var downloadsGroup = hoursDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		// group: downloads
		var avgDownloadsGroup = avgEpisodeHoursDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		// group: cumulative downloads
		var _cumulativeDownloadsGroup = hoursDimension.group()
			.reduce(reduceAddFun, reduceSubFun, reduceBaseFun)
			.all()
			.reduce(function (acc, cur) {
				if (acc.length) {
					cur.value.downloads += acc.slice(-1)[0].value.downloads;
				}
				acc.push(cur);
				return acc;
			}, [])
		;

		var cumulativeDownloadsGroup = {
		    all: function () { return _cumulativeDownloadsGroup; }
		};

		// group: downloads per weekday
		var dayOfWeekGroup = dayOfWeekDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		// group: downloads per asset
		var assetsGroup = assetDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		// group: downloads per client
		var clientsGroup = clientDimension.group()
			.reduce(reduceAddFun, reduceSubFun, reduceBaseFun)
			.order(function(v) { return v.downloads; })
		;

		// group: downloads per operating system
		var systemsGroup = systemDimension.group()
			.reduce(reduceAddFun, reduceSubFun, reduceBaseFun)
			.order(function(v) { return v.downloads; })
		;

		// group: downloads by source
		var sourceGroup = sourceDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);
		
		// group: downloads by context
		var contextGroup = contextDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun);

		/**
		 * Charts
		 */
		var chartColor = '#69B3FF';

		var downloadsChart = dc.barChart(compChart)
			.dimension(hoursDimension)
			.group(downloadsGroup, "Current Episode")
			.centerBar(true)
			.xAxisPadding(0.6)
			.renderTitle(true)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.colors(chartColor)
		;

		var avgEpisodeDownloadsChart = dc.lineChart(compChart)
			.dimension(avgEpisodeHoursDimension)
			.group(avgDownloadsGroup, "Average Episode")
			.renderTitle(true)
			.colors('red')
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.renderDataPoints({})
		;

		var cumulativeEpisodeChart = dc.lineChart(compChart)
			.dimension(avgEpisodeHoursDimension)
			.group(cumulativeDownloadsGroup, "Cumulative")
			.colors('#CCC')
			.useRightYAxis(true)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.renderDataPoints({})
			.renderArea(true)
		;

		var rangeChart = dc.barChart("#episode-range-chart")
			.width(chart_width)
			.height(80)
			.dimension(hoursDimension)
			.group(downloadsGroup)
			.x(d3.scale.linear().domain([0,Infinity]))
			.elasticX(true)
			.centerBar(true)
			.xAxisPadding(0.6)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.colors(chartColor)
			.yAxisLabel(" ") // to align yaxis with main chart
		;

		var compChart = dc.compositeChart("#episode-performance-chart")
			.width(chart_width)
			.x(d3.scale.linear().domain([0,1000000]))
			.legend(dc.legend().x(chart_width - 160).y(20).itemHeight(13).gap(5))
			.elasticX(true)
			.elasticY(true)
			.brushOn(false)
			.yAxisLabel("Downloads")
			.xAxisLabel("Hours since release")
			.rangeChart(rangeChart)
			.title(function(d) {
				return [
					d.value.date ? titleDateFormat(d.value.date) : "",
					(d.key * hours_per_unit) + "h – " + ((d.key + 1) * hours_per_unit - 1) + "h after release",
					"Downloads: " + d.value.downloads
				].join("\n");
			})
			.compose([cumulativeEpisodeChart, downloadsChart, avgEpisodeDownloadsChart])
			.rightYAxisLabel("Cumulative Downloads")
		;

		var weekdayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
		var weekdayChart = dc.rowChart("#episode-weekday-chart")
		    .margins({top: 0, left: 40, right: 10, bottom: 25})
		    .group(dayOfWeekGroup)
		    .dimension(dayOfWeekDimension)
		    .elasticX(true)
		    .label(function(d) {
		    	return weekdayNames[d.key];
		    })
		    .title(function (d) {
		        return d.value.downloads;
		    })
		    .valueAccessor(function (v) {
		    	if (v.value) {
		    		return v.value.downloads;
		    	} else {
		    		return 0;
		    	}
		    })
			.ordering(function (v) {
				return -v.value.downloads;
			})
		    .colors(chartColor)
		    .on('preRedraw', addPercentageLabels)
		;

		var assetChart = dc.rowChart("#episode-asset-chart")
			.margins({top: 0, left: 40, right: 10, bottom: 25})
			.elasticX(true)
			.dimension(assetDimension) // set dimension
			.group(assetsGroup) // set group
			.valueAccessor(function (v) {
				if (v.value) {
					return v.value.downloads;
				} else {
					return 0;
				}
			})
			.ordering(function (v) {
				return -v.value.downloads;
			})
			.label(function(d) {
				return assetNames[d.key];
			})
			.title(function (d) {
				return d.value.downloads;
			})
			.colors(chartColor)
			.on('preRedraw', addPercentageLabels)
		;

		var clientChart = dc.rowChart("#episode-client-chart")
			.margins({top: 0, left: 40, right: 10, bottom: 25})
			.elasticX(true)
			.dimension(clientDimension)
			.group(clientsGroup)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.ordering(function (v) {
				return -v.value.downloads;
			})
			.othersGrouper(function(data) {
				return data; // no "others" group
			})
			.cap(10)
			.label(function(d) {
				return d.key;
			})
			.colors(chartColor)
			.on('preRedraw', addPercentageLabels)
		;

		var systemChart = dc.rowChart("#episode-system-chart")
			.margins({top: 0, left: 40, right: 10, bottom: 25})
			.elasticX(true)
			.dimension(systemDimension)
			.group(systemsGroup)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.ordering(function (v) {
				return -v.value.downloads;
			})
			.othersGrouper(function(data) {
				return data; // no "others" group
			})
			.cap(10)
			.label(function(d) {
				return d.key;
			})
			.colors(chartColor)
			.on('preRedraw', addPercentageLabels)
		;

		var sourceChart = dc.rowChart("#episode-source-chart")
			.margins({top: 0, left: 40, right: 10, bottom: 25})
			.elasticX(true)
			.dimension(sourceDimension)
			.group(sourceGroup)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.ordering(function (v) {
				return -v.value.downloads;
			})
			.label(function(d) {
				return d.key;
			})
			.colors(chartColor)
			.on('preRedraw', addPercentageLabels)
		;

		var contextChart = dc.rowChart("#episode-context-chart")
			.margins({top: 0, left: 40, right: 10, bottom: 25})
			.elasticX(true)
			.dimension(contextDimension)
			.group(contextGroup)
			.valueAccessor(function (v) {
				return v.value.downloads;
			})
			.ordering(function (v) {
				return -v.value.downloads;
			})
			.label(function(d) {
				return d.value.source + "/" + d.key;
			})
			.colors(chartColor)
			.on('preRedraw', addPercentageLabels)
		;

		// set tickFormats for all charts
		rangeChart.yAxis().ticks([2]);
		rangeChart.xAxis().tickFormat(function(v) {
			return PODLOVE.Analytics.hourFormat(v * hours_per_unit);
		});
			
		compChart.xAxis().tickFormat(function(v) {
			return PODLOVE.Analytics.hourFormat(v * hours_per_unit);
		});

		rangeChart.yAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		compChart.yAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		compChart.rightYAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		weekdayChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		assetChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		clientChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		systemChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		sourceChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands);
		contextChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands);

		[compChart, rangeChart, weekdayChart, assetChart, clientChart, systemChart, sourceChart, contextChart].forEach(function(chart) {
			chart.render()
		});

		var renderBrush = function(chart, brush) {
			chart.brush()
				// set new brush range
				.extent([
					brush.min / hours_per_unit,
					Math.min(
						chart.xUnitCount(),
						brush.max / hours_per_unit
					)
				])
				// send brush event to trigger redraw
				.event(chart.select('g.brush'));
		}
		
		// set range from 0 to "one week" or "everything" if the episode is younger than a week
		if (!brush.min && !brush.max) {
			brush.min = 0;
			brush.max = 7*24 - 1;
			$("#chart-zoom-selection .button:eq(1)").addClass('active');
		}

		renderBrush(rangeChart, brush);

		// handle the user changing the brush manually
		rangeChart.brush().on('brushend', function() {
			var validRanges = $("#chart-zoom-selection .button").map(function() { return $(this).data('hours'); });

			extent = rangeChart.brush().extent();
			brush.min = extent[0] * hours_per_unit;
			brush.max = extent[1] * hours_per_unit;

			// if startpoint is < 0, automatically shift brush to the right
			if (brush.min < 0) {
				brush.max -= brush.min;
				brush.min = 0;

				renderBrush(rangeChart, brush);
			}

			// clear selection if the user modifies selection
			if (-1 === $.inArray(Math.round(brush.max - brush.min + 1), validRanges)) {
				$("#chart-zoom-selection .button.active").removeClass("active");
			}
		});

		$("#chart-zoom-selection .button").on("click", function(e) {
			var hours = parseInt($(this).data('hours'), 10);

			$(this).siblings().removeClass("active");
			$(this).addClass("active");

			if (hours === 0) {
				// set to full range
				brush.min = 0;
				brush.max = rangeChart.xUnitCount() * hours_per_unit;
			} else {
				// extend to set range
				brush.max = brush.min + hours - 1;
			}

			renderBrush(rangeChart, brush);

			e.preventDefault();
		});
	}

	function load_episode_performance_chart(options) {

		if (csvCurEpisodeRawData) {
			render_episode_performance_chart(options);
		} else {
			$.when(
				$.ajax(ajaxurl + "?action=podlove-analytics-episode-downloads-per-hour&episode=" + episode_id),
				$.ajax(ajaxurl + "?action=podlove-analytics-episode-average-downloads-per-hour")
			).done(function(csvCurEpisode, csvAvgEpisode) {

				var csvMapper = function(d) {
					var parsed_date = new Date(+d.date * 1000);

					return {
						date: parsed_date,
						downloads: +d.downloads,
						weekday: parsed_date.getDay(),
						hoursSinceRelease: +d.hours_since_release,
						asset_id: +d.asset_id,
						client: d.client ? d.client : "Unknown",
						system: d.system ? d.system : "Unknown",
						source: d.source ? d.source : "Unknown",
						context: d.context ? d.context : "Unknown"
					};
				};

				csvCurEpisodeRawData = d3.csv.parse(csvCurEpisode[0], csvMapper);
				csvAvgEpisodeRawData = d3.csv.parse(csvAvgEpisode[0], function(d) {
					return {
						hoursSinceRelease: +d.hoursSinceRelease,
						downloads: +d.downloads
					};
				});

				render_episode_performance_chart(options);
			});
		}

	}

	$("#chart-grouping-selection").on("click", "a", function(e) {
		var hours = parseInt($(this).data('hours'), 10);

		$(this).siblings().removeClass("active");
		$(this).addClass("active");

		load_episode_performance_chart({
			hours_per_unit: hours
		});

		e.preventDefault();
	});

	$("#chart-grouping-selection a:eq(3)").click();

});