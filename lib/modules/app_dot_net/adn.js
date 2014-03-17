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

		$(document).ready(function() {
			if( $("#podlove_module_app_dot_net_adn_broadcast").is(':checked') )
				$(".row_podlove_module_app_dot_net_adn_broadcast_channel").show();
			if( $("#podlove_module_app_dot_net_adn_patter_room_announcement").is(':checked') )
				$(".row_podlove_module_app_dot_net_adn_patter_room").show();

			$(".chosen").chosen();
		});

		$("#podlove_module_app_dot_net_adn_post_delay").change(function() {
			var content = $(this).val();

			if ( !content )
				return;

			if ( content.length == 5 && content.indexOf(':') == 2 )
				return;

			if ( /[a-zA-Z]/.test( $(this).val() ) ) {
				$(this).val('');
				return;
			}

			// If string is longer than 5 or the : is misplaced it will be cut
			if ( content.length > 5 || content.indexOf(':') == 3 )
				$(this).val( content.substr( content.indexOf(':') - 2, content.indexOf(':') + 2 ) );

			// If : is missing it will be added (Number of minutes will be used to calculate the new string)
			if ( content.indexOf(':') == -1 ) {
				var sum_of_minutes = content;

				if ( sum_of_minutes.length < 2 )
					sum_of_minutes = '0' + sum_of_minutes;

				if ( sum_of_minutes < 60 ) {
					$(this).val( '00:' + sum_of_minutes );
				} else {
					sum_of_hours 	= parseInt( sum_of_minutes / 60 ).toString();
					rest_of_minutes = ( sum_of_minutes - sum_of_hours * 60 ).toString();

					if ( sum_of_hours.length < 2 )
						sum_of_hours = '0' + sum_of_hours;

					if ( rest_of_minutes.length < 2 )
						rest_of_minutes = '0' + rest_of_minutes;

					$(this).val( sum_of_hours + ':' + rest_of_minutes );
				}
			}

			// Adding 0s until the required format is achieved
			inital_timestring_length = $(this).val().length;

			while( inital_timestring_length < 5 ) {
				if ( $(this).val().length < 5 && $(this).val().indexOf(':') < 2 )
					$(this).val( '0' +  $(this).val() );

				if ( $(this).val().length < 5 && $(this).val().indexOf(':') == 2 )
					$(this).val(  $(this).val().slice( 0,  $(this).val().indexOf(':') + 1 ) + '0' +  $(this).val().slice(  $(this).val().indexOf(':') + 1,  $(this).val().length ) );			
				
				inital_timestring_length++;
			}
			
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