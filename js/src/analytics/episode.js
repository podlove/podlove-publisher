import * as dc from 'dc'

var PODLOVE = PODLOVE || {}
PODLOVE.Analytics = PODLOVE.Analytics || {}

Number.isNaN =
  Number.isNaN ||
  function (value) {
    return value !== value
  }

jQuery(document).ready(function ($) {
  var csvCurEpisodeRawData, csvAvgEpisodeRawData

  var titleDateFormat = d3.timeFormat('%Y-%m-%d %H:%M %Z')

  var episode_id = jQuery('#episode-performance-chart').data('episode')
  var chart_width = $('#episode-performance-chart').closest('.inside').width()
  var brush = {
    min: null,
    max: null,
  }

  let enableAvgEpisodeChart, disableAvgEpisodeChart

  var reduceAddFun = function (p, v) {
    p.downloads += v.downloads

    p.asset_id = v.asset_id
    p.date = p.date && p.date < v.date ? p.date : v.date // take first date in reduced set
    p.client = v.client
    p.system = v.system
    p.source = v.source
    p.context = v.context
    p.geo = v.geo

    return p
  }
  var reduceSubFun = function (p, v) {
    p.downloads -= v.downloads
    return p
  }
  var reduceBaseFun = function () {
    return {
      downloads: 0,
      asset_id: 0,
      date: 0,
      client: '',
      system: '',
      geo: '',
    }
  }

  function render_episode_performance_chart(options) {
    var hours_per_unit = options.hours_per_unit

    var xfilter = crossfilter(csvCurEpisodeRawData)
    var xfilterAvg = crossfilter(csvAvgEpisodeRawData)
    var all = xfilter.groupAll().reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    var addPercentageLabels = function (chart) {
      var data = chart.data()
      var filters = chart.filters()

      data.forEach(function (d, index) {
        var row = chart.select('g.row._' + index)
        var label = chart.select('g.row._' + index + ' text')
        var text = ''

        if (!row.select('.subLabel').size()) {
          row
            .append('text')
            .attr('class', 'subLabel')
            .attr('text-anchor', 'end')
            .attr('x', -10)
            .attr('y', label.attr('y'))
        }

        // when a filter is set, only show active rows
        if (filters.length > 0 && filters.includes(d.key)) {
          row.select('.subLabel').style({
            display: 'none',
          })
        } else {
          row.select('.subLabel').style({
            display: 'inherit',
          })
        }

        if (all.value().downloads > 0) {
          text = Math.round((d.value.downloads / all.value().downloads) * 100) + '%'
        }

        row.select('.subLabel').text(text)
      })
    }

    var addResetFilter = function (chart, filter) {
      chart.select('.reset').on('click', function (data, index) {
        d3.event.preventDefault()
        chart.filterAll()
        dc.redrawAll()
      })
    }

    /**
     * Dimensions & Groups
     */
    var dimRelativeHoursSinceRelease = function (d) {
      return Math.floor(d.hoursSinceRelease / hours_per_unit)
    }

    // dimension: "hours since release"
    var hoursDimension = xfilter.dimension(dimRelativeHoursSinceRelease)

    // dimension: "hours since release"
    var avgEpisodeHoursDimension = xfilterAvg.dimension(dimRelativeHoursSinceRelease)

    // dimension: asset id
    var assetDimension = xfilter.dimension(function (d) {
      return d.asset_id
    })

    // dimension: client
    var clientDimension = xfilter.dimension(function (d) {
      return d.client
    })

    // dimension: operating system
    var systemDimension = xfilter.dimension(function (d) {
      return d.system
    })

    // dimension: download source
    var sourceDimension = xfilter.dimension(function (d) {
      return d.source
    })

    // dimension: download context
    var contextDimension = xfilter.dimension(function (d) {
      return d.context
    })

    var geoDimension = xfilter.dimension(function (d) {
      return d.geo
    })

    // group: downloads
    var downloadsGroup = hoursDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    // group: downloads
    var avgDownloadsGroup = avgEpisodeHoursDimension
      .group()
      .reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    // group: cumulative downloads
    var _cumulativeDownloadsGroup = hoursDimension
      .group()
      .reduce(reduceAddFun, reduceSubFun, reduceBaseFun)
      .all()
      .reduce(function (acc, cur) {
        cur.key += 1 // shift all keys to make space for a zero-entry
        cur.value.cum = true // set flag to identify cumulative data set
        if (acc.length) {
          cur.value.downloads += acc.slice(-1)[0].value.downloads
        }
        acc.push(cur)
        return acc
      }, [])

    // add zero-entry
    _cumulativeDownloadsGroup.unshift({
      key: 0,
      value: {
        date: 0,
        downloads: 0,
        cum: true,
      },
    })

    var cumulativeDownloadsGroup = {
      all: function () {
        return _cumulativeDownloadsGroup
      },
    }

    // group: downloads per asset
    var assetsGroup = assetDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    // group: downloads per client
    var clientsGroup = clientDimension
      .group()
      .reduce(reduceAddFun, reduceSubFun, reduceBaseFun)
      .order(function (v) {
        return v.downloads
      })

    // group: downloads per operating system
    var systemsGroup = systemDimension
      .group()
      .reduce(reduceAddFun, reduceSubFun, reduceBaseFun)
      .order(function (v) {
        return v.downloads
      })

    // group: downloads by source
    var sourceGroup = sourceDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    // group: downloads by context
    var contextGroup = contextDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    var geoGroup = geoDimension.group().reduce(reduceAddFun, reduceSubFun, reduceBaseFun)

    /**
     * Charts
     */
    var chartColor = '#69B3FF'
    var compChart = dc.compositeChart('#episode-performance-chart')

    var downloadsChart = dc
      .barChart(compChart)
      .dimension(hoursDimension)
      .group(downloadsGroup, 'Current Episode')
      .renderTitle(true)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .gap(1)
      .colors(chartColor)

    var avgEpisodeDownloadsChart = dc
      .barChart(compChart)
      .dimension(hoursDimension)
      .group(avgDownloadsGroup, 'Average Episode')
      .renderTitle(true)
      .colors('#224BA6')
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .barPadding(2)

    var cumulativeEpisodeChart = dc
      .lineChart(compChart)
      .dimension(hoursDimension)
      .group(cumulativeDownloadsGroup, 'Cumulative')
      .colors('#CCC')
      .useRightYAxis(true)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .renderDataPoints({})
      .renderArea(true)

    var rangeChartXAxisLength = downloadsGroup.all().reduce(function (prev, cur) {
      return Math.max(prev, cur.key)
    }, 0)

    var rangeChart = dc
      .barChart('#episode-range-chart')
      .width(chart_width)
      .height(80)
      .dimension(hoursDimension)
      .group(downloadsGroup)
      .x(d3.scaleLinear().domain([0, rangeChartXAxisLength]))
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .colors(chartColor)
      .yAxisLabel(' ') // to align yaxis with main chart
    window.rangeChart = rangeChart

    compChart
      .width(chart_width)
      .x(d3.scaleLinear().domain([0, Infinity]))
      .legend(
        dc
          .legend()
          .x(chart_width - 160)
          .y(20)
          .itemHeight(13)
          .gap(5)
      )
      .elasticX(false)
      // .elasticY(true)
      .brushOn(false)
      .transitionDuration(0) // turn off transitions
      .yAxisLabel('Downloads')
      .xAxisLabel('Hours since release')
      .rangeChart(rangeChart)
      .title(function (d) {
        var title = d.value.date ? titleDateFormat(d.value.date) : 'Average Episode',
          time = ''

        if (d.value.cum) {
          time = d.key * hours_per_unit + 'h after release'
        } else {
          time = d.key * hours_per_unit + 'h – ' + (d.key + 1) * hours_per_unit + 'h after release'
        }

        return [title, time, 'Downloads: ' + d.value.downloads].join('\n')
      })
      .compose([cumulativeEpisodeChart, downloadsChart, avgEpisodeDownloadsChart])
      .rightYAxisLabel('Cumulative Downloads')

    var assetChart = dc
      .rowChart('#episode-asset-chart')
      .margins({
        top: 0,
        left: 40,
        right: 10,
        bottom: 25,
      })
      .elasticX(true)
      .dimension(assetDimension) // set dimension
      .group(assetsGroup) // set group
      .valueAccessor(function (v) {
        if (v.value) {
          return v.value.downloads
        } else {
          return 0
        }
      })
      .ordering(function (v) {
        return -v.value.downloads
      })
      .label(function (d) {
        return assetNames[d.key]
      })
      .title(function (d) {
        return d.value.downloads
      })
      .colors(chartColor)
      .on('preRedraw', addPercentageLabels)
      .on('renderlet', addResetFilter)

    var clientChart = dc
      .rowChart('#episode-client-chart')
      .margins({
        top: 0,
        left: 40,
        right: 10,
        bottom: 25,
      })
      .elasticX(true)
      .dimension(clientDimension)
      .group(clientsGroup)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .ordering(function (v) {
        return -v.value.downloads
      })
      .othersGrouper(function (data) {
        return data // no 'others' group
      })
      .cap(10)
      .label(function (d) {
        return d.key
      })
      .colors(chartColor)
      .on('preRedraw', addPercentageLabels)
      .on('renderlet', addResetFilter)

    var systemChart = dc
      .rowChart('#episode-system-chart')
      .margins({
        top: 0,
        left: 40,
        right: 10,
        bottom: 25,
      })
      .elasticX(true)
      .dimension(systemDimension)
      .group(systemsGroup)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .ordering(function (v) {
        return -v.value.downloads
      })
      .othersGrouper(function (data) {
        return data // no 'others' group
      })
      .cap(10)
      .label(function (d) {
        return d.key
      })
      .colors(chartColor)
      .on('preRedraw', addPercentageLabels)
      .on('renderlet', addResetFilter)

    var sourceChart = dc
      .rowChart('#episode-source-chart')
      .margins({
        top: 0,
        left: 40,
        right: 10,
        bottom: 25,
      })
      .elasticX(true)
      .dimension(sourceDimension)
      .group(sourceGroup)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .ordering(function (v) {
        return -v.value.downloads
      })
      .label(function (d) {
        return d.key
      })
      .colors(chartColor)
      .on('preRedraw', addPercentageLabels)
      .on('renderlet', addResetFilter)

    var contextChart = dc
      .rowChart('#episode-context-chart')
      .margins({
        top: 0,
        left: 40,
        right: 10,
        bottom: 25,
      })
      .elasticX(true)
      .dimension(contextDimension)
      .group(contextGroup)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .ordering(function (v) {
        return -v.value.downloads
      })
      .label(function (d) {
        return d.value.source + '/' + d.key
      })
      .colors(chartColor)
      .on('preRedraw', addPercentageLabels)
      .on('renderlet', addResetFilter)

    var geoChart = dc
      .rowChart('#episode-geo-chart')
      .margins({
        top: 0,
        left: 40,
        right: 10,
        bottom: 25,
      })
      .elasticX(true)
      .cap(10)
      .dimension(geoDimension)
      .group(geoGroup)
      .valueAccessor(function (v) {
        return v.value.downloads
      })
      .ordering(function (v) {
        return -v.value.downloads
      })
      .othersGrouper(function (data) {
        return data // no 'others' group
      })
      .colors(chartColor)
      .on('preRedraw', addPercentageLabels)
      .on('renderlet', addResetFilter)

    // set tickFormats for all charts
    rangeChart.yAxis().ticks([2])
    rangeChart.xAxis().tickFormat(function (v) {
      return PODLOVE.Analytics.hourFormat(v * hours_per_unit)
    })

    compChart.xAxis().tickFormat(function (v) {
      return PODLOVE.Analytics.hourFormat(v * hours_per_unit)
    })

    rangeChart.yAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    compChart.yAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    compChart.rightYAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    assetChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    clientChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    systemChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    sourceChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    contextChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands)
    geoChart.xAxis().tickFormat(PODLOVE.Analytics.formatThousands)

    enableAvgEpisodeChart = function () {
      compChart
        .elasticY(true)
        .compose([cumulativeEpisodeChart, downloadsChart, avgEpisodeDownloadsChart])
      compChart.render()
    }

    disableAvgEpisodeChart = function () {
      compChart.elasticY(true).compose([cumulativeEpisodeChart, downloadsChart])
      compChart.render()
    }
    ;[
      compChart,
      rangeChart,
      assetChart,
      clientChart,
      systemChart,
      sourceChart,
      contextChart,
      geoChart,
    ].forEach(function (chart) {
      chart.render()
    })

    var filterHours = function (min, max) {
      dc.filterAll()
      rangeChart.filter(dc.filters.RangedFilter(min / hours_per_unit, max / hours_per_unit))
      dc.redrawAll()
    }

    window.filterHours = filterHours

    // set range from 0 to 'one week' or 'everything' if the episode is younger than a week
    if (!brush.min && !brush.max) {
      brush.min = 0
      brush.max = 7 * 24
      $('#chart-zoom-selection .button:eq(1)').addClass('active')
    }

    $('#chart-zoom-selection .button').on('click', function (e) {
      var hours = parseInt($(this).data('hours'), 10)

      e.preventDefault()

      if ($(this).hasClass('disabled')) {
        return
      }

      $(this).siblings().removeClass('active')
      $(this).addClass('active')

      if (hours === 0) {
        // set to full range
        brush.min = 0
        brush.max = rangeChart.xUnitCount() * hours_per_unit
      } else {
        // extend to set range
        brush.max = brush.min + hours
      }

      filterHours(brush.min, brush.max)
    })

    if (options.rendered && options.rendered instanceof Function) {
      options.rendered()
    }
  }

  function load_episode_performance_chart(options) {
    if (csvCurEpisodeRawData) {
      render_episode_performance_chart(options)
    } else {
      $.when(
        $.ajax(
          ajaxurl + '?action=podlove-analytics-episode-downloads-per-hour&episode=' + episode_id
        ),
        $.ajax(ajaxurl + '?action=podlove-analytics-episode-average-downloads-per-hour')
      ).done(function (csvCurEpisode, csvAvgEpisode) {
        var csvMapper = function (d) {
          var parsed_date = new Date(+d.date * 1000)

          return {
            date: parsed_date,
            downloads: +d.downloads,
            hoursSinceRelease: +d.hours_since_release,
            asset_id: +d.asset_id,
            client: d.client ? d.client : 'Unknown',
            system: d.system ? d.system : 'Unknown',
            source: d.source ? d.source : 'Unknown',
            context: d.context ? d.context : 'Unknown',
            geo: d.geo ? d.geo : 'Unknown',
          }
        }

        csvCurEpisodeRawData = d3.csvParse(csvCurEpisode[0], csvMapper)
        csvAvgEpisodeRawData = d3.csvParse(csvAvgEpisode[0], function (d) {
          return {
            hoursSinceRelease: +d.hoursSinceRelease,
            downloads: +d.downloads,
          }
        })

        render_episode_performance_chart(options)
      })
    }
  }

  $('#chart-grouping-selection').on('click', 'a', function (e) {
    var unit_hours = parseInt($(this).data('hours'), 10),
      zoom_hours = parseInt($('#chart-zoom-selection .button.active').data('hours'), 10)

    $(this).siblings().removeClass('active')
    $(this).addClass('active')

    // deactivate all zoom buttons smaller than unit selection
    $('#chart-zoom-selection a.button').each(function () {
      var h = parseInt($(this).data('hours'), 10)

      if (h !== 0 && h < unit_hours) {
        $(this).addClass('disabled')
      } else {
        $(this).removeClass('disabled')
      }
    })

    load_episode_performance_chart({
      hours_per_unit: unit_hours,
      rendered: function () {
        // check if zoom setting makes sense
        if (zoom_hours !== 0 && (Number.isNaN(zoom_hours) || zoom_hours < unit_hours)) {
          $('#chart-zoom-selection a.button')
            .filter(function () {
              var h = parseInt($(this).data('hours'), 10)
              return h === 0 || h >= unit_hours
            })
            .first()
            .click()
        } else {
          $('#chart-zoom-selection a.button.active:first').click()
        }
      },
    })

    e.preventDefault()
  })

  $('#chart-grouping-selection a:eq(3)').click()

  /**
   * Analytics Tiles can be hidden via Screen Options
   */
  $("input[name='podlove_analytics_tiles']")
    .each(function () {
      var checked = $(this).attr('checked'),
        tile_id = $(this).val(),
        chart = $(".chart-wrapper[data-tile-id='" + tile_id + "']")

      if (!checked) {
        chart.hide()
      }
    })
    .on('click', function () {
      var checked = $(this).attr('checked'),
        tile_id = $(this).val(),
        chart = $(".chart-wrapper[data-tile-id='" + tile_id + "']")

      // save
      $.ajax({
        url: ajaxurl,
        data: {
          action: 'podlove-analytics-settings-tiles-update',
          tile_id: tile_id,
          checked: checked,
        },
      })

      // update UI
      if (checked) {
        chart.show()
      } else {
        chart.hide()
      }
    })

  const setAverageEpisodeSetting = function () {
    if (typeof disableAvgEpisodeChart == 'undefined') {
      window.setTimeout(setAverageEpisodeSetting, 500)
    } else {
      const checked = $('#average-episode').attr('checked')

      if (checked) {
        enableAvgEpisodeChart()
      } else {
        disableAvgEpisodeChart()
      }
    }
  }

  const saveAverageEpisodeSetting = function () {
    $.ajax({
      url: ajaxurl,
      data: {
        action: 'podlove-analytics-settings-avg-update',
        checked: $(this).attr('checked'),
      },
    })
  }

  $('#average-episode')
    .each(setAverageEpisodeSetting)
    .on('click', function () {
      saveAverageEpisodeSetting()
      setAverageEpisodeSetting()
    })
})
