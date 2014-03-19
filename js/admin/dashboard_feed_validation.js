var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Dashboard Validation box.
 */
(function($) {
	PODLOVE.DashboardFeedValidation = function(container) {
		// private
		var o = {};

		function enable_validation() {

			$("#dashboard_feed_info td[data-feed-id]").click(function() {
				var feed_id = $(this).data("feed-id");

				if (!feed_id)
					return;

				var $that = $(this);
				var data = {
					action: 'podlove-validate-feed',
					feed_id: feed_id
				};

				$(this).html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						$that.html(result.validation_icon);
					}
				});

			});

			$("#revalidate_feeds").click(function(e) {
				e.preventDefault();

				$("#dashboard_feed_info td[data-feed-id]").each(function() {
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