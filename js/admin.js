function human_readable_size(size) {
	if (!size || size < 1) {
		return "File Size Missing :(";
	}

	var kilobytes = size / 1024;

	if (kilobytes < 500) {
		return kilobytes.toFixed(2) + " kB";
	}

	var megabytes = kilobytes / 1024
	return megabytes.toFixed(2) + " MB";
}

jQuery(function($) {

	$("#podlove_settings_handle_validation").each(function() {
		DashboardValidation.init($(this));
	});

	$(".postbox[id*=podlove_show]").each(function() {
		Episode.init($(this));
	});

	$("#podlove_media_locations").each(function() {
		MediaLocationSettings.init($(this));
	});
	
});