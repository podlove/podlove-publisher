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

		function generate_slug_live_preview() {
			// handle preview updates
			$('#podlove_feed_slug', container).on( 'keyup', o.update_url_preview );
			o.update_url_preview();
		}

		function generate_title_live_preview() {
			// handle preview updates
			$('#podlove_feed_append_name_to_podcast_title', container).change( function () {
				o.update_title_preview();
			});
			$('#podlove_feed_name', container).change( function () {
				o.update_title_preview();
			});
			o.update_title_preview();
		}

		function manage_redirect_url_display() {
			var http_status = $("#podlove_feed_redirect_http_status").val();

			if (http_status > 0) {
				$(".row_podlove_feed_redirect_url").show();
			} else {
				$(".row_podlove_feed_redirect_url").hide();
			}
		}

		function slugify(text) {

			text = text.trim();
			// replace non letter or digits by -
			text = text.replace(/[^-\w\.\~]/g, '-');

			return text ? text : 'n-a';
		}

		// public
		o.update_url_preview = function () {
			// remove trailing slash
			var url = $("#feed_subscribe_url_preview").html().substr(0, $("#feed_subscribe_url_preview").html().length - 1);
			// remove slug
			url = url.substr(0, url.lastIndexOf("/"));

			$("#feed_subscribe_url_preview").html(url + "/" + slugify( $("#podlove_feed_slug").val() ) + "/");
		}

		o.update_title_preview = function () {
			if( $("#podlove_feed_append_name_to_podcast_title").prop('checked') ) {
				$("#feed_title_preview_append").html( ' (' + $("#podlove_feed_name").val() + ')' );
			} else {
				$("#feed_title_preview_append").html('');
			}
		}

		if ($("#feed_title_preview_append").length && $("#podlove_feed_append_name_to_podcast_title").length) {
			generate_title_live_preview();
		}

		if ($("#feed_subscribe_url_preview").length && $("#podlove_feed_slug").length) {
			generate_slug_live_preview();
		}

		$("#podlove_feed_redirect_http_status").on("change", function(){
			manage_redirect_url_display();
		});
		manage_redirect_url_display();
		make_feed_list_table_sortable();

		return o;
	};
}(jQuery));
