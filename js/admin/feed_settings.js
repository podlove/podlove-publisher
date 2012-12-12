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

		// public
		o.update_preview = function () {
			// remove trailing slash
			var url = $("#feed_subscribe_url_preview").html().substr(0, $("#feed_subscribe_url_preview").html().length - 1);
			// remove slug
			url = url.substr(0, url.lastIndexOf("/"));

			$("#feed_subscribe_url_preview").html(url + "/" + $("#podlove_feed_slug").val() + "/");
		}

		if ($("#feed_subscribe_url_preview").length && $("#podlove_feed_slug").length) {
			generate_live_preview();
		}

		return o;
	};
}(jQuery));
