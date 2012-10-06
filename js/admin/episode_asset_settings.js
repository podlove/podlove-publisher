var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Show Settings Screen.
 */
(function($) {
	PODLOVE.EpisodeAssetSettings = function(container) {
		// private
		var o = {};

		// default title = extension
		// only set if title is empty
		function generate_default_media_file_title() {
			$('select[name*=media_format_id]', container).on('change', function() {
				var $container = $(this).closest('table');
				var $title = $container.find('[name*="title"]');

				var media_format_id = $(this).val();
				var format = jQuery.parseJSON(jQuery("#media_format_data").html())[media_format_id]

				if ($title.val().length === 0) {
					$title.val(format['extension']);
				}
			});
		}

		function generate_live_preview() {
			// handle preview updates
			$('input[name*="url_template"]', container).on( 'keyup', o.update_preview );
			$('input[name*="suffix"]', container).on( 'keyup', o.update_preview );
			$('#podlove_show_media_file_base_uri', container).on( 'keyup', o.update_preview );
			$('[name*="media_format_id"]', container).on( 'change', o.update_preview );
			o.update_preview();
		}

		// public
		o.update_preview = function () {
			$('input[name*="url_template"]', container).each(function() {
				var template = $(this).val();
				var $preview = $(this).closest('td').find('.url_template_preview');
				var $container = $(this).closest('table');

				var media_file_base_uri = $('#podlove_show_media_file_base_uri').val();
				var episode_slug        = 'example-episode';

				var selected_format     = $container.find('[name*="media_format_id"] option:selected').text();
				var match               = selected_format.match(/\((.*)\)/);

				if (!match) return;

				var format_extension    = match[1];

				template = template.replace( '%media_file_base_url%', media_file_base_uri );
				template = template.replace( '%episode_slug%', episode_slug );
				template = template.replace( '%format_extension%', format_extension );

				$preview.html(template);	
			});
		}

		generate_default_media_file_title();
		generate_live_preview();

		return o;
	};
}(jQuery));
