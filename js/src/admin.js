var PODLOVE = PODLOVE || {};

// jQuery Tiny Pub/Sub
// https://github.com/cowboy/jquery-tiny-pubsub
(function ($) {
	var o = $({});
	$.subscribe = function () {
		o.on.apply(o, arguments);
	};

	$.unsubscribe = function () {
		o.off.apply(o, arguments);
	};

	$.publish = function () {
		o.trigger.apply(o, arguments);
	};
}(jQuery));


jQuery.ajaxSetup({
    beforeSend: function (xhr, settings) {
        if (settings.url.includes("wp-json")) {
            xhr.setRequestHeader("X-WP-Nonce", podlove_vue.nonce);
        }
    },
});

PODLOVE.rtrim = function (string, thechar) {
	var re = new RegExp(thechar + "+$", "g");
	return string.replace(re, '');
}

PODLOVE.untrailingslashit = function (url) {
	return PODLOVE.rtrim(url, '/');
}

PODLOVE.trailingslashit = function (url) {
	return PODLOVE.untrailingslashit(url) + '/';
}

PODLOVE.toDurationFormat = function (float_seconds) {
	var sec_num = parseInt(float_seconds, 10);
	var hours = Math.floor(sec_num / 3600);
	var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
	var seconds = sec_num - (hours * 3600) - (minutes * 60);
	var milliseconds = Math.round((float_seconds % 1) * 1000);

	if (hours < 10) {
		hours = "0" + hours;
	}
	if (minutes < 10) {
		minutes = "0" + minutes;
	}
	if (seconds < 10) {
		seconds = "0" + seconds;
	}
	var time = hours + ':' + minutes + ':' + seconds;

	if (milliseconds) {
		time += '.' + milliseconds;
	};

	return time;
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

function fix_url(string) {
	if (!string) {
		return null;
	}
	var url = string;
	try   { url = new URL(string) } 
	catch { url = new URL((string.indexOf("@") != -1 ? 'acct:' : 'https://') + string) };
	if ( url.protocol === 'http:' ) {
		url.protocol = 'https:'
	}
	return PODLOVE.untrailingslashit(url.toString());
}


function lookup_identifier(service, id) {
	try {
		return jQuery.getJSON(podlove_vue.rest_url + "podlove/v1/social/lookup/" + service, {'id': id })
		// returns 404 when nothing is found, 
		// TODO: how to I supress the error message in the browser's JavaScript Console?
	} 
	catch { }
}

function auto_fill_form(id, title_id) {
	(function ($) {
		function find_profile(identifier, type) {
			// identier is probably an URI
			if ( type !== 'email' && identifier.indexOf(":") !== -1 ) {
				lookup_identifier('webfinger', identifier).done(function(webfinger) {
					console.debug("webfinger lookup response", webfinger);
					fill_if_empty('#podlove_contributor_guid', webfinger.subject);
					fill_if_empty('#podlove_contributor_privateemail', webfinger.alias);

					// TODO: Add social media accounts from webfinger.aliases to datatable
					fill_person_from_links(webfinger.links);
				});
				return null;
			} 
			// identier is probably a string in form user@domain.tld
			else {
				lookup_identifier('webfinger', 'acct:' + identifier).done((webfinger) => {
					console.debug("webfinger lookup response", webfinger);
					if (webfinger) {
						// allways overwrite URI with subject from response
						$('#podlove_contributor_guid').val(webfinger.subject);
						// TODO: Add social media accounts from webfinger.aliases to datatable
						fill_person_from_links(webfinger.links);
					}
				}).fail(() => lookup_identifier('gravatar.com', identifier).done((gravatar) => {
					console.debug("gravatar lookup response", gravatar);
					fill_if_empty('#podlove_contributor_guid', fix_url(gravatar.urls?.[0]?.value || gravatar.accounts?.pop()?.url || 'https://' + identifier.split('@')[1]));
					fill_if_empty('#podlove_contributor_identifier', gravatar.preferredUsername);
					fill_if_empty('#podlove_contributor_realname', gravatar.name.formatted 
						|| [gravatar.name.givenName, gravatar.name.familyName].join(' ') 
						|| gravatar.preferredUsername);
					fill_if_empty('#podlove_contributor_publicname', gravatar.name.formatted || gravatar.preferredUsername);
					fill_if_empty('#podlove_contributor_avatar', gravatar.thumbnailUrl, true);
				}));	
			}
		}
		
		function fill_person_from_links(links) {
			// lookup links[rel=self] for name, avatar, etc.
			var self = links.filter(x => x.rel === 'self');
			if (self.length > 0) {
				lookup_identifier('json', self[0].href).done((mastodon) => {
					console.debug("links.self person lookup response", mastodon);
					fill_if_empty('#podlove_contributor_identifier', mastodon.preferredUsername);
					fill_if_empty('#podlove_contributor_realname', mastodon.name || mastodon.preferredUsername);
					fill_if_empty('#podlove_contributor_publicname', mastodon.name || mastodon.preferredUsername);
					fill_if_empty('#podlove_contributor_avatar', mastodon.icon.url, true);
				});
			}
		}

		function fill_if_empty(field, value, triggerChangeEvent) {
			var input = $(field);
			if ( input.val() == "" && value ) {
				input.val(value);
				if (triggerChangeEvent) {
					input.change();
				}
				return true;
			}
		}


		switch (id) {
			case 'contributor':
				if ($("#podlove_contributor_publicname").val() == "") {
					if ($("#podlove_contributor_realname").val() == "") {
						$("#podlove_contributor_publicname").attr('placeholder', $("#podlove_contributor_nickname").val());
					} else {
						$("#podlove_contributor_publicname").attr('placeholder', $("#podlove_contributor_realname").val());
					}
				}
				if ($("#podlove_contributor_guid").val() == "") {
					if ($("#podlove_contributor_realname").val() != "") {
						$("#podlove_contributor_publicname").attr('placeholder', $("#podlove_contributor_nickname").val());
					}
				}
				break;
			case 'contributor_email':
				if ($("#podlove_contributor_avatar").val() == "") {
					var email = $("#podlove_contributor_privateemail").val();
					if (email != "") {
						find_profile(email, 'email');
					}
				}
				break;
			case 'contributor_guid':
				if ($("#podlove_contributor_avatar").val() == "") {
					var guid = $("#podlove_contributor_guid").val();
					if (guid != "") {
						find_profile(guid, 'uri');
					}
				}
				break;
			case 'contributor_group':
				if ($("#podlove_contributor_group_slug").val() == "") {
					$("#podlove_contributor_group_slug").val(convert_to_slug($("#podlove_contributor_" + title_id).val()));
				}
				break;
			case 'contributor_role':
				if ($("#podlove_contributor_role_slug").val() == "") {
					$("#podlove_contributor_role_slug").val(convert_to_slug($("#podlove_contributor_" + title_id).val()));
				}
				break;
		}


	}(jQuery));
}

/**
 * HTML-based input behavior for text fields.
 *
 * To activate behavior, add class `podlove-check-input`.
 *
 * - trims whitespace from beginning and end
 *
 * Add these data attributes to add further behavior:
 *
 * - `data-podlove-input-type="url"`   : verifies against URL regex
 * - `data-podlove-input-type="avatar"`: verifies against URL or email regex
 * - `data-podlove-input-type="email"` : verifies against email regex
 * - `data-podlove-input-remove="@ +"` : removes given whitespace separated list of characters from input
 *
 * Expects HTML to be in the following form:
 *
 * ```html
 * <input type="text" id="inputid" class="podlove-check-input">
 * <span class="podlove-input-status" data-podlove-input-status-for="inputid"></span>
 * ```
 */
function clean_up_input() {
	(function ($) {
		$(".podlove-check-input").on('change', function () {
			var textfield = $(this);
			var textfieldid = textfield.attr("id");
			var $status = $(".podlove-input-status[data-podlove-input-status-for=" + textfieldid + "]");

			textfield.removeClass("podlove-invalid-input");
			$status.removeClass("podlove-input-isinvalid");

			function ShowInputError(message) {
				$status.text(message);

				textfield.addClass("podlove-invalid-input");
				$status.addClass("podlove-input-isinvalid");

				// abort further change events, hopefully
				return false;
			}

			// trim whitespace
			textfield.val(textfield.val().trim());

			// remove blacklisted characters
			if (inputType = $(this).data("podlove-input-remove")) {
				characters = $(this).data("podlove-input-remove").split(' ');
				$.each(characters, function (index, character) {
					textfield.val(textfield.val().replace(character, ''));
				});
			}

			// handle special input types
			if (inputType = $(this).data("podlove-input-type")) {
				$status.text('');

				if ($(this).val() == '')
					return;

				switch (inputType) {
					case "url":
						valid_url_regexp = /^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i;

						if (!textfield.val().match(valid_url_regexp)) {
							// Encode URL only if it is not already encoded
							if (!encodeURI(textfield.val()).match(valid_url_regexp)) {
								return ShowInputError('Please enter a valid URL');
							} else {
								textfield.val(encodeURI(textfield.val()));
							}
						}
						break;
					case "avatar":
						if (!textfield.val().match(/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/i)) {
							// textfield.val( encodeURI( textfield.val() ) );

							if (!textfield.val().match(/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i)) {
								return ShowInputError('Please enter a valid email adress or a valid URL');
							}
						}
						break;
					case "email":
						if (!textfield.val().match(/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/i)) 
							return ShowInputError('Please enter a valid email adress.');
						break;
				}
			}
		});
	}(jQuery));
}

/**
 * Initialize contextual help links.
 *
 *	Use like this:
 *
 *  <a href="#" data-podlove-help="help-tab-id">?</a>
 */
function init_contextual_help_links() {
	jQuery("a[data-podlove-help]").on("click", function (e) {
		var help_id = jQuery(this).data('podlove-help');

		e.preventDefault();

		// Remove 'active' class from all link tabs
		jQuery('li[id^="tab-link-"]').each(function () {
			jQuery(this).removeClass('active');
		});

		// Hide all panels
		jQuery('div[id^="tab-panel-"]').each(function () {
			jQuery(this).css('display', 'none');
		});

		// Set our desired link/panel
		jQuery('#tab-link-' + help_id).addClass('active');
		jQuery('#tab-panel-' + help_id).css('display', 'block');

		// Force click on the Help tab
		if (jQuery('#contextual-help-link').attr('aria-expanded') === "false") {
			jQuery('#contextual-help-link').click();
		}

		// Force scroll to top, so you can actually see the help
		window.scroll(0, 0);
	});
}

jQuery(function ($) {

	$("#_podlove_meta_recording_date").datepicker({
		dateFormat: 'yy-mm-dd'
	});

	$("#dashboard_feed_info").each(function () {
		PODLOVE.DashboardFeedValidation($(this));
	});

	$("#asset_validation").each(function () {
		PODLOVE.DashboardAssetValidation($(this));
	});

	$("#podlove_podcast").each(function () {
		PODLOVE.Episode($(this));
	});

	$("#podlove_episode_assets, table.episode_assets").each(function () {
		PODLOVE.EpisodeAssetSettings($(this));
	});

	$(".wrap").each(function () {
		PODLOVE.FeedSettings($(this));
	});

	$(".row_podlove_feed_protected").each(function () {
		PODLOVE.ProtectFeed();
	});

	$("#podlove_contributor_publicname").change(function () {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_realname").change(function () {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_nickname").change(function () {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_privateemail").change(function () {
		auto_fill_form('contributor_email', 'email');
	});

	$("#podlove_contributor_group_title").change(function () {
		auto_fill_form('contributor_group', 'group_title');
	});

	$("#podlove_contributor_role_title").change(function () {
		auto_fill_form('contributor_role', 'role_title');
	});

	$(document).ready(function () {
		auto_fill_form('contributor', 'realname');
		// TODO auto_fill_form('contributor_guid', 'guid'); from social media accounts
		clean_up_input();
		init_contextual_help_links();
		new ClipboardJS('.clipboard-btn');
	});

	const guid = $("#podlove_contributor_guid");
	guid.change(function () {
		guid.val(fix_url(guid.val()));
		auto_fill_form('contributor_guid', 'guid');
	});

});
