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

	/**
	 * Handles all logic in Create/Edit Episode screen.
	 */
	var Episode = (function () {

		// private
		var o = {};

		function enable_all_media_files_by_default() {
			if (o.slug_field.val().length === 0) {
				o.slug_field.on('blur', function() {
					if (o.slug_field.val().length > 0) {
						// by default, tick all
						$container.find('input[type="checkbox"][name*="media_locations"]').attr("checked", true);
					}
				});
			}
		}

		// public
		o.init = function ($container) {
			o.container  = $container;
			o.slug_field = o.container.find("[name*=slug]");

			enable_all_media_files_by_default();

			return o;
		};
		
		return o;
	}());

	// Media Files: Default title = extension
	$('select[name*=media_format_id]').on('change', function() {
		var $container = $(this).closest('table');
		var $title = $container.find('[name*="title"]');

		var media_format_id = $(this).val();
		var format = jQuery.parseJSON(jQuery("#media_format_data").html())[media_format_id]

		if ($title.val().length === 0) {
			$title.val(format['extension']);
		}
		
	});

	// live preview for media file url templates in settings
	function update_media_file_preview() {
		$('input[name*="url_template"]').each(function() {
			var template = $(this).val();
			var $preview = $(this).closest('td').find('.url_template_preview');
			var $container = $(this).closest('table');

			var media_file_base_uri = $('#podlove_show_media_file_base_uri').val();
			var episode_slug        = 'example-episode';
			var feed_suffix         = $container.find('[name*="suffix"]').val();

			var selected_format     = $container.find('[name*="media_format_id"] option:selected').text();
			var match               = selected_format.match(/\((.*)\)/);

			if (!match) return;

			var format_extension    = match[1];

			template = template.replace( '%media_file_base_url%', media_file_base_uri );
			template = template.replace( '%episode_slug%', episode_slug );
			template = template.replace( '%suffix%', feed_suffix );
			template = template.replace( '%format_extension%', format_extension );

			$preview.html(template);	
		});
	}

	$('input[name*="url_template"]').on( 'keyup', update_media_file_preview );
	$('input[name*="suffix"]').on( 'keyup', update_media_file_preview );
	$('#podlove_show_media_file_base_uri').on( 'keyup', update_media_file_preview );
	$('[name*="media_format_id"]').on( 'change', update_media_file_preview );
	update_media_file_preview();

	// live preview for file urls
	var	update_media_file_path = function() {
		$("tr.row_media_locations td .media_file_path").each(function() {
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
	
	$("tr.row_media_locations td label").after('<span class="media_file_path"></span>');
	update_media_file_path();
	$('input[name*="slug"], input[name*="media_locations"]').on('change', update_media_file_path);

	$("#validate_everything").click(function(e) {
		e.preventDefault();

		$(".release .file").each(function() {
			var file_id = $(this).data('id');

			var data = {
				action: 'podlove-validate-file',
				file_id: file_id
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$file = $('.file[data-id="' + result.file_id + '"]');
					console.log(result);
					if (result.reachable) {
						$(".status", $file).html("<span style='color:green'>ok</span>");
					} else {
						$(".status", $file).html("<span style='color:red'>unreachable</span>");
					}
				}
			});

		});
	});

	$(".postbox[id*=podlove_show]").each(function() {
		Episode.init($(this));
	});
	
});