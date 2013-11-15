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


PODLOVE.ProtectFeed = function() {
	if( jQuery("#podlove_feed_protected:checked").val() == "on" ) {
		jQuery("tr.row_podlove_feed_protection_type").show();
	}
	if( jQuery("#podlove_feed_protection_type").val() == "0" && jQuery("#podlove_feed_protected:checked").val() == "on" ) {
		jQuery("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user").show();
	}
	jQuery("#podlove_feed_protected").change(function() {
		if( jQuery("#podlove_feed_protected:checked").val() == "on" ) {
			jQuery("tr.row_podlove_feed_protection_type").show();
			if( jQuery("#podlove_feed_protection_type").val() == "0" ) {
				jQuery("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user").show();
			} 
		} else {
			jQuery("tr.row_podlove_feed_protection_type,tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user").hide();
		}
	});	
	jQuery("#podlove_feed_protection_type").change(function() {
		if( jQuery("#podlove_feed_protection_type").val() == "0" && jQuery("#podlove_feed_protected:checked").val() == "on" ) {
			jQuery("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user").show();
		} else {
			jQuery("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user").hide();
		}
	});
}