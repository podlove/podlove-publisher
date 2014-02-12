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

function convert_to_slug(string) {
	string = string.toLowerCase();
	string = string.replace(/\s+/g, '-');
	string = string.replace(/[\u00e4]/g, 'ae');
	string = string.replace(/[\u00f6]/g, 'oe');
	string = string.replace(/[\u00fc]/g, 'ue');
	string = string.replace(/[\u00df]/g, 'ss');
	string = string.replace(/[^\w\-]+/g, '');
	string = escape(string);
	return string;
}

function auto_fill_form(id, title_id) {
	(function($) {
		switch( id ) {
			case 'contributor':
				if( $("#podlove_contributor_publicname").val() == "" ) {
					if( $("#podlove_contributor_realname").val() == "" ) {
						$("#podlove_contributor_publicname").attr( 'placeholder', $("#podlove_contributor_nickname").val() );
					} else {
						$("#podlove_contributor_publicname").attr( 'placeholder', $("#podlove_contributor_realname").val() );
					}											
				}
			break;
			case 'contributor_group':
				if( $("#podlove_contributor_group_slug").val() == "" ) {
					$("#podlove_contributor_group_slug").val( convert_to_slug( $("#podlove_contributor_" + title_id).val() ) );
				}
			break;
			case 'contributor_role':
				if( $("#podlove_contributor_role_slug").val() == "" ) {
					$("#podlove_contributor_role_slug").val( convert_to_slug( $("#podlove_contributor_" + title_id).val() ) );
				}
			break;
		}

		
	}(jQuery));
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

	$("#podlove_contributor_publicname").change(function() {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_realname").change(function() {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_nickname").change(function() {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_group_title").change(function() {
		auto_fill_form('contributor_group', 'group_title');
	});

	$("#podlove_contributor_role_title").change(function() {
		auto_fill_form('contributor_role', 'role_title');
	});

	$(document).ready(function() {
		auto_fill_form('contributor', 'realname');
	});
	
});

