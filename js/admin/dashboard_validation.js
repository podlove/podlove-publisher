var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Dashboard Validation box.
 */
(function($) {
	PODLOVE.DashboardValidation = function(container) {
		// private
		var o = {};

		function enable_validation() {
			$("#validate_everything", container).click(function(e) {
				e.preventDefault();

				$(".episode .file").each(function() {
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
							if (result.reachable) {
								$(".status", $file).html("<span style='color:green'>ok</span>");
							} else {
								$(".status", $file).html("<span style='color:red'>unreachable</span>");
							}
						}
					});

				});
			});
		}

		// public
		enable_validation();

		return o;		
	}
}(jQuery));
