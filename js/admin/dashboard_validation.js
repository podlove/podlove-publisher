var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Dashboard Validation box.
 */
(function($) {
	PODLOVE.DashboardValidation = function(container) {
		// private
		var o = {};

		function enable_validation() {

			$("#asset_status_dashboard td[data-media-file-id]").click(function() {
				var media_file_id = $(this).data("media-file-id");

				if (!media_file_id)
					return;

				var $that = $(this);
				var data = {
					action: 'podlove-update-file',
					file_id: media_file_id
				};

				$(this).html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						if (result.file_size > 0) {
							$that.html('<i class="clickable podlove-icon-ok"></i>');
						} else {
							$that.html('<i class="clickable podlove-icon-remove"></i>');
						}
					}
				});

			});

			$("#revalidate_assets").click(function(e) {
				e.preventDefault();

				$("#asset_status_dashboard td[data-media-file-id]").each(function() {
					$(this).click();
				});

				return false;
			});
		}

		// public
		enable_validation();

		return o;		
	}
}(jQuery));
