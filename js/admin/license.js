var PODLOVE = PODLOVE || {};

(function($) {
	PODLOVE.License = function(settings) {
		var podlove_license_cc_get_image = function (allow_modifications, commercial_use) {
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
		};

		var podlove_change_url_preview_and_name_from_form = function(version_value, modification_value, commercial_use_value, jurisdiction_value) {
			if (!version_value || !modification_value || !commercial_use_value || !jurisdiction_value )
				return;

			var $that = $(this);
			var data = {
				action: 'podlove-get-license-url',
				version: version_value,
				modification: modification_value,
				commercial_use: commercial_use_value,
				jurisdiction: jurisdiction_value
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(settings.license_url_field_id).val(result);
					$(".podlove-license-link").attr("href", result);
				}
			});

			// Redifining the required AJAX action (for license name)
			data.action = 'podlove-get-license-name';

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(settings.license_name_field_id).val(result);
					$(".podlove-license-link").html(result);
					$(".podlove-license-link").attr("alt", result);
				}
			});

			$(".podlove_podcast_license_image").html(podlove_get_license_image(version_value, modification_value, commercial_use_value));
			$(".row_podlove_podcast_license_preview").show();
		};

		var podlove_get_license_image = function(version_value, modification_value, commercial_use_value) {
			if (version_value == 'cc0') {
				return '<img src="' + settings.plugin_url + '/images/cc/pd.png" alt="" />';
			} else if (version_value == 'pdmark') {
				return '<img src="' + settings.plugin_url + '/images/cc/pdmark.png" alt="" />';
			} else if (version_value == 'cc4') {
				return '<img src="' + settings.plugin_url + '/images/cc/1_1.png" alt="" />';
			} else {
				return '<img src="' + settings.plugin_url + '/images/cc/' + podlove_license_cc_get_image(modification_value, commercial_use_value) + '.png" alt="" />';
			}
		};

		var podlove_filter_license_selector = function(license_version) {
			switch(license_version) {
				case 'cc3':
					$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction").closest('div').show();
				break;
				case 'cc4':
					$("#license_cc_allow_modifications, #license_cc_allow_commercial_use").closest('div').show();
					$("#license_cc_license_jurisdiction").closest('div').hide();
				break;
				default:
					$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction").closest('div').hide();
				break;
			}
		};

		var podlove_populate_license_form = function(version_value, modification_value, commercial_use_value, jurisdiction_value) {
			$("#license_cc_version").find('option[value=' + version_value + ']').attr('selected','selected');
			$("#license_cc_allow_modifications").find('option[value=' + modification_value + ']').attr('selected','selected');
			$("#license_cc_allow_commercial_use").find('option[value=' + commercial_use_value + ']').attr('selected','selected');
			$("#license_cc_license_jurisdiction").find('option[value=' + jurisdiction_value + ']').attr('selected','selected');

			podlove_filter_license_selector($("#license_cc_version").val());

			$(".podlove_podcast_license_image").html(podlove_get_license_image(version_value, modification_value, commercial_use_value));
			
			var data = {
				action: 'podlove-get-license-name',
				version: version_value,
				modification: modification_value,
				commercial_use: commercial_use_value,
				jurisdiction: jurisdiction_value
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(".podlove-license-link").html(result);
					$(".podlove-license-link").attr('href', $("#podlove_podcast_license_url").val())
				}
			});

			if( $(settings.license_name_field_id).val() == '' || $(settings.license_url_field_id).val() == '' )
				$(".row_podlove_podcast_license_preview").hide();
		};

		$("#podlove_cc_license_selector_toggle").on( 'click', function() {
			$(this).find("._podlove_episode_list_triangle").toggle();
			$(this).find("._podlove_episode_list_triangle_expanded").toggle();
			$(".row_podlove_cc_license_selector").toggle();
		});

		$("#license_cc_version").on( 'change', function () {
			podlove_filter_license_selector($(this).val());
		} );

		$(settings.license_url_field_id).on( 'change', function() {
			if( $(this).val().indexOf('creativecommons.org') !== -1 ) {
				var data = {
					action: 'podlove-get-license-parameters-from-url',
					url: $(this).val()
				};

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						podlove_populate_license_form(
							result.version,
							result.modification,
							result.commercial_use,
							result.jurisdiction
						);
					}
				});
			} else {
				$(".podlove_podcast_license_image").html('');
				$(".podlove-license-link").html( $(settings.license_name_field_id).val() );
				$(".podlove-license-link").attr("href", $(this).val() );
			}
			$(".row_podlove_podcast_license_preview").show();
		});

		$(settings.license_name_field_id).on( 'change', function() {
			$(".podlove-license-link").html( $(this).val() );
			$(".row_podlove_podcast_license_preview").show();
		});

		$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction, #license_cc_version").on( 'change', function() {
			podlove_change_url_preview_and_name_from_form(
				$("#license_cc_version").val(),
				$("#license_cc_allow_modifications").val(),
				$("#license_cc_allow_commercial_use").val(),
				$("#license_cc_license_jurisdiction").val()
			);
		});

		$(document).ready(function() {
			if( $(settings.license_name_field_id).val() !== '' || $(settings.license_url_field_id).val() !== '' )
				podlove_populate_license_form( settings.license.version, settings.license.modification, settings.license.commercial_use, settings.license.jurisdiction );

			if( $(settings.license_name_field_id).val() == '' || $(settings.license_url_field_id).val() == '' )
				$(".row_podlove_podcast_license_preview").hide();
		});
	}

}(jQuery));

