var PODLOVE = PODLOVE || {};

(function($){

	PODLOVE.Bitlove = function () {

		$("#podlove_feed_bitlove").on( "change", function (e) {
			handle_bitlove_request();
		});

		$("#podlove_feed_bitlove").each(function() {
			handle_bitlove_request();	
		});

		function handle_bitlove_request() {
			if( $("#podlove_feed_bitlove").is(":checked") ) {
				var feed_id = $("#podlove_feed_bitlove").data("feed-id");			
				check_on_bitlove(feed_id);
			} else {
				$(".podlove-bitlove-status").html('');
			}
		}

		function check_on_bitlove(feed_id) {
			var bitlove_status = $(".podlove-bitlove-status");
			var error_message = '<i class="podlove-icon-remove"></i> Your feed seems to be not available on Bitlove. Please check that again.';
			var success_message = '<i class="podlove-icon-ok"></i> Your feed is available via Bitlove';

			if (!feed_id)
				return;

			var $that = $(this);
			var data = {
				action: 'podlove-fetch-bitlove-url',
				feed_id: feed_id
			};

			bitlove_status.html('<i class="podlove-icon-spinner rotate"></i>');

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					if( result.bitlove_url == "" || result.bitlove_url == null ) {
						bitlove_status.html(error_message);
					} else {
						bitlove_status.html(success_message + " (<a href='" + result.bitlove_url + "'>" + result.bitlove_url + "</a>).");
					}					
				},
				error: function(result) {
					bitlove_status.html(error_message);
				}
			});
		}

	}

}(jQuery));