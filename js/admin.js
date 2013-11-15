var PODLOVE = PODLOVE || {};

// jQuery Tiny Pub/Sub
// https://github.com/cowboy/jquery-tiny-pubsub
(function($) {
	var o = $({});
	$.subscribe = function() {
		o.on.apply(o, arguments);
	};

	$.unsubscribe = function() {
		o.off.apply(o, arguments);
	};

	$.publish = function() {
		o.trigger.apply(o, arguments);
	};
}(jQuery));

PODLOVE.rtrim = function (string, thechar) {
	var re = new RegExp(thechar + "+$","g");
	return string.replace(re, '');
}

PODLOVE.untrailingslashit = function (url) {
	return PODLOVE.rtrim(url, '/');
}

PODLOVE.trailingslashit = function (url) {
	return PODLOVE.untrailingslashit(url) + '/';
}

function human_readable_size(size) {
	if (!size || size < 1) {
		return "???";
	}

	var kilobytes = size / 1024;

	if (kilobytes < 500) {
		return kilobytes.toFixed(2) + " kB";
	}

	var megabytes = kilobytes / 1024
	return megabytes.toFixed(2) + " MB";
}

(function($) {
	PODLOVE.ProtectFeed = function() {
		var $protection = $("#podlove_feed_protected"),
			$protection_row = $("tr.row_podlove_feed_protection_type"),
			$protection_type = $("#podlove_feed_protection_type"),
			$credentials = $("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user");

		var protectionIsActive = function() {
			return $protection.is(":checked");
		};

		var isCustomLogin = function() {
			return $protection_type.val() == "0";
		};

		if (protectionIsActive()) {
			$protection_row.show();
		}
		
		if (protectionIsActive() && isCustomLogin()) {
			$credentials.show();
		}

		$("#podlove_feed_protected").on("change", function() {
			if (protectionIsActive()) {
				$protection_row.show();
				if (isCustomLogin()) {
					$credentials.show();
				} 
			} else {
				$protection_row.hide();
				$credentials.hide();
			}
		});	

		$protection_type.change(function() {
			if (protectionIsActive() && isCustomLogin()) {
				$credentials.show();
			} else {
				$credentials.hide();
			}
		});
	}
}(jQuery));

jQuery(function($) {

	$("#validation").each(function() {
		PODLOVE.DashboardValidation($(this));
	});

	$("#podlove_podcast").each(function() {
		PODLOVE.Episode($(this));
	});

	$("#podlove_episode_assets, table.episode_assets").each(function() {
		PODLOVE.EpisodeAssetSettings($(this));
	});

	$(".wrap").each(function() {
		PODLOVE.FeedSettings($(this));
	});

	$(".row_podlove_feed_protected").each(function() {
		PODLOVE.ProtectFeed();
	});

	$(".autogrow").autogrow();
	
});