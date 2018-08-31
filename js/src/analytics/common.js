var PODLOVE = PODLOVE || {};
PODLOVE.Analytics = PODLOVE.Analytics || {};

dc.config.defaultColors(d3.schemeAccent);

/**
 * round to <digits> digits after comma.
 *
 * decimalRound(5.123,1) // => 5.1
 * decimalRound(5.678,2) // => 5.68
 */
PODLOVE.Analytics.decimalRound = function (number, digits) {
	var exp = Math.pow(10, digits);

	number *= exp;
	number = Math.round(number);
	number /= exp;

	return number;
};

PODLOVE.Analytics.formatThousands = function (num) {
	if (num < 1000)
		return num;
	else
		return PODLOVE.Analytics.decimalRound(num / 1000, 1) + "k";
};

PODLOVE.Analytics.hourFormat = function (hours) {
	var days = 0,
		weeks = 0,
		label = [];

	if (hours > 48) {
		days = (hours - hours % 24) / 24;
		hours = hours % 24;
	}

	if (days > 13) {
		weeks = (days - days % 7) / 7;
		days = days % 7;
	};

	if (weeks)
		label.push(PODLOVE.Analytics.decimalRound(weeks, 1) + "w");

	if (days)
		label.push(PODLOVE.Analytics.decimalRound(days, 1) + "d");

	if (hours)
		label.push(PODLOVE.Analytics.decimalRound(hours, 1) + "h")

	if (label.length === 0)
		label = ["0h"];

	return label.join(" ");
};

PODLOVE.Analytics.addPercentageLabels = function (chart, total) {
	var data = chart.data();
	var filters = chart.filters();

	data.forEach(function (d, index) {
		var row = chart.select('g.row._' + index);
		var label = chart.select('g.row._' + index + ' text');
		var text = '';

		if (!row.select('.subLabel').size()) {
			row.append('text')
				.attr('class', 'subLabel')
				.attr('text-anchor', 'end')
				.attr('x', -10)
				.attr('y', label.attr('y'));
		}

		// when a filter is set, only show active rows
		if (filters.length > 0 && $.inArray(d.key, filters) === -1) {
			row.select('.subLabel').style({
				'display': 'none'
			});
		} else {
			row.select('.subLabel').style({
				'display': 'inherit'
			});
		};

		if (total > 0) {
			text = Math.round(d.value / total * 100) + '%';
		}

		row.select('.subLabel').text(text);
	});
};
