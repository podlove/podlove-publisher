function human_readable_size(size) {
	if (!size) {
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
	
	var	update_media_file_path = function() {
		$("tr.row_formats td .media_file_path").each(function() {
			$container = $(this).closest('.inside');
			$checkbox  = $(this).parent().find("input");

			if ($($checkbox).is(":checked")) {
				var url = "";
				var media_file_base_uri = $container.find('input[name="show-media-file-base-uri"]').val();
				var episode_slug        = $container.find('input[name*="slug"]').val();
				var format_suffix       = $checkbox.data('suffix');
				var format_extension    = $checkbox.data('extension');
				var size                = $checkbox.data('size');

				url = media_file_base_uri + episode_slug + format_suffix + '.' + format_extension;

				output = '(' + url + ' [' + human_readable_size( size ) + '])';
			} else {
				output = "";
			}
			$(this).html(output);
		});
	}
	
	$("tr.row_formats td label").after('<span class="media_file_path"></span>');
	update_media_file_path();
	$('input[name*="slug"], input[name*="formats"]').on('change', update_media_file_path);
	
});