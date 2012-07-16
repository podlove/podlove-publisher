/**
 * Handles all logic in Create/Edit Episode screen.
 */
var Episode = (function ($) {

	// private
	var o = {};

	function enable_all_media_files_by_default() {
		if (o.slug_field.val().length === 0) {
			o.slug_field.on('blur', function() {
				if (o.slug_field.val().length > 0) {
					// by default, tick all
					$container.find('input[type="checkbox"][name*="media_locations"]')
						.attr("checked", true)
						.change();
				}
			});
		}
	}

	function generate_live_preview() {
		$("tr.row_media_locations td label", o.container).after('<span class="media_file_path"></span>');
		o.update_preview();
		$('input[name*="slug"], input[name*="media_locations"]', o.container).on('change', o.update_preview);
	};

	// public
	o.update_preview = function() {
		$("tr.row_media_locations td .media_file_path", o.container).each(function() {
			$container = $(this).closest('.inside');
			$checkbox  = $(this).parent().find("input");

			if ($($checkbox).is(":checked")) {
				var url                 = $checkbox.data('template');

				var media_file_base_uri = $container.find('input[name="show-media-file-base-uri"]').val();
				var episode_slug        = $container.find('input[name*="slug"]').val();
				var feed_suffix         = $checkbox.data('suffix');
				var format_extension    = $checkbox.data('extension');
				var size                = $checkbox.data('size');

				url = url.replace( '%media_file_base_url%', media_file_base_uri );
				url = url.replace( '%episode_slug%', episode_slug );
				url = url.replace( '%suffix%', feed_suffix );
				url = url.replace( '%format_extension%', format_extension );

				output = '(' + url + ' [' + human_readable_size( size ) + '])';
			} else {
				output = "";
			}

			$(this).html(output);
		});
	}

	o.init = function ($container) {
		o.container  = $container;
		o.slug_field = o.container.find("[name*=slug]");

		enable_all_media_files_by_default();
		generate_live_preview();
	};
	
	return o;
}(jQuery));
