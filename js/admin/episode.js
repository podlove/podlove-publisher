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
	 					container.find('input[type="checkbox"][name*="episode_assets"]')
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
	 			action: 'podlove-file-create',
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
	 					size: result.file_size,
	 					'fileUrl': result.file_url
	 				});
	 				o.update_preview_row(args.container_row);
	 			}
	 		});
	 	};

	 	function maybe_update_episode_slug(title) {
	 		if (o.slug_field.data("auto-update")) {
	 			update_episode_slug(title);
	 		}
	 	};

	 	// current ajax object to ensure only the latest one is active
	 	var update_episode_slug_xhr;

	 	function update_episode_slug(title) {

	 		if (update_episode_slug_xhr)
	 			update_episode_slug_xhr.abort();

	 		update_episode_slug_xhr = $.ajax({
	 			url: ajaxurl,
	 			data: {
	 				action: 'podlove-episode-slug',
	 				title: title,
	 			},
	 			context: o.slug_field
	 		}).done(function(slug) {
	 			$(this)
	 				.val(slug)
		 			.blur();
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
	 				var url                 = $checkbox.data('fileUrl');
	 				var media_file_base_uri = PODLOVE.trailingslashit($container.find('input[name="show-media-file-base-uri"]').val());
	 				var size                = $checkbox.data('size');

	 				var readable_size = human_readable_size( size );
	 				var filename      = url.replace(media_file_base_uri, "");
	 				var $row          = $checkbox.closest(".media_file_row");

	 				var isNumber = function (obj) { return !isNaN(parseFloat(obj)) };

	 				if (readable_size === "???") {
	 					size_html = '<span style="color:red">File not found!</span>';
	 					$row.find(".status").html('<i class="podlove-icon-remove"></i>');
	 				} else {
	 					if (isNumber(size)) {
		 					size_html = '<span style="color:#0a0b0b" title="' + readable_size + '">' + size + ' Bytes</span>';	
	 					} else {
	 						size_html = '<span>' + size + '</span>';	
	 					}
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
 			if (e) {
	 			e.preventDefault();
 			}
 			$(".update_media_file").click();
 		};

 		$.subscribe("/auphonic/production/status/done", update_all_media_files);
 		$.subscribe("/auphonic/production/status/results_imported", update_all_media_files);
 		$(document).on("click", "#update_all_media_files", update_all_media_files);

 		$(document).on("click", ".update_media_file", function(e) {
 			e.preventDefault();

 			var container = $(this).closest(".media_file_row");
 			var file = container.find("input").data();

 			var data = {
 				action: 'podlove-file-update',
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
 					var input = container.find("input");
 					if (result && result.file_size > 0 && result.reachable) {
 						if (result.file_size === 1) {
 							input.data('size' , 'unknown');
 						} else {
		 					input.data('size' , result.file_size);
 						}
	 					input.data('fileUrl', result.file_url);
 					} else {
	 					input.data('size'   , -1);
	 					input.data('fileUrl', "");
 					}
 				},
 				error: function(xhr, status, error) {
 					var input = container.find("input");
 					input.data('size'   , -1);
 					input.data('fileUrl', "");
 				},
 				complete: function(xhr, status) {
 					ajax_requests.pop();
 					o.update_preview_row(container);
 				}
 			});
 			ajax_requests.push(request);

 			return false;
 		});

		o.slug_field
			.on('slugHasChanged', function() {
				maybe_update_media_files();
			})
			.data("auto-update", !Boolean(o.slug_field.val())) // only auto-update if it is empty
			.on("keyup", function() {
				o.slug_field.data("auto-update", false); // stop autoupdate on manual change
			})
		;

 		$(document).ready(function() {
 			// check all media files on page load
 			// wait a while because it shouldn't slow down loading the rest of the page
 			if (o.slug_field.val().length > 0) {
	 			setTimeout(function() { update_all_media_files(); }, 2000);
 			}
 		});

		var typewatch = (function() {
			var timer = 0;
			return function(callback, ms) {
				clearTimeout (timer);
				timer = setTimeout(callback, ms);
			}
		})();

		$.subscribe("/auphonic/production/status/results_imported", function(e, production) {
			o.slug_field.trigger('slugHasChanged');
		});

		var title_input = $("#titlewrap input");

		title_input
			.on('blur', function() {
 				title_input.trigger('titleHasChanged');
 			})
			.on('keyup', function() {
				typewatch(
					function() {
						title_input.trigger('titleHasChanged');
					},
					500
				);
 			})
 			.on('titleHasChanged', function () {
	 			var title = $(this).val();

	 			// update episode title
	 			$("#_podlove_meta_title").val(title);

	 			// maybe update episode slug
	 			maybe_update_episode_slug(title);
	 		}).trigger('titleHasChanged');

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

