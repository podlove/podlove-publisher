var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Show Settings Screen.
 */
(function($) {
	PODLOVE.EpisodeAssetSettings = function(container) {
		// private
		var o = {};

		function make_asset_list_table_sortable() {
			$("table.episode_assets tbody").sortable({
				helper: function(event, el) {
					
					helper = $("<div></div>");
					helper.append( el.find(".title").html() );
					helper.css({
						width: $("table.episode_assets").width(),
						background: 'rgba(255,255,255,0.66)',
						boxSizing: 'border-box',
						padding: 5
					});

					return helper;
				},
				update: function( event, ui ) {
					// console.log(ui);
					var prev = parseFloat(ui.item.prev().find(".position").val()),
					    next = parseFloat(ui.item.next().find(".position").val()),
					    new_position = 0;

					if ( ! prev ) {
						new_position = next / 2;
					} else if ( ! next ) {
						new_position = prev + 1;
					} else {
						new_position = prev + (next - prev) / 2
					}

					// update UI
					ui.item.find(".position").val(new_position);

					// persist
					var data = {
						action: 'podlove-update-asset-position',
						asset_id: ui.item.find(".asset_id").val(),
						position: new_position
					};

					$.ajax({ url: ajaxurl, data: data, dataType: 'json'	});
				}
			});
		}

		function filter_file_formats_by_asset_type() {
			$('select[name=podlove_episode_asset_type]', container).on('change', function() {
				var $container = $(this).closest('table');
			
				$("#option_storage option").remove().appendTo($("#podlove_episode_asset_file_type_id"));
				$("#podlove_episode_asset_file_type_id option[data-type!='" + $(this).val() + "']").remove().appendTo($("#option_storage"));
				$('select[name*=file_type_id]').change();
			});
		}

		// set default asset title
		function generate_default_episode_asset_title() {
			$('select[name*=file_type_id]', container).on('change', function() {
				var $container = $(this).closest('table');
				var $title = $container.find('[name*="title"]');
				// if ($title.val().length === 0) {
					$title.val($("option:selected", this).data('name'));
				// }
			});
		}

		function generate_live_preview() {
			// handle preview updates
			$('input[name*="url_template"]', container).on( 'keyup', o.update_preview );
			$('input[name*="suffix"]', container).on( 'keyup', o.update_preview );
			$('#podlove_show_media_file_base_uri', container).on( 'keyup', o.update_preview );
			$('select[name="podlove_episode_asset_type"]', container).on( 'change', o.update_preview );
			$('[name*="file_type_id"]', container).on( 'change', o.update_preview );
			o.update_preview();
		}

		// public
		o.update_preview = function () {
			$('#url_preview', container).each(function() {
				var template = $("#url_template").html();
				var $preview = $("#url_preview");
				var $container = $(this).closest('table');

				var media_file_base_uri = $('#podlove_show_media_file_base_uri').val();
				var episode_slug        = '<span style="font-style:italic; font-weight:100">episode-slug</span>';
				var suffix              = $('input[name*="suffix"]').val();

				var selected_file_type  = $container.find('[name*="file_type_id"] option:selected').text();
				var format_extension    = $container.find('[name*="file_type_id"] option:selected').data('extension');

				if (!format_extension) {
					$preview.html('Please select file format');
					return;
				}

				template = template.replace( '%media_file_base_url%', '<span style="color:grey">' + media_file_base_uri );
				template = template.replace( '%episode_slug%', episode_slug + "</span>" );
				template = template.replace( '%suffix%', suffix );
				template = template.replace( '%format_extension%', format_extension );

				$preview.html(template);	
			});
		}

		generate_default_episode_asset_title();
		filter_file_formats_by_asset_type();
		generate_live_preview();
		make_asset_list_table_sortable();

		return o;
	};
}(jQuery));
