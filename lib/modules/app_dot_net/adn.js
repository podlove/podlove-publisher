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