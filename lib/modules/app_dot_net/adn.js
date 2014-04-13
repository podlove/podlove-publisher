var PODLOVE = PODLOVE || {};

(function($){

	PODLOVE.AppDotNet = function () {
		var $textarea = $("#podlove_module_app_dot_net_adn_poster_announcement_text"),
		    $preview = $("#podlove_adn_post_preview");

		var parseUri = function (str) {
			var	o   = parseUri.options,
				m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
				uri = {},
				i   = 14;

			while (i--) uri[o.key[i]] = m[i] || "";

			uri[o.q.name] = {};
			uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
				if ($1) uri[o.q.name][$1] = $2;
			});

			return uri;
		};

		parseUri.options = {
			strictMode: false,
			key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
			q:   {
				name:   "queryKey",
				parser: /(?:^|&)([^&=]*)=?([^&]*)/g
			},
			parser: {
				strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
				loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
			}
		};

		var nl2br = function (str, is_xhtml) {
		    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
		    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
		}

		var endsWith = function (str, suffix) {
		    return str.indexOf(suffix, str.length - suffix.length) !== -1;
		}

		var update_preview = function() {
			var text = $textarea.val(),
				podcast = $preview.data('podcast'),
			    episode_link = $preview.data('episode-link'),
			    episode_subtitle = $preview.data('episode-subtitle'),
			    episode = $preview.data('episode');

			text = text.replace("{podcastTitle}", podcast);
			text = text.replace("{episodeTitle}", episode);
			text = text.replace("{episodeLink}",  episode_link);
			text = text.replace("{episodeSubtitle}", episode_subtitle);

			safetyNet = 0;
			shortened = false;
			while (safetyNet < 1000 && text.replace(/\{linkedEpisodeTitle\}/g, episode).length > 256) {
				safetyNet++;
				if (endsWith(text, "{linkedEpisodeTitle}") && episode.length > 0) {
					episode = episode.slice(0,-1); // shorten episode title by one character at a time
				} else {
					text = text.slice(0,-1); // shorten text by one character at a time
				}
				shortened = true;
			}

			text = text.replace(/\{linkedEpisodeTitle\}/g, '<a href="' + episode_link + '">' + episode + '</a> [' + parseUri(episode_link)['host'] + ']')

			if (shortened) {
				text = text + "…";
			}

			$(".adn.body", $preview).html(nl2br(text));
		};

		var adn_list_update = function( that ) {
				var category = $(that).data("category");

				if (!category)
					return;

				var data = {
					action: 'podlove-refresh-channel',
					category: category
				};

				$(that).html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						$("#podlove_module_app_dot_net_adn_" + category).children( 'option:not(:first)' ).remove();

						$.each( result, function( index, value) {
							$("#podlove_module_app_dot_net_adn_" + category).append("<option value='" + index + "'>" + value + "</option>");
						});						
						$("#podlove_module_app_dot_net_adn_" + category).trigger("liszt:updated");
						$(that).html('<i class="podlove-icon-repeat"></i>');
					}
				});
		}

		var post_to_adn = function( that ) {

				var data = {
					action: 'podlove-adn-post',
					post_id: $("#adn_manual_post_episode_selector").val()
				};

				$(".adn-post-status-pending").show();

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						$(".adn-post-status-pending").hide();
						$(".adn-post-status-ok").show().delay(750).fadeOut(200);
					}
				});
		}

		$(document).ready(function() {
			if( $("#podlove_module_app_dot_net_adn_broadcast").is(':checked') )
				$(".row_podlove_module_app_dot_net_adn_broadcast_channel").show();
			if( $("#podlove_module_app_dot_net_adn_patter_room_announcement").is(':checked') )
				$(".row_podlove_module_app_dot_net_adn_patter_room").show();
			if( $("#podlove_module_app_dot_net_adn_automatic_announcement").is(':checked') )
				$(".row_podlove_module_app_dot_net_adn_post_delay").show();

			$(".chosen").chosen();
		});

		$("#podlove_module_app_dot_net_adn_broadcast").change(function() {
			if( $(this).is(':checked') ) {
				$(".row_podlove_module_app_dot_net_adn_broadcast_channel").show();
			} else {
				$(".row_podlove_module_app_dot_net_adn_broadcast_channel").hide();
			}
			
			$(".chosen").chosen();
		});

		$("#podlove_module_app_dot_net_adn_patter_room_announcement").change(function() {
			if( $(this).is(':checked') ) {
				$(".row_podlove_module_app_dot_net_adn_patter_room").show();
			} else {
				$(".row_podlove_module_app_dot_net_adn_patter_room").hide();
			}

			$(".chosen").chosen();
		});

		$("#podlove_module_app_dot_net_adn_automatic_announcement").change(function() {
			if( $(this).is(':checked') ) {
				$(".row_podlove_module_app_dot_net_adn_post_delay").show();
			} else {
				$(".row_podlove_module_app_dot_net_adn_post_delay").hide();
			}

			$(".chosen").chosen();
		});

		$(".podlove_adn_patter_refresh, .podlove_adn_broadcast_refresh").on( 'click', function() {
			adn_list_update( this );
		});

		$("#adn_manual_post_alpha").on( 'click', function() {
			post_to_adn( this );
		});
		
		jQuery("#podlove_module_app_dot_net_adn_poster_announcement_text").autogrow();
		jQuery(".adn-dropdown").chosen(); 

		$textarea.keyup(function() {
			update_preview();
		});

		update_preview();
	}

}(jQuery));


jQuery(function($) {
	PODLOVE.AppDotNet();
});