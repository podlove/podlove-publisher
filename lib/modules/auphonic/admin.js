var PODLOVE = PODLOVE || {};

(function($){

	/**
	 * Adds loading indicator to a button.
	 *
	 * Expects the following HTML:
	 *
	 *	<span id="my_button">
     *		<span class="state_idle"><i class="some-icon-class"></i></span>
     *		<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
     *		<span class="state_success"><i class="podlove-icon-ok"></i></span>
     *		<span class="state_fail"><i class="podlove-icon-remove"></i></span>
     *	</span>
     *
     * Usage:
     *
     *   $("#my_button").button_with_loading_indicator();
     *   $("#my_button").trigger('start');
     *   $("#my_button").trigger('stop');
	 */
	$.fn.button_with_loading_indicator = function(options) {

	    return this.each(function() {

	        var	$button  = $(this),
			    $idle    = $button.find(".state_idle"),
			    $working = $button.find(".state_working"),
			    $fail    = $button.find(".state_fail"),
			    $success = $button.find(".state_success");

	        var start = function() {
	        	$idle.hide();
	        	$working.show();
	        };

	        var stop = function() {
				$working.hide();
				$success.show().delay(750).fadeOut(200);
				$idle.delay(750).fadeIn(200);
	        };

	        var fail = function() {
	        	$working.hide();
	        	$fail.show().delay(750).fadeOut(200);
	        	$idle.delay(750).fadeIn(200);
	        }

	        $button.bind('start', start);
	        $button.bind('stop', stop);
	        $button.bind('fail', fail);

	    });
	};

	PODLOVE.AuphonicImport = function () {

		function get_chapters_string_from_data (data) {
			var chapters_entry = "";

			$.each(data.data.chapters, function(index, value) {
				chapters_entry = chapters_entry + value.start + " " + value.title;
				if (value.url == "") {
			
				} else {
					chapters_entry = chapters_entry + " <" + value.url + ">";
				}
				chapters_entry = chapters_entry + '\n';
			});

			return chapters_entry;
		}

		function get_fields_to_update(data, chapter_asset_assignment) {
			var fields = [
				{ field: '#title'                 , value: data.data.metadata.title },
				{ field: '#_podlove_meta_subtitle', value: data.data.metadata.subtitle },
				{ field: '#_podlove_meta_summary' , value: data.data.metadata.summary },
				{ field: '#_podlove_meta_duration', value: data.data.length_timestring },
				{ field: '#_podlove_meta_slug'    , value: data.data.output_basename },
				{ field: '#new-tag-post_tag'      , value: data.data.metadata.tags.join(" , ") },
			];

			if (chapter_asset_assignment == 'manual') {
				fields.push({ field: '#_podlove_meta_chapters', value: get_chapters_string_from_data(data) });
			}

			return fields;
		}

		/**
		 * Import and override existing fields.
		 */
		function do_force_import(data, chapter_asset_assignment) {
			var fields = get_fields_to_update(data, chapter_asset_assignment);
			$.each(fields, function (index, field) {
				$(field.field).val(field.value);
			});
		}

		/**
		 * Import but do not override existing fields.
		 */
		function do_simple_import(data, chapter_asset_assignment) {
			var fields = get_fields_to_update(data, chapter_asset_assignment);
			$.each(fields, function (index, field) {
				if ($(field.field).val() == "") {
					$(field.field).val(field.value);
				}
			});
		}

		function fetch_auphonic_production_status(production_uuid) {
			var api_url = "https://auphonic.com/api/production/{uuid}.json".replace("{uuid}",production_uuid);

			$.getJSON(api_url, { bearer_token: get_token() }, function(data) {
				if (data && data.status_code == 200) {
					$("#auphonic-creation-status").html(" " + data.data.status_string);
				}
			});

			window.setTimeout(function() { fetch_auphonic_production_status(production_uuid); }, 3000);
		}

		function start_auphonic_production() {
			var module_url = $("#auphonic").data('module-url'),
				$button = $("#start_auphonic_production_button > span").button_with_loading_indicator(),
				production_uuid = $("#auphonic").data('production-uuid');

			$button.trigger('start');
			$.post(
				ajaxurl,
				{
					action: 'podlove-auphonic-start-production',
					production: production_uuid
				},
				function(data) {
					if (data) {
						console.log(data);
						window.setTimeout(function() { fetch_auphonic_production_status(production_uuid); }, 1000);
					}
				}
			).fail(function() {
				$button.trigger('fail');
			}).done(function() {
				$button.trigger('stop');
			});
		}
		
		/**
		 * Create Auphonic production.
		 */				
		 function create_auphonic_production() {
		 	var presetuuid = $("#auphonic").data('presetuuid');
		 	var chapter_asset_assignment = $("#auphonic").data('assignment-chapter');
		 	var cover_art_asset_assignment = $("#auphonic").data('assignment-image');
		 	var module_url = $("#auphonic").data('module-url');
		 	var auphonic_production_data = new Object();
		 	var auphonic_production_metadata = new Object();
		 	var auphonic_files = new Object();
		 	
		 	var raw_production_tags = $(".tagchecklist").text();
		 	var raw_chapters = $("#_podlove_meta_chapters").val();
		 	
		 	var now = new Date();
		 	var chapters = new Array();
		 	
		 	$button = $("#create_auphonic_production_button span").button_with_loading_indicator();
		 	$button.trigger('start');
		 	
		 	if(typeof presetuuid !== undefined && presetuuid !== "") {
		 		auphonic_production_data.preset = presetuuid;
		 	}

		 	var service = $("#auphonic_services").val(),
		 		input_file = $("#auphonic_production_files").val(),
		 		input_url = $("#auphonic_http_upload_url").val();
		 	
		 	if (service === "url") {
		 		auphonic_production_data.input_file = input_url;
		 	} else {
			 	auphonic_production_data.service = service;
			 	auphonic_production_data.input_file = input_file;
		 	}

		 	auphonic_production_data.length_timestring = $("#_podlove_meta_duration").val();
		 	auphonic_production_data.output_basename= $("#_podlove_meta_slug").val();
		 	auphonic_production_metadata.title = $("#title").val();
		 	auphonic_production_metadata.subtitle = $("#_podlove_meta_subtitle").val();
		 	auphonic_production_metadata.summary = $("#_podlove_meta_summary").val();
		 	auphonic_production_metadata.year = now.getFullYear();
		 	/* auphonic_production_metadata.tags = raw_production_tags.substring(2, raw_production_tags.length).split('X\u00a0'); */
		 		
		 	if(typeof chapter_asset_assignment !== 'undefined') {
		 		if (chapter_asset_assignment == 'manual' && raw_chapters !== "") {
		 			$(raw_chapters.split('\n')).each(function (index, value) {
		 				if(value !== "\n" && value !== "") {
							var chapter = new Object();
							chapter.start = value.substring(0, value.indexOf(" "));
							if(value.indexOf("<") == -1) {
								chapter.title = value.substring(value.indexOf(" ") + 1, value.length);
								chapter.url = "";
							} else {
								chapter.title = value.substring(value.indexOf(" ") + 1, value.lastIndexOf(" "));
								chapter.url = value.substring(value.lastIndexOf(" "), value.length).substring(2, value.substring(value.lastIndexOf(" "), value.length).length - 1);
							}
							chapters[index] = chapter;
							delete chapter;
						}
		 			});
		 			auphonic_production_data.chapters = chapters;
		 		}
		 	}
						 		
		 	auphonic_production_data.metadata = auphonic_production_metadata;

			$.post(
				ajaxurl,
				{
					action: 'podlove-auphonic-create-production',
					data: JSON.stringify(auphonic_production_data)
				},
				function(data) {
					if (data) {
						console.log(data);

						if (data.error_message) {
							alert(data.error_message);
						}

						if (data.data) {
							var production_data = data.data,
								$status = $("#auphonic-creation-status");

							$status.html(" " + production_data.status_string);
							switch (production_data.status_string) {
								case "Incomplete Form":
									$status.removeClass('status-ok');
									$status.addClass('status-warning');
									break;
								default:
									$status.addClass('status-ok');
									$status.removeClass('status-warning');
							}

							$("#auphonic-creation-title").html(production_data.metadata.title);
							$("#auphonic-production-segment").show();
							$("#auphonic").data('production-uuid', production_data.uuid);

							delete production_data;
							fetch_episodes(token);
						}
					}
				}
			).fail(function() {
				$button.trigger('fail');
			}).done(function() {
				$button.trigger('stop');
			});
		 }

		function fetch_production_data() {
			var uuid = $("#import_from_auphonic option:selected").val(),
			    chapter_asset_assignment = $("#auphonic").data("assignment-chapter")
			    $button = $("#fetch_production_data_button > span").button_with_loading_indicator();

			$button.trigger('start');

			var url = 'https://auphonic.com/api/production/{uuid}.json?bearer_token={token}'
				.replace('{uuid}', uuid)
				.replace('{token}', get_token());

			$.getJSON(url, function(data) {
				if (data) {
					// hide prompt label which usually is placed above the title field
					$('#title-prompt-text').addClass('screen-reader-text');

					if (document.getElementById('force_import_from_auphonic').checked) {
						do_force_import(data, chapter_asset_assignment);
					} else {
						do_simple_import(data, chapter_asset_assignment);
					}

					// activate all assets if no asset is active
					if ($(".media_file_row input[type=checkbox]:checked").length === 0) {
						$(".media_file_row input[type=checkbox]:not(:checked)").click();
					}
				}
			}).fail(function() {
				$button.trigger('fail');
			}).done(function() {
				$button.trigger('stop');
			});
		}
		
		function fetch_episodes() {
			var $button = $("#reload_productions_button").button_with_loading_indicator(),
				url = 'https://auphonic.com/api/productions.json?bearer_token={token}'.replace('{token}', get_token());

			$button.trigger('start');
			var productions = $.getJSON(url, function(data) {
				if (data) {
					var production_list = new Array();
					var auphonic_productions = data.data;
					$("#import_from_auphonic").empty();
					$(auphonic_productions).each(function(key, value) {				
						var date = new Date(value.change_time);				
						$("#import_from_auphonic").append('<option value="' + value.uuid + '">' + value.output_basename + ' (' + date.getFullYear() + '-' + ("0" + (date.getMonth() + 1)).slice(-2) + '-' + ("0" + (date.getDay() + 1)).slice(-2) + ' ' + date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds() + ') [' + value.status_string + ']</option>\n');
					});
					
					delete auphonic_productions;
				}
			}).fail(function() {
				$button.trigger('fail');
			}).done(function() {
				$button.trigger('stop');
			});
		}

		$("#fetch_production_data_button").click(function (e) {
			e.preventDefault();
			fetch_production_data();
		});
		
		$("#create_auphonic_production_button").click(function (e) {
			e.preventDefault();
			create_auphonic_production();
		});
		
		$("#start_auphonic_production_button").click(function(e) {
			e.preventDefault();
			start_auphonic_production();
		});

		$("#reload_productions_button").click(function () {
			fetch_episodes();
		});
		
		$("#open_production_button").click(function () {
			window.open('https://auphonic.com/engine/upload/edit/' + $("#import_from_auphonic").find(":selected").val());
		});

		$("#fetch_auphonic_production_files").click(function(){
			fetch_files_for_current_service();
		});

		$("#auphonic_services").change(function(){
			fetch_files_for_current_service();
		});

		function fetch_files_for_current_service() {
			var uuid = $("#auphonic_services").val(),
				api_url = "https://auphonic.com/api/service/{uuid}/ls.json".replace("{uuid}",uuid),
				$files = $("#auphonic_production_files"),
				$button = $("#fetch_auphonic_production_files").button_with_loading_indicator();

			if (uuid == "url") {
				$("#auphonic_http_upload_url").show();
				$("#auphonic_production_files").hide();
			} else {

				$("#auphonic_http_upload_url").hide();
				$("#auphonic_production_files").show();
				$button.trigger('start');
				$.getJSON(api_url, { bearer_token: get_token() }, function(data) {
					if (data && data.status_code == 200) {
						$files.empty();
						$.each(data.data, function(index, file) {
							$files.append("<option>" + file + "</option>");
						});
					}
				}).fail(function() {
					$button.trigger('fail');
					$files.empty().append("<option>Unable to load files</option>");
				}).done(function() {
					$button.trigger('stop');
				});
			}

		}

		function fetch_incoming_services() {
			var api_url = "https://auphonic.com/api/services.json",
				$services = $("#auphonic_services");

			$.getJSON(api_url, { bearer_token: get_token() }, function(data) {
				if (data.status_code == 200) {
					$services.empty();
					$services.append('<option value="url">From URL</option>')
					$.each(data.data, function(index, service) {
						if (service.incoming) {
							$services.append("<option value='" + service.uuid + "'>" + service.display_name + " (" + service.type + ")</option>");
						}
					});
					fetch_files_for_current_service();
				}
			}).fail(function() {
				$services.empty().append("<option>Unable to load Services</option>");
			}).done(function() {
				// console.log("fetch service: succeeded");
			});
		}

		function get_token() {
			return $("#auphonic").data('api-key');
		}

		$(document).ready(function() {
			fetch_episodes($("#auphonic").data('api-key'));
			$("#auphonic-box").tabs({ active: 0 });
			fetch_incoming_services();
		});

	}
}(jQuery));

jQuery(function($) {
	PODLOVE.AuphonicImport();
});