var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 */
(function($){
	PODLOVE.Episode = function (container) {

	 	var o = {};
	 	var ajax_requests = [];

	 	// private
	 	function enable_all_media_files_by_default() {
	 		if (o.slug_field.val().length === 0) {
	 			o.slug_field.on('slugHasChanged', function() {
	 				if (o.slug_field.val().length > 0) {
	 					// by default, tick all
	 					$container.find('input[type="checkbox"][name*="episode_assets"]')
	 						.attr("checked", true)
	 						.change();
	 				}
	 			});
	 		}
	 	}

	 	function maybe_update_media_files() {
	 		var current_slug = o.slug_field.val(),
	 		    prev_slug = o.slug_field.data('prev-slug');

	 		if (current_slug !== prev_slug) {
	 			// abort all current requests if any are running
	 			$.each(
	 				ajax_requests,
	 				function(index, request){ request.abort(); });
	 			// then trigger new requests
	 			$(".update_media_file").click();
	 		}

	 		o.slug_field.data('prev-slug', current_slug);
	 	};

	 	function generate_live_preview() {
	 		o.update_preview();
	 		$('input[name*="episode_assets"]', container).on('change', function(){
	 			o.update_preview_row($(this).closest(".media_file_row"));
	 		});
	 	};

	 	function create_file(args) {
	 		var data = {
	 			action: 'podlove-create-file',
	 			episode_id: args.episode_id,
	 			episode_asset_id: args.episode_asset_id,
	 			slug: $("#_podlove_meta_slug").val()
	 		};

	 		$.ajax({
	 			url: ajaxurl,
	 			data: data,
	 			dataType: 'json',
	 			success: function(result) {
	 				args.checkbox.data({
	 					id: result.file_id,
	 					size: result.file_size
	 				});
	 				o.update_preview_row(args.container_row);
	 			}
	 		});
	 	};

	 	o.update_preview_row = function(container) {

	 		$container = container.closest('.inside');
	 		$checkbox  = container.find("input");

	 		if ($($checkbox).is(":checked")) {
	 			var file_id = $checkbox.data('id');

	 			if (!file_id) {
	 				// create file
	 				create_file({
	 					episode_id: $checkbox.data('episode-id'),
	 					episode_asset_id: $checkbox.data('episode-asset-id'),
	 					checkbox: $checkbox,
	 					container_row: container
	 				});
	 			} else {
	 				var url                 = $checkbox.data('template');
	 				var media_file_base_uri = PODLOVE.trailingslashit($container.find('input[name="show-media-file-base-uri"]').val());
	 				var episode_slug        = $container.find('input[name*="slug"]').val();
	 				var format_extension    = $checkbox.data('extension');
	 				var size                = $checkbox.data('size');
	 				var suffix              = $checkbox.data('suffix');

	 				url = url.replace( '%media_file_base_url%', media_file_base_uri );
	 				url = url.replace( '%episode_slug%', episode_slug );
	 				url = url.replace( '%suffix%', suffix );
	 				url = url.replace( '%format_extension%', format_extension );

	 				var readable_size = human_readable_size( size );
	 				var filename      = url.replace(media_file_base_uri, "");
	 				var $row          = $checkbox.closest(".media_file_row");

	 				if (readable_size === "???") {
	 					size_html = '<span style="color:red">File not found!</span>';
	 					$row.find(".status").html('<i class="podlove-icon-remove"></i>');
	 				} else {
	 					size_html = '<span style="color:#0a0b0b" title="' + readable_size + '">' + size + ' Bytes</span>';	
	 					$row.find(".status").html('<i class="podlove-icon-ok"></i>');
	 				}
	 				$row.find(".size").html(size_html);
	 				$row.find(".url").html('<a href="' + url + '" target="_blank">' + filename + '</a>');
	 				$row.find(".update").html('<a href="#" class="button update_media_file">update</a>');

	 				o.slug_field.trigger('mediaFileHasUpdated', [url]);
	 			}

	 		} else {
	 			$checkbox.data('id', null);
	 			$checkbox.closest(".media_file_row").find(".size, .url, .update, .status").html('');
	 		}

	 	};

 		o.update_preview = function() {
 			$(".media_file_row", o.container).each(function() {
 				o.update_preview_row($(this));
 			});
 		}

 		o.slug_field = container.find("[name*=slug]");
 		enable_all_media_files_by_default();
 		generate_live_preview();

 		$("#_podlove_meta_subtitle").count_characters( { limit: 255,  title: 'recommended maximum length: 255' } );
 		$("#_podlove_meta_summary").count_characters(  { limit: 4000, title: 'recommended maximum length: 4000' } );

 		$(document).on("click", ".subtitle_warning .close", function() {
 			$(this).closest(".subtitle_warning").remove();
 		});

 		$("#_podlove_meta_subtitle").keydown(function(e) {
 			// forbid return key
 			if (e.keyCode == 13) {
 				e.preventDefault();

 				if (!$(".subtitle_warning").length) {
	 				$(this).after('<span class="subtitle_warning">The subtitle has to be a single line. <span class="close">(hide)</span></span>');
 				}

 				return false;
 			}
 		});

 		$(".media_file_row").each(function() {
 			$(".enable", this).html($(".asset input", this));
 		});

 		$(".row__podlove_meta_episode_assets > span > label").after(" <a href='#' id='update_all_media_files'>update all media files</a>")

 		var update_all_media_files = function(e) {
 			e.preventDefault();
 			$(".update_media_file").click();
 		};

 		$.subscribe("/auphonic/production/status/done", update_all_media_files);
 		$(document).on("click", "#update_all_media_files", update_all_media_files);

 		$(document).on("click", ".update_media_file", function(e) {
 			e.preventDefault();

 			var container = $(this).closest(".media_file_row");
 			var file = container.find("input").data();

 			var data = {
 				action: 'podlove-update-file',
 				file_id: file.id,
 				slug: $("#_podlove_meta_slug").val()
 			};

 			container.find('.update').html('<i class="podlove-icon-spinner rotate"></i>');
 			container.find(".size, .url, .status").html('');

 			var request = $.ajax({
 				url: ajaxurl,
 				data: data,
 				dataType: 'json',
 				success: function(result) {
 					ajax_requests.pop();
 					container.find("input").data('size', result.file_size);
 					o.update_preview_row(container);
 					if (result.message) {
 						if ( !$("#debug_info").length ) {
 							$("table.media_file_table").after("<div id='debug_info'></div>");
 						}
 						CodeMirror(
 							document.getElementById("debug_info"),
 							{ value: result.message, mode: "yaml" }
 						);
 					}
 				}
 			});
 			ajax_requests.push(request);

 			return false;
 		});

		o.slug_field.on('slugHasChanged', function() {
			maybe_update_media_files();
		});

		var typewatch = (function() {
			var timer = 0;
			return function(callback, ms) {
				clearTimeout (timer);
				timer = setTimeout(callback, ms);
			}
		})();

 		o.slug_field
 			.on('blur', function() {
 				o.slug_field.trigger('slugHasChanged');
 			})
 			.on('keyup', function() {
				typewatch(
					function() {
						o.slug_field.trigger('slugHasChanged');
					},
					500
				);
			});

	 	return o;

	}
}(jQuery));

