var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Show Settings Screen.
 */
(function($) {
	PODLOVE.EpisodeAssetSettings = function(container) {
		// private
		var o = {};

		function filter_file_formats_by_asset_type() {
			$('select[name=podlove_episode_asset_type]', container).on('change', function() {
				var $container = $(this).closest('table');
			
				$("#option_storage option").remove().appendTo($("#podlove_episode_asset_file_type_id"));
				$("#podlove_episode_asset_file_type_id option[data-type!='" + $(this).val() + "']").remove().appendTo($("#option_storage"));
			});
		}

		// default title = extension
		// only set if title is empty
		function generate_default_episode_asset_title() {
			$('select[name*=file_type_id]', container).on('change', function() {
				var $container = $(this).closest('table');
				var $title = $container.find('[name*="title"]');
				if ($title.val().length === 0) {
					var extension = $("option:selected", this).text().match(/\((.*)\)/)[1];
					$title.val(extension);
				}
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
				var episode_slug        = 'example-episode';
				var suffix              = $('input[name*="suffix"]').val();

				var selected_file_type  = $container.find('[name*="file_type_id"] option:selected').text();
				var match               = selected_file_type.match(/\((.*)\)/);

				if (!match) {
					$preview.html('Please select file format');
					return;
				}

				var format_extension    = match[1];

				template = template.replace( '%media_file_base_url%', media_file_base_uri );
				template = template.replace( '%episode_slug%', episode_slug );
				template = template.replace( '%suffix%', suffix );
				template = template.replace( '%format_extension%', format_extension );

				$preview.html(template);	
			});
		}

		generate_default_episode_asset_title();
		filter_file_formats_by_asset_type();
		generate_live_preview();

		return o;
	};
}(jQuery));
