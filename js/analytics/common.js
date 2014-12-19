var PODLOVE = PODLOVE || {};
PODLOVE.Analytics = PODLOVE.Analytics || {};

/**
 * round to <digits> digits after comma.
 *
 * decimalRound(5.123,1) // => 5.1
 * decimalRound(5.678,2) // => 5.68
 */
PODLOVE.Analytics.decimalRound = function(number, digits) {
	var exp = Math.pow(10, digits);

	number *= exp;
	number = Math.round(number);
	number /= exp;

	return number;
};

PODLOVE.Analytics.formatThousands = function(num) {
	if (num < 1000)
		return num;
	else
		return PODLOVE.Analytics.decimalRound(num/1000, 1) + "k";
};

PODLOVE.Analytics.hourFormat = function(hours) {
	var days = 0, weeks = 0, label = [];

	if (hours > 48) {
		days  = (hours - hours % 24) / 24;
		hours = hours % 24;
	}

	if (days > 13) {
		weeks = (days - days % 7) / 7;
		days  = days % 7;
	};

	if (weeks)
		label.push(PODLOVE.Analytics.decimalRound(weeks,1) + "w");

	if (days)
		label.push(PODLOVE.Analytics.decimalRound(days,1) + "d");

	if (hours)
		label.push(PODLOVE.Analytics.decimalRound(hours,1) + "h")

	return label.join(" ");
};