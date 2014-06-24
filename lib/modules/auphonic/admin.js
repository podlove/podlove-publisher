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
	        	$htmlButton = $button.closest("button"),
			    $idle    = $button.find(".state_idle"),
			    $working = $button.find(".state_working"),
			    $fail    = $button.find(".state_fail"),
			    $success = $button.find(".state_success");

	        var start = function() {
	        	$idle.hide();
	        	$working.show();
	        	$htmlButton.attr('disabled', 'disabled');
	        };

	        var stop = function() {
				$working.hide();
				$success.show().delay(750).fadeOut(200);
				$idle.delay(750).fadeIn(200);
				$htmlButton.removeAttr('disabled');
	        };

	        var fail = function() {
	        	$working.hide();
	        	$fail.show().delay(750).fadeOut(200);
	        	$idle.delay(750).fadeIn(200);
	        	$htmlButton.removeAttr('disabled');
	        }

	        $button.bind('start', start);
	        $button.bind('stop', stop);
	        $button.bind('fail', fail);

	    });
	};

	PODLOVE.AuphonicImport = function () {

		var statusTimeoutId;

		function get_chapters_string_from_data (production) {
			var chapters_entry = "";

			var chapters = production.chapters.sort(function(a, b) {
				return a.start_sec - b.start_sec;
			});

			console.log(chapters);

			$.each(chapters, function(index, value) {
				chapters_entry = chapters_entry + value.start_output + " " + value.title;
				if (value.url == "") {
			
				} else {
					chapters_entry = chapters_entry + " <" + value.url + ">";
				}
				chapters_entry = chapters_entry + '\n';
			});

			return chapters_entry;
		}

		function get_result_fields(production) {
			return [
				{ field: '#_podlove_meta_duration', value: production.length_timestring },
				{ field: '#_podlove_meta_slug'    , value: production.output_basename }
			];
		}

		function get_metadata_fields(production, chapter_asset_assignment) {
			var fields = [
				{ field: '#title'                 		, value: production.metadata.title },
				{ field: '#_podlove_meta_subtitle'		, value: production.metadata.subtitle },
				{ field: '#_podlove_meta_summary' 		, value: production.metadata.summary },
				{ field: '#new-tag-post_tag'      		, value: production.metadata.tags.join(" , ") },
				{ field: '#_podlove_meta_license_name'  , value: production.metadata.license },
				{ field: '#_podlove_meta_license_url'   , value: production.metadata.license_url }
			];

			if (chapter_asset_assignment == 'manual') {
				fields.push({ field: '#_podlove_meta_chapters', value: get_chapters_string_from_data(production) });
			}

			return fields;
		}

		/**
		 * Import and override existing fields.
		 */
		function do_force_import(fields) {
			$.each(fields, function (index, field) {
				$(field.field).val(field.value);
			});
		}

		/**
		 * Import but do not override existing fields.
		 */
		function do_simple_import(fields) {
			$.each(fields, function (index, field) {
				if ($(field.field).val() == "") {
					$(field.field).val(field.value);
				}
			});
		}

		function import_production_results(e, production) {
			do_force_import(get_result_fields(production));
			$.publish("/auphonic/production/status/results_imported", [production]);
		}

		function fetch_auphonic_production_status(production_uuid) {
			var api_url = "https://auphonic.com/api/production/{uuid}.json".replace("{uuid}",production_uuid),
				production;

			window.clearTimeout(statusTimeoutId); // in case there are multiple running

			$.getJSON(api_url, { bearer_token: PODLOVE.Auphonic.get_token() }, function(data) {
				if (data && data.status_code == 200) {
					production = data.data;
					update_production_status(production.status_string, production.status);
					if (production.status_string === "Done") {
						$("#start_auphonic_production_button, #stop_auphonic_production_button").toggle();
						$.publish("/auphonic/production/status/done", [production]);
					} else {
						statusTimeoutId = window.setTimeout(function() { fetch_auphonic_production_status(production_uuid); }, 5000);
					}
				}
			});
		}

		/**
		 * Extract Auphonic relevant data from WordPress web form.
		 *
		 * @param {string} mode "init" or "update". Default: "init". Update ignores presets and output files.
		 * 
		 * @return json In the correct format for the Auphonic API.
		 */
		function extract_auphonic_data_from_form(mode) {
			mode = typeof mode !== 'undefined' ? mode : 'init';

			var presetuuid = $("#auphonic").data('presetuuid'),
				chapter_asset_assignment = $("#auphonic").data('assignment-chapter'),
				cover_art_asset_assignment = $("#auphonic").data('assignment-image'),
				module_url = $("#auphonic").data('module-url'),
				raw_chapters = $("#_podlove_meta_chapters").val(),
				chapters = [],
				data = {};

			data.metadata = {};

			if (mode === "init") {
				if(typeof presetuuid !== undefined && presetuuid !== "") {
					data.preset = presetuuid;
				} else {
					// no preset? add some output files
					data.output_files = [
						{"format":"aac", "bitrate":"128", "ending":"m4a"},
						{"format":"mp3", "bitrate":"128", "ending":"mp3"},
						{"format":"opus", "bitrate":"96", "ending":"opus"}
					]
				}
			}

			var service = $("#auphonic_services").val(),
				input_file = $("#auphonic_production_files").val(),
				input_url = $("#auphonic_http_upload_url").val();

			if (service === "url") {
				data.input_file = input_url;
			} else if (service == "file") {
				// do nothing
			} else {
				data.service = service;
				data.input_file = input_file;
			}

			data.length_timestring = $("#_podlove_meta_duration").val();
			data.output_basename = $("#_podlove_meta_slug").val();
			data.metadata.title = $("#title").val();
			data.metadata.subtitle = $("#_podlove_meta_subtitle").val();
			data.metadata.summary = $("#_podlove_meta_summary").val();
			data.metadata.year = $("#aa").val() || (new Date()).getFullYear();
			data.metadata.license = $("#_podlove_meta_license_name").val();
			data.metadata.license_url = $("#_podlove_meta_license_url").val();
			data.metadata.tags = $(".the-tags").val().split(',').concat( $("#new-tag-post_tag").val().split(',') );
				
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
					data.chapters = chapters;
				}
			}

			return data;
		}

		function update_auphonic_production(production_uuid, callback) {
			// Delete chapters before adding them again (avoid doubling of chapters)
			var url = 'https://auphonic.com/api/production/{uuid}/chapters.json',
				production_uuid = $("#auphonic_productions option:selected").val();

		 	var xhr = PODLOVE.Auphonic.createCORSRequest("DELETE", url.replace("{uuid}", production_uuid));
		 	xhr.setRequestHeader("Content-type","application/json");
		 	xhr.setRequestHeader("Authorization","Bearer " + PODLOVE.Auphonic.get_token());
		 	xhr.onloadend = function() {
		 		// Once chapters are deleted update the auphonic production
	 			var url = 'https://auphonic.com/api/production/{uuid}.json'
	 				data = extract_auphonic_data_from_form("update");

	 		 	var xhr = PODLOVE.Auphonic.createCORSRequest("POST", url.replace("{uuid}", production_uuid));
	 		 	xhr.setRequestHeader("Content-type","application/json");
	 		 	xhr.setRequestHeader("Authorization","Bearer " + PODLOVE.Auphonic.get_token());
	 		 	xhr.onload = function(e) {
	 		 		callback();
	 		 	};

	 		 	xhr.send(JSON.stringify(data));
		 	};
		 	xhr.send();			
		}

		function stop_auphonic_production() {
			var url = 'https://auphonic.com/api/production/{uuid}/stop.json',
				production_uuid = $("#auphonic_productions option:selected").val();

		 	var xhr = PODLOVE.Auphonic.createCORSRequest("POST", url.replace("{uuid}", production_uuid));
		 	xhr.setRequestHeader("Content-type","application/json");
		 	xhr.setRequestHeader("Authorization","Bearer " + PODLOVE.Auphonic.get_token());
		 	xhr.onload = function(e) {
		 		fetch_auphonic_production_status(production_uuid);
		 	};
		 	xhr.send();
		}

		function start_auphonic_production() {
			var url = 'https://auphonic.com/api/production/{uuid}/start.json',
				production_uuid = $("#auphonic_productions option:selected").val();

		 	var xhr = PODLOVE.Auphonic.createCORSRequest("POST", url.replace("{uuid}", production_uuid));
		 	xhr.setRequestHeader("Content-type","application/json");
		 	xhr.setRequestHeader("Authorization","Bearer " + PODLOVE.Auphonic.get_token());
		 	xhr.onload = function(e) {
		 		fetch_auphonic_production_status(production_uuid);
		 	};
		 	xhr.send();
		}

		/**
		 * Create Auphonic production.
		 */				
		 function create_auphonic_production() {

		 	$button = $("#create_auphonic_production_button span").button_with_loading_indicator();
		 	$button.trigger('start');
						 		
		 	var fail = function() { $button.trigger('fail'); }

		 	var xhr = PODLOVE.Auphonic.createCORSRequest("POST", "https://auphonic.com/api/productions.json");
		 	xhr.addEventListener("error", fail, false);
		 	xhr.addEventListener("abort", fail, false);
		 	xhr.setRequestHeader("Content-type","application/json");
		 	xhr.setRequestHeader("Authorization","Bearer " + PODLOVE.Auphonic.get_token());
		 	xhr.onload = function(e) {
		 		var response = JSON.parse(e.target.response),
		 			status = response.status_code,
		 			production = response.data,
		 			file = document.querySelector('#auphonic_local_upload_url').files[0];

		 		$.publish('/auphonic/createproduction/success', [production]);

		 		if ($("#auphonic_services").val() == "file" && file) {
		 			$.publish('/auphonic/fileupload', [production, $button] );
		 		} else {
			 		$button.trigger('stop');
			 		$.publish('/auphonic/createproduction/done', [production]);
		 		}
		 	};

		 	$.publish('/auphonic/createproduction/before');
	 	 	$.ajax({
	 	 		dataType: "json",
	 	 		url: ajaxurl,
	 	 		data: { action: 'podlove-podcast' },
	 	 		success: function(publisher_podcast) {
	 	 			var auphonic_production_data = extract_auphonic_data_from_form();
	 		 		auphonic_production_data.metadata.publisher = publisher_podcast.publisher_name;
	 		 		auphonic_production_data.metadata.url = publisher_podcast.publisher_url;
	 		 		auphonic_production_data.metadata.license = publisher_podcast.license_name;
	 		 		auphonic_production_data.metadata.license_url = publisher_podcast.license_url;

				 	xhr.send(JSON.stringify(auphonic_production_data));
	 		 	}
	 	 	});
		}

		/**
		 * Fetch data from the production and put it into the episode.
		 *
		 * @param string import_mode Set to "metadata" to override all data from production result.
		 *                           Otherwise set to production results only.
		 */
		function fetch_production_data(button, import_mode) {
			var uuid = $("#auphonic_productions option:selected").val(),
			    chapter_asset_assignment = $("#auphonic").data("assignment-chapter")
			    $button = $(" > span", button).button_with_loading_indicator();

			$button.trigger('start');

			var url = 'https://auphonic.com/api/production/{uuid}.json?bearer_token={token}'
				.replace('{uuid}', uuid)
				.replace('{token}', PODLOVE.Auphonic.get_token());

			$.getJSON(url, function(data) {
				if (data && data.data) {
					// hide prompt label which usually is placed above the title field
					$('#title-prompt-text').addClass('screen-reader-text');

					do_force_import(get_result_fields(data.data));
					if (import_mode === 'metadata') {
						do_force_import(get_metadata_fields(data.data, chapter_asset_assignment));
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

		function fetch_files_for_current_service() {
			var uuid = $("#auphonic_services").val(),
				api_url = "https://auphonic.com/api/service/{uuid}/ls.json".replace("{uuid}",uuid),
				$files = $("#auphonic_production_files"),
				$button = $("#fetch_auphonic_production_files").button_with_loading_indicator();

			if (uuid == "url") {
				$("#auphonic_http_upload_url").show();
				$("#auphonic_production_files, #auphonic_local_upload_url").hide();
			} else if (uuid == "file") {
				$("#auphonic_local_upload_url").show();
				$("#auphonic_production_files, #auphonic_http_upload_url").hide();
			} else {
				$("#auphonic_http_upload_url, #auphonic_local_upload_url").hide();
				$("#auphonic_production_files").show();
				$button.trigger('start');
				$.getJSON(api_url, { bearer_token: PODLOVE.Auphonic.get_token() }, function(data) {
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

			$services.empty();
			$services.append('<option value="file">Upload from computer</option>')
			$services.append('<option value="url">From URL</option>')

			$.getJSON(api_url, { bearer_token: PODLOVE.Auphonic.get_token() }, function(data) {
				if (data.status_code == 200) {
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

		function fetch_processing_time() {
			var api_url = "https://auphonic.com/api/user.json",
				$credits = $("#auphonic-credits"),
				$credits_container = $("#auphonic-credits-status");

			$.getJSON(api_url, { bearer_token: PODLOVE.Auphonic.get_token() }, function(data) {
				console.log(data);
				if (data.status_code == 200) {
					var hours   = parseInt(data.data.credits, 10),
						minutes = parseInt((data.data.credits - hours) * 60, 10),
						status  = hours + "h" + (minutes ? (" " + minutes + "m") : "");

					$credits.html(status);
					$credits_container.show();
				}
			}).fail(function() {
				$credits_container.hide();
			});
		}

		function update_production_status(text, number) {
			var css_classes = "status-progress status-ok status-info status-error",
				$status = $("#auphonic-production-status");

			$status.html(text);
			$status.removeClass(css_classes);
			switch (number) {
				case 0: // "0": "File Upload",
				case 1: // "1": "Waiting",
				case 4: // "4": "Audio Processing",
				case 5: // "5": "Audio Encoding",
				case 6: // "6": "Outgoing File Transfer",
				case 7: // "7": "Audio Mono Mixdown",
				case 8: // "8": "Splitting Audio On Chapter Marks",
				case 12: // "12": "Incoming File Transfer",
				case 13: // "13": "Stopping the Production"
					$status.addClass('status-progress');
					break;
				case 3: // "3": "Done",
					$status.addClass('status-ok');
					break;
				case 9:  // "9": "Incomplete Form",
				case 10: // "10": "Production Not Started Yet",
				case 11: // "11": "Production Outdated",
					$status.addClass('status-info');
					break;
				case 2: // "2": "Error",
					$status.addClass('status-error');
					break;
			}
		}

		/**
		 * Start an Auphonic production process.
		 * 
		 * - handle button states
		 * - update production with current episode data
		 * - start the actual production
		 */
		function do_auphonic_production() {
			$("#start_auphonic_production_button, #stop_auphonic_production_button").toggle();
			update_auphonic_production($("#auphonic_productions option:selected").val(), function(){
				start_auphonic_production();
			});
		}

		if ($("#auphonic").length) {

			// automatically import production results when production is done
			$.subscribe("/auphonic/production/status/done", import_production_results);

			// Automatically publish after results are imported (if flag is set).
			// Wait a second in case stuff is still going on after the results
			// were imported — like media file checking.
			$.subscribe("/auphonic/production/status/results_imported", function(e, production) {
				if ($("#auphonic_publish_after_finishing").is(":checked")) {
					setTimeout(function() {
						$("#publish").click();
					}, 1000);
				}
			});

			$.subscribe('/auphonic/createdproduction/added', function(e, production) {
				if ($("#auphonic_start_after_creation").is(":checked")) {
					do_auphonic_production();
				}
			});

			$("#auphonic_productions").chosen({ width: "50%" });

			$("#auphonic_productions").change(function(e) {
				var $production = $("option:selected", this),
					production = $production.data(),
					title = production.title ? production.title : '(no title)';

				if ($production.val() !== "0") {
					$("#_auphonic_production").val($production.val());
					update_production_status(production.status, production.status_number);

					$("#auphonic-selected-production button").attr("disabled", false);
				} else {
					$("#auphonic-selected-production").attr("disabled", true);
				}

			});

			$("#fetch_production_data_button").click(function (e) {
				e.preventDefault();
				fetch_production_data($(this), 'metadata');
			});

			$("#fetch_production_results_button").click(function (e) {
				e.preventDefault();
				fetch_production_data($(this), 'results');
			});
			
			$("#create_auphonic_production_button").click(function (e) {
				e.preventDefault();
				create_auphonic_production();
			});
			
			$("#start_auphonic_production_button").click(function(e) {
				e.preventDefault();
				
				do_auphonic_production();
			});
			
			$("#stop_auphonic_production_button").click(function(e) {
				e.preventDefault();
				$("#start_auphonic_production_button, #stop_auphonic_production_button").toggle();
				stop_auphonic_production();
			});

			$("#open_production_button").click(function (e) {
				e.preventDefault();
				window.open('https://auphonic.com/engine/upload/edit/' + $("#auphonic_productions").find(":selected").val());
			});

			$("#fetch_auphonic_production_files").click(function(){
				fetch_files_for_current_service();
			});

			$("#auphonic_services").change(function(){
				fetch_files_for_current_service();
			});

			$(document).ready(function() {
				fetch_incoming_services();
				fetch_processing_time();
			});
		}
	}

	/**
	 * Starting below are tidy logic-modules.
	 * Above is "JS Spaghetti Blurb" that works but should be modularized, too.
	 */

	/**
	 * Auphonic Helper Methods
	 */
	PODLOVE.Auphonic = (function(){
		return {
			get_token: function() {
				return $("#auphonic").data('api-key');
			},
			createCORSRequest: function(method, url) {
				var xhr = new XMLHttpRequest();
				if ("withCredentials" in xhr) {
					xhr.open(method, url, true); // XHR for Chrome/Firefox/Opera/Safari
				} else if (typeof XDomainRequest != "undefined") {
					xhr = new XDomainRequest(); // XDomainRequest for IE
					xhr.open(method, url);
				} else {
					xhr = null; // CORS not supported
				}
				return xhr;
		 	},
		 	pad_number: function (n, p, c) {
		 	    var pad_char = typeof c !== 'undefined' ? c : '0';
		 	    var pad = new Array(1 + p).join(pad_char);
		 	    return (pad + n).slice(-pad.length);
		 	},
		 	refresh_preset_list: function( that ) {
		 		var data = {
		 			action: 'podlove-refresh-auphonic-presets'
		 		};

		 		var selected_preset = $("#podlove_module_auphonic_auphonic_production_preset").val();

		 		$(that).html('<i class="podlove-icon-spinner rotate"></i>');

		 		$.ajax({
		 			url: ajaxurl,
		 			data: data,
		 			dataType: 'json',
		 			success: function(result) {
		 				$("#podlove_module_auphonic_auphonic_production_preset").children( 'option:not(:first)' ).remove();

		 				$.each( result.data, function( index, value) {
		 					$("#podlove_module_auphonic_auphonic_production_preset").append("<option value='" + value.uuid + "'>" + value.preset_name + "</option>");
		 				});
		 				
		 				$("#podlove_module_auphonic_auphonic_production_preset").val( selected_preset );

		 				$(that).html('<i class="podlove-icon-repeat"></i>');
		 			}
		 		});
		 	}
		}
	}());

	/**
	 * Auphonic Production Fethcer
	 *
	 * This module fills the list of available productions.
	 * It ensures it's always up to date.
	 */
	PODLOVE.AuphonicProductionFetcher = (function(){
		var $productions, $button, url;

    	var init = function() {
			$(document).ready(function () {
	    		$productions = $("#auphonic_productions");

	    		if (!$productions.length) return;

	    		$button = $("#reload_productions_button").button_with_loading_indicator();
	    		url = 'https://auphonic.com/api/productions.json?bearer_token={token}'
	    			.replace('{token}', PODLOVE.Auphonic.get_token());

	    		$("#reload_productions_button").on('click', fetch);
	    		$.subscribe('/auphonic/createproduction/done', addCreatedProduction);

	    		fetch();
		    });
    	};

    	var refreshSelectUI = function() {
    		jQuery("#auphonic_productions")
    			.change()
    			.trigger("liszt:updated"); // for "chosen"
    	};

    	var fetch = function(e, production) {
    		$button.trigger('start');
    		
    		$.getJSON(url, function(data) {
    			if (!data) return;
				var production_list = [],
					auphonic_productions = data.data;

				$("#auphonic_productions").empty();
				$("#auphonic_productions").append('<option value="0">Select existing Production</option>\n');
				$(auphonic_productions).each(function(key, auphonic_production) {				
					$("#auphonic_productions").append(productionOptionTag(auphonic_production, production));
				});
				refreshSelectUI();
    		}).fail(function() {
    			$button.trigger('fail');
    		}).done(function() {
    			$button.trigger('stop');
    		});
    	};

    	var addCreatedProduction = function(e, production) {
    		// deselect currently selected
    		$("#auphonic_productions option:selected").removeAttr("selected");
    		// add new option and select it
    		$("#auphonic_productions").prepend(productionOptionTag(production, production));
    		refreshSelectUI();
    		$.publish('/auphonic/createdproduction/added', [production]);
    	};

    	/**
    	 * Generates <option> tag for production selector.
    	 * 
    	 * @param  object production Production object as it is returned from Auphonic.
    	 * @return jQuery wrapped <option> tag
    	 */
    	var productionOptionTag = function(production, current_production) {
    		var date = new Date(production.change_time),
    			output_basename = $.trim(production.output_basename),
    			saved_production_uuid = $("#_auphonic_production").val(),
    			production_title, option_title;

    		// title
    		if (production.metadata.title) {
    			production_title = production.metadata.title;
    		} else if (output_basename.length) {
    			production_title = output_basename;
    		} else {
    			production_title = '(no title)';
    		}

    		option_title = production_title + ' (' + date.getFullYear() + '-' + PODLOVE.Auphonic.pad_number(date.getMonth(),2) + '-' + PODLOVE.Auphonic.pad_number(date.getDate(),2) + ')';

    		// check if it should be selected
    		selected = "";
    		if (current_production) {
    			if (current_production.uuid == production.uuid) {
    				selected = "selected";
    			}
    		} else if (saved_production_uuid == production.uuid) {
    			selected = "selected";
    		}

    		// generate html
    		$option = $('<option value="' + production.uuid + '" ' + selected + '>' + option_title + '</option>\n');
    		$option.data({
    			"title":         production_title,
    			"date":          date,
    			"datestring":    timeSince(date),
    			"status":        production.status_string,
    			"status_number": production.status
    		});

    		return $option;
    	};

    	var timeSince = function (date) {
    	    var seconds = Math.floor((new Date() - date) / 1000);
    	    var interval = Math.floor(seconds / 31536000);

    	    if (interval > 1) {
    	        return interval + " years ago";
    	    }
    	    interval = Math.floor(seconds / 2592000);
    	    if (interval > 1) {
    	        return interval + " months ago";
    	    }
    	    interval = Math.floor(seconds / 86400);
    	    if (interval > 1) {
    	        return interval + " days ago";
    	    }
    	    interval = Math.floor(seconds / 3600);
    	    if (interval > 1) {
    	        return interval + " hours ago";
    	    }
    	    interval = Math.floor(seconds / 60);
    	    if (interval > 1) {
    	        return interval + " minutes ago";
    	    }
    	    return "just now";
    	};

    	init();
    	return {};
	}());

	/**
	 * Auphonic File Uploader
	 *
	 * This module handles the file upload of media files to Auphonic.
	 */
    PODLOVE.AuphonicFileUploader = (function() {
    	var $service;
    	
    	var init = function() {
			$(document).ready(function () {
	    		$service = $("#auphonic_services");
	    		$.subscribe('/auphonic/fileupload', doUpload);
		    });
    	};

    	var isFileUpload = function() {
    		return $service.val() === "file" && typeof getFile() === "object";
    	};

    	var getFile = function() {
    		return document.querySelector('#auphonic_local_upload_url').files[0];
    	};

    	var doUpload = function(event, production, button) {
    		$.publish('/auphonic/fileupload/before', [production])

			var url = 'https://auphonic.com/api/production/{uuid}/upload.json'.replace('{uuid}', production.uuid),
				xhr2 = PODLOVE.Auphonic.createCORSRequest("POST", url),
				formData = new FormData(),
				fail = function() { button.trigger('fail'); };

			xhr2.addEventListener("error", fail, false);
			xhr2.addEventListener("abort", fail, false);
			xhr2.upload.addEventListener("progress", function(e) {
				var percent = (e.loaded / e.total) * 100;
				$.publish('/auphonic/fileupload/progress', [production, percent])
			}, false);
			xhr2.onload = function(e) {
				$.publish('/auphonic/fileupload/done', [production])
				$.publish('/auphonic/createproduction/done', [production]);
				button.trigger('stop');
			};

			formData.append('input_file', getFile());

			xhr2.setRequestHeader("Authorization", "Bearer " + PODLOVE.Auphonic.get_token());
			xhr2.send(formData);
    	};

    	init();
    	return {
    		isFileUpload: isFileUpload
    	};
    }());

	/**
	 * Auphonic CreateProduction Status
	 *
	 * This module handles the status text for production creation
	 * and file upload.
	 */
    PODLOVE.AuphonicCreateProductionStatus = (function() {
    	var $status;

    	var init = function() {
			$(document).ready(function () {
	    		$status = $("#auphonic-production-status");

	    		$status.html('Please select a File').show();

	    		// upload
	    		$.subscribe('/auphonic/fileupload/before', updateUploadBefore)
	    		$.subscribe('/auphonic/fileupload/progress', updateUploadProgress)
	    		$.subscribe('/auphonic/fileupload/done', updateUploadDone);
	    		$.subscribe('/auphonic/createproduction/before', updateProductionStart)
	    		// create production
	    		$.subscribe('/auphonic/createproduction/success', updateProductionSuccess)
	    		$.subscribe('/auphonic/createproduction/done', updateProductionDone)
			});
    	};

    	var updateProductionDone = function(e) {
    		// $status.delay(1500).slideUp(300);
    	};

    	var updateProductionSuccess = function(e) {
    		$status.html("Production created").removeClass('status-progress').addClass('status-ok').show();
    	};

    	var updateProductionStart = function(e) {
    		$status.html("Creating production").addClass('status-progress').removeClass('status-ok').css("display", "block");
    	};

    	var updateUploadBefore = function(e, production) {
    		$status.html("File Upload").addClass('status-progress').removeClass('status-ok').show();
    	};

    	var updateUploadProgress = function(e, production, percent) {
    		$status.html("File Upload " + Math.round(percent) + "%").show();
    	};

    	var updateUploadDone = function(e, production) {
    		$status.html("File Uploaded").show().removeClass('status-progress').addClass('status-ok');
    		// $status.delay(1500).slideUp(300);
    	};

    	init();
    	return {};
    }());

}(jQuery));

jQuery(function($) {
	if ($("#auphonic").length && pagenow && pagenow === "podcast") {
		PODLOVE.AuphonicImport();
	}

	$(".podlove_auphonic_production_refresh").on( 'click', function() {
		PODLOVE.Auphonic.refresh_preset_list( this );
	});
});
