var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Feed Settings Screen.
 */
(function($) {
	PODLOVE.FeedSettings = function(container) {
		// private
		var o = {};

		function generate_live_preview() {
			// handle preview updates
			$('#podlove_feed_slug', container).on( 'keyup', o.update_preview );
			o.update_preview();
		}

		function manage_redirect_url_display() {
			var http_status = $("#podlove_feed_redirect_http_status").val();

			if (http_status > 0) {
				$(".row_podlove_feed_redirect_url").show();
			} else {
				$(".row_podlove_feed_redirect_url").hide();
			}
		}

		function slugify( text ) {

			// replace non letter or digits by -
			text = text.replace(/[^-\w]+/g, '-');

			// trim
			text = text.replace(/^-+|-+$/g, '');

			// remove unwanted characters
			text = text.replace(/[^-\w]+/g, '');

			return text ? text : 'n-a';
		}

		// public
		o.update_preview = function () {
			// remove trailing slash
			var url = $("#feed_subscribe_url_preview").html().substr(0, $("#feed_subscribe_url_preview").html().length - 1);
			// remove slug
			url = url.substr(0, url.lastIndexOf("/"));

			$("#feed_subscribe_url_preview").html(url + "/" + slugify( $("#podlove_feed_slug").val() ) + "/");
		}

		if ($("#feed_subscribe_url_preview").length && $("#podlove_feed_slug").length) {
			generate_live_preview();
		}

		$("#podlove_feed_redirect_http_status").on("change", function(){
			manage_redirect_url_display();
		});
		manage_redirect_url_display();

		return o;
	};
}(jQuery));
