var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Dashboard Validation box.
 */
(function($) {
	PODLOVE.DashboardFeedValidation = function(container) {
		// private
		var o = {};

		function enable_validation() {

			$("#dashboard_feed_info").on('click', 'td[data-feed-id]', function() {
				var feed_id = $(this).data("feed-id");
				var redirect = $(this).data("feed-redirect");

				if (!feed_id)
					return;

				var $that = $(this);
				var data = {
					action: 'podlove-validate-feed',
					feed_id: feed_id,
					redirect: redirect
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
			});
		}

		function enable_information() {

			$("#dashboard_feed_info").on('click', 'td[data-feed-id]', function() {
				var feed_id = $(this).data("feed-id");
				var redirect = $(this).data("feed-redirect");

				if (!feed_id)
					return;

				var column_latest_item 	= $(this).prev();
				var column_size			= column_latest_item.prev();
				var column_modification = column_size.prev();

				var data = {
					action: 'podlove-feed-info',
					feed_id: feed_id,
					redirect: redirect
				};

				column_latest_item.html('<i class="podlove-icon-spinner rotate"></i>');
				column_size.html('<i class="podlove-icon-spinner rotate"></i>');
				column_modification.html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						column_size.html(result.size);
						column_modification.html(result.last_modification);
						column_latest_item.html(result.latest_item);
					}
				});

			});
		}

		enable_validation();
		enable_information();

		// fetch missing data on page load
		$("#dashboard_feed_info [data-needs-validation]").each(function() {
			$(this).removeAttr('data-needs-validation').click();
		});

		return o;		
	}
}(jQuery));