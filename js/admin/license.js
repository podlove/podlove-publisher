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

		var podlove_change_url_preview_and_name_from_form = function(modification_value, commercial_use_value, jurisdiction_value) {
			if (!modification_value || !commercial_use_value || !jurisdiction_value )
				return;

			var $that = $(this);
			var data = {
				action: 'podlove-get-license-url',
				modification: modification_value,
				commercial_use: commercial_use_value,
				jurisdiction: jurisdiction_value,
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

			$(".podlove_podcast_license_image").html('<img src="' + settings.plugin_url + '/images/cc/' + podlove_license_cc_get_image(modification_value, commercial_use_value) + '.png" alt="" />');
			$(".row_podlove_podcast_license_preview").show();
		};

		var podlove_populate_license_form = function(modification_value, commercial_use_value, jurisdiction_value) {
			$("#license_cc_allow_modifications").find('option[value=' + modification_value + ']').attr('selected','selected');
			$("#license_cc_allow_commercial_use").find('option[value=' + commercial_use_value + ']').attr('selected','selected');
			$("#license_cc_license_jurisdiction").find('option[value=' + jurisdiction_value + ']').attr('selected','selected');

			$(".podlove_podcast_license_image").html('<img src="' + settings.plugin_url + '/images/cc/' + podlove_license_cc_get_image(modification_value, commercial_use_value) + '.png" alt="" />');
			
			var data = {
				action: 'podlove-get-license-name',
				modification: modification_value,
				commercial_use: commercial_use_value,
				jurisdiction: jurisdiction_value,
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(".podlove-license-link").html(result);
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
		});

		$(settings.license_name_field_id).on( 'change', function() {
			$(".podlove-license-link").html( $(this).val() );
		});

		$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction").on( 'change', function() {
			podlove_change_url_preview_and_name_from_form(
															$("#license_cc_allow_modifications").val(),
															$("#license_cc_allow_commercial_use").val(),
															$("#license_cc_license_jurisdiction").val()
														);
		});

		$(document).ready(function() {
			podlove_populate_license_form( settings.license.modification, settings.license.commercial_use, settings.license.jurisdiction );
		});
	}

}(jQuery));

