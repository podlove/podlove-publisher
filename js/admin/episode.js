var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 */
(function($){
	 PODLOVE.Episode = function (container) {

	 	var o = {};

	 	// private
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
	 		$("tr[class*='media_locations'] td label", container).after('<span class="media_file_path"></span>');
	 		o.update_preview();
	 		$('input[name*="slug"], input[name*="media_locations"]', container).on('change', o.update_preview);
	 	};

 		o.update_preview = function() {
 			$("tr[class*='media_locations'] td .media_file_path", o.container).each(function() {
 				$container = $(this).closest('.inside');
 				$checkbox  = $(this).parent().find("input");

 				if ($($checkbox).is(":checked")) {
 					var url                 = $checkbox.data('template');

 					var media_file_base_uri = $container.find('input[name="show-media-file-base-uri"]').val();
 					var episode_slug        = $container.find('input[name*="slug"]').val();
 					var format_extension    = $checkbox.data('extension');
 					var size                = $checkbox.data('size');

 					url = url.replace( '%media_file_base_url%', media_file_base_uri );
 					url = url.replace( '%episode_slug%', episode_slug );
 					url = url.replace( '%format_extension%', format_extension );

 					var readable_size = human_readable_size( size );

 					output = ' ';
 					if (readable_size === "???") {
 						output += '<span style="color:red">File not found!</span>';
 					} else {
 						output += '<span title="' + url + '">' + size + ' Bytes (' + readable_size + ')</span>';	
 					}
 					output += ' <a href="#" class="update_media_file">update</a>';
 				} else {
 					output = "";
 				}

 				$(this).html(output);
 			});
 		}

 		o.slug_field = container.find("[name*=slug]");
 		enable_all_media_files_by_default();
 		generate_live_preview();

 		$(document).on("click", ".update_media_file", function(e) {
 			e.preventDefault();

 			var container = $(this).closest("div");
 			var file = container.find("input").data();

 			var data = {
 				action: 'podlove-update-file',
 				file_id: file.id
 			};

 			$(this).parent().html("updating ...");

 			$.ajax({
 				url: ajaxurl,
 				data: data,
 				dataType: 'json',
 				success: function(result) {
 					console.log(result);
 					container.find("input").data('size', result.file_size);
 					o.update_preview();
 				}
 			});

 			return false;
 		});

	 	return o;

	}
}(jQuery));

