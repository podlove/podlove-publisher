var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Feed Settings Screen.
 */
(function($) {
	PODLOVE.FeedSettings = function(container) {
		// private
		var o = {};

		function make_feed_list_table_sortable() {
			$("table.feeds tbody").sortable({
				handle: '.reorder-handle',
				helper: function(event, el) {
					
					helper = $("<div></div>");
					helper.append( el.find(".title").html() );
					helper.css({
						width: $("table.feeds").width(),
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
						action: 'podlove-update-feed-position',
						feed_id: ui.item.find(".feed_id").val(),
						position: new_position
					};

					$.ajax({ url: ajaxurl, data: data, dataType: 'json'	});
				}
			});
		}

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
		make_feed_list_table_sortable();

		return o;
	};
}(jQuery));
