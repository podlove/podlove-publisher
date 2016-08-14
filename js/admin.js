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

PODLOVE.toDurationFormat = function (float_seconds) {
	var sec_num = parseInt(float_seconds, 10);
	var hours   = Math.floor(sec_num / 3600);
	var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
	var seconds = sec_num - (hours * 3600) - (minutes * 60);
	var milliseconds = Math.round((float_seconds % 1) * 1000);

	if (hours   < 10) {hours   = "0"+hours;}
	if (minutes < 10) {minutes = "0"+minutes;}
	if (seconds < 10) {seconds = "0"+seconds;}
	var time = hours+':'+minutes+':'+seconds;

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
	(function($) {
		$(".podlove-check-input").on('change', function() {
			var textfield = $(this);
			var textfieldid = textfield.attr("id");
			var $status = $(".podlove-input-status[data-podlove-input-status-for=" + textfieldid + "]");

			textfield.removeClass("podlove-invalid-input");
			$status.removeClass("podlove-input-isinvalid");

			function ShowInputError(message) {
				$status.text(message);

				textfield.addClass("podlove-invalid-input");
				$status.addClass("podlove-input-isinvalid");
			}

			// trim whitespace
			textfield.val( textfield.val().trim() );

			// remove blacklisted characters
			if ( inputType = $(this).data("podlove-input-remove") ) {
				characters = $(this).data("podlove-input-remove").split(' ');
				$.each( characters, function(index, character) {
					textfield.val( textfield.val().replace(character, '') );
				} );
			}
			
			// handle special input types
			if ( inputType = $(this).data("podlove-input-type") ) {
				$status.text('');

				if ( $(this).val() == '' )
					return;

				switch(inputType) {
					case "url":
						// Encode the text field into valid URL before we check if it is valid
						textfield.val( encodeURI( textfield.val() ) );

						if ( ! textfield.val().match(/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i) ) {
							ShowInputError('Please enter a valid URL');
						}		 				
					break;
					case "avatar":
						if ( ! textfield.val().match(/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/i) ) {
							// textfield.val( encodeURI( textfield.val() ) );

							if ( ! textfield.val().match(/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i) ) {
								ShowInputError('Please enter a valid email adress or a valid URL');
							}
						}
					break;
					case "email":
						if ( ! textfield.val().match(/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/i) )
							ShowInputError('Please enter a valid email adress.');
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
		jQuery('li[id^="tab-link-"]').each(function(){
		    jQuery(this).removeClass('active');
		});

		// Hide all panels
		jQuery('div[id^="tab-panel-"]').each(function(){
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

jQuery(function($) {

	$( "#_podlove_meta_recording_date" ).datepicker({ dateFormat: 'yy-mm-dd'});

	$("#dashboard_feed_info").each(function() {
		PODLOVE.DashboardFeedValidation($(this));
	});
	
	$("#asset_validation").each(function() {
		PODLOVE.DashboardAssetValidation($(this));
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

	$("#podlove_feed_bitlove").each(function() {
		PODLOVE.Bitlove();
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
		clean_up_input();
		init_contextual_help_links();
	});
	
});

