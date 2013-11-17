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

	$(".podlove_podcast_license_image").each(function() {
		PODLOVE.license();
	});

	$(".autogrow").autogrow();
	
});

PODLOVE.license = function() {
	jQuery(license.form_type).change(function() {
		if(jQuery(license.form_type).val() !== "") {
			podlove_toggle_license_form(jQuery(license.form_type).val());
			podlove_check_license_form(jQuery(license.form_type).val());
			jQuery(license.status).show();
		} else {
			jQuery(license.form_row_other+","+license.form_row_cc_preview).hide();
			jQuery(license.form_row_cc).hide();										
			jQuery(license.status).hide();
		}
	});			
	jQuery(license.form_row_cc+","+license.form_row_other).change(function(){
		podlove_check_license_form(jQuery(license.form_type).val());
	});
	podlove_check_license_form(jQuery(license.form_type).val());
}

function podlove_license_cc_get_image(allow_modifications, commercial_use) {
	var banner_identifier_allowed_modification, banner_identifier_commercial_use;

	switch (allow_modifications) {
		case "yes" :
			banner_identifier_allowed_modification = 1;
		break;
		case "yesbutshare" :
			banner_identifier_allowed_modification = 10;
		break;
		case "no" :
			banner_identifier_allowed_modification = 0;
		break;
		default :
			banner_identifier_allowed_modification = 1;
		break;
	}

	banner_identifier_commercial_use = (commercial_use == "no") ? "0" : "1";

	return banner_identifier_allowed_modification + "_" + banner_identifier_commercial_use;
}

function podlove_check_license_form(license_type) {
	switch (license_type) {
		case "cc" :
			jQuery(license.form_row_cc_preview).show();
			if(jQuery(license.form_cc_modification).val() == "" ||
			   jQuery(license.form_cc_commercial_use).val() == "" ||
			   jQuery(license.form_cc_jurisdiction).val() == "") {
				jQuery(license.status).html("<i class=\"podlove-icon-remove\"></i> Additional parameters license parameters need to be set.");
				podlove_toggle_license_form("cc");
				jQuery(license.form_row_cc_preview).hide();
			} else {
				if(jQuery(license.form_cc_jurisdiction).val() == "international") {
					var country = '';
					var version_and_name = versions[jQuery(license.form_cc_jurisdiction).val()];
					var name = version_and_name.name;
				} else {
					var country = jQuery(license.form_cc_jurisdiction).val()+'/';
					var version_and_name = versions[jQuery(license.form_cc_jurisdiction).val()];
					var name = locales[jQuery(license.form_cc_jurisdiction).val()];
				}
				jQuery(license.status).html("<i class=\"podlove-icon-ok\"></i> All license parameter are set. You can <a href=\"javascript:podlove_toggle_license_form('cc')\">edit</a> the license parameters.");			
				jQuery(license.image).html("<div class=\"podlove_cc_license\"><img src=\"" + plugin_url + "/images/cc/" + podlove_license_cc_get_image(jQuery(license.form_cc_modification).val(), jQuery(license.form_cc_commercial_use).val()) + ".png\" /> <p>This work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by/"+version_and_name.version+"/"+country+"deed.en\">Creative Commons Attribution "+version_and_name.version+" "+name+" License</a>.</p></div>");
			}
		break;
		case "other" :
			if(jQuery(license.form_other_url).val() == "" ||
			   jQuery(license.form_other_name).val() == "") {
				jQuery(license.status).html("<i class=\"podlove-icon-remove\"></i> You need to select additonal options.");
				podlove_toggle_license_form("other");
				jQuery(license.form_row_cc_preview).hide();
			} else {
				jQuery(license.status).html("<i class=\"podlove-icon-ok\"></i> All license parameter are set. You can <a href=\"javascript:podlove_toggle_license_form('other')\">edit</a> the license parameters.");
				jQuery(license.form_row_cc_preview).show();
				jQuery(license.image).html("<div class=\"podlove_license\"><p>This work is licensed under the <a rel=\"license\" href=\""+jQuery(license.form_other_url).val()+"\">"+jQuery(license.form_other_name).val()+"</a> license.</p></div>");
			}
			
		break;
	}
}

function podlove_toggle_license_form(license_type) {
	switch(license_type) {
		case "cc" :
			jQuery(license.form_row_other).hide();
			jQuery(license.form_row_cc).show();
		break;
		case "other" :
			jQuery(license.form_row_other).show();
			jQuery(license.form_row_cc).hide();
		break;
	}
}
