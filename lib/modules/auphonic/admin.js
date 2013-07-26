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

			$.getJSON(api_url, { bearer_token: PODLOVE.Auphonic.get_token() }, function(data) {
				if (data && data.status_code == 200) {
					update_production_status(data.data.status_string, data.data.status);
					if (data.data.status_string !== "Done") {
						window.setTimeout(function() { fetch_auphonic_production_status(production_uuid); }, 5000);
					}
				}
			});

		}

		function start_auphonic_production() {
			var module_url = $("#auphonic").data('module-url'),
				$button = $("#start_auphonic_production_button > span").button_with_loading_indicator(),
				production_uuid = $("#auphonic_productions option:selected").val();

			$button.trigger('start');
			$.post(
				ajaxurl,
				{
					action: 'podlove-auphonic-start-production',
					production: production_uuid
				},
				function(data) {
					if (data) {
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
		 	var presetuuid = $("#auphonic").data('presetuuid'),
		 		chapter_asset_assignment = $("#auphonic").data('assignment-chapter'),
		 		cover_art_asset_assignment = $("#auphonic").data('assignment-image'),
		 		module_url = $("#auphonic").data('module-url'),
		 		raw_chapters = $("#_podlove_meta_chapters").val(),
			 	chapters = [],
		 		auphonic_production_data = {};
		 	
	 		auphonic_production_data.metadata = {};
		 	
		 	$button = $("#create_auphonic_production_button span").button_with_loading_indicator();
		 	$button.trigger('start');
		 	
		 	if(typeof presetuuid !== undefined && presetuuid !== "") {
		 		auphonic_production_data.preset = presetuuid;
		 	} else {
		 		// no preset? add some output files
		 		auphonic_production_data.output_files = [
			 		{"format":"aac", "bitrate":"128", "ending":"m4a"},
			 		{"format":"mp3", "bitrate":"128", "ending":"mp3"},
			 		{"format":"opus", "bitrate":"96", "ending":"opus"}
		 		]
		 	}

		 	var service = $("#auphonic_services").val(),
		 		input_file = $("#auphonic_production_files").val(),
		 		input_url = $("#auphonic_http_upload_url").val();
		 	
		 	if (service === "url") {
		 		auphonic_production_data.input_file = input_url;
	 		} else if (service == "file") {
	 			// do nothing
	 		} else {
			 	auphonic_production_data.service = service;
			 	auphonic_production_data.input_file = input_file;
		 	}

		 	auphonic_production_data.length_timestring = $("#_podlove_meta_duration").val();
		 	auphonic_production_data.output_basename= $("#_podlove_meta_slug").val();
		 	auphonic_production_data.metadata.title = $("#title").val();
		 	auphonic_production_data.metadata.subtitle = $("#_podlove_meta_subtitle").val();
		 	auphonic_production_data.metadata.summary = $("#_podlove_meta_summary").val();
		 	auphonic_production_data.metadata.year = $("#aa").val() || (new Date()).getFullYear();
		 		
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
						 		
		 	var fail = function() { $button.trigger('fail'); }

		 	var xhr = PODLOVE.Auphonic.createCORSRequest("POST", "https://auphonic.com/api/productions.json");
		 	xhr.addEventListener("error", fail, false);
		 	xhr.addEventListener("abort", fail, false);
		 	xhr.setRequestHeader("Content-type","application/json");
		 	xhr.setRequestHeader("Authorization","Bearer " + PODLOVE.Auphonic.get_token());
		 	xhr.onload = function(e) {
		 		var response = JSON.parse(e.target.response),
		 			status = response.status_code,
		 			data = response.data,
		 			production_uuid = data.uuid,
		 			file = document.querySelector('#auphonic_local_upload_url').files[0];

		 		$.publish('/auphonic/createproduction/success', [production_uuid]);

		 		if (service == "file" && file) {
		 			$.publish('/auphonic/fileupload', [production_uuid, $button] );
		 		} else {
			 		$button.trigger('stop');
			 		$.publish('/auphonic/createproduction/done', [production_uuid]);
		 		}
		 	};
		 	$.publish('/auphonic/createproduction/before');

	 	 	$.ajax({
	 	 		dataType: "json",
	 	 		url: ajaxurl,
	 	 		data: { action: 'podlove-podcast' },
	 	 		success: function(publisher_podcast) {
	 		 		auphonic_production_data.metadata.publisher = publisher_podcast.publisher_name;
	 		 		auphonic_production_data.metadata.url = publisher_podcast.publisher_url;
	 		 		auphonic_production_data.metadata.license = publisher_podcast.license_name;
	 		 		auphonic_production_data.metadata.license_url = publisher_podcast.license_url;

				 	xhr.send(JSON.stringify(auphonic_production_data));
	 		 	}
	 	 	});
		}

		function fetch_production_data() {
			var uuid = $("#auphonic_productions option:selected").val(),
			    chapter_asset_assignment = $("#auphonic").data("assignment-chapter")
			    $button = $("#fetch_production_data_button > span").button_with_loading_indicator();

			$button.trigger('start');

			var url = 'https://auphonic.com/api/production/{uuid}.json?bearer_token={token}'
				.replace('{uuid}', uuid)
				.replace('{token}', PODLOVE.Auphonic.get_token());

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

		if ($("#auphonic").length) {

			$("#auphonic_productions").change(function(e) {
				var $production = $("option:selected", this),
					production = $production.data(),
					title = production.title ? production.title : '(no title)';

				if ($production.val() !== "0") {
					$("#_auphonic_production").val($production.val());
					$("#auphonic-production-title").html(title);
					$("#auphonic-production-ago").html(production.datestring);
					update_production_status(production.status, production.status_number);

					$("#auphonic-selected-production, #auphonic-the-production .production").show();
				} else {
					$("#auphonic-selected-production, #auphonic-the-production .production").hide();
				}

			});

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
			});
		}
	}

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
	    		$.subscribe('/auphonic/createproduction/done', fetch);

	    		fetch();
		    });
    	};

    	var fetch = function(e, production_uuid) {
    		$button.trigger('start');
    		
    		$.getJSON(url, function(data) {
    			if (!data) return;
				var production_list = [],
					auphonic_productions = data.data,
					saved_production_uuid = $("#_auphonic_production").val();

				$("#auphonic_productions").empty();
				$("#auphonic_productions").append('<option value="0">Select existing Production</option>\n');
				$(auphonic_productions).each(function(key, value) {				
					var date = new Date(value.change_time),
						output_basename = $.trim(value.output_basename),
						production_title, option_title;

					if (output_basename.length) {
						production_title = output_basename;
					} else {
						production_title = '(no title)';
					}

					option_title = production_title + ' (' + date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate() + ')';

					selected = "";
					if (production_uuid) {
						if (production_uuid == value.uuid) {
							selected = "selected";
						}
					} else if (saved_production_uuid == value.uuid) {
						selected = "selected";
					}

					$("#auphonic_productions").append('<option value="' + value.uuid + '" ' + selected + '>' + option_title + '</option>\n');
					$option = $("#auphonic_productions option:last");
					$option.data({
						"title":         production_title,
						"date":          date,
						"datestring":    timeSince(date),
						"status":        value.status_string,
						"status_number": value.status
					});
				});
				jQuery("#auphonic_productions").change();
    		}).fail(function() {
    			$button.trigger('fail');
    		}).done(function() {
    			$button.trigger('stop');
    		});
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

    	var doUpload = function(event, production_uuid, button) {
    		$.publish('/auphonic/fileupload/before', [production_uuid])

			var url = 'https://auphonic.com/api/production/{uuid}/upload.json'.replace('{uuid}', production_uuid),
				xhr2 = PODLOVE.Auphonic.createCORSRequest("POST", url),
				formData = new FormData(),
				fail = function() { button.trigger('fail'); };

			xhr2.addEventListener("error", fail, false);
			xhr2.addEventListener("abort", fail, false);
			xhr2.upload.addEventListener("progress", function(e) {
				var percent = (e.loaded / e.total) * 100;
				$.publish('/auphonic/fileupload/progress', [production_uuid, percent])
			}, false);
			xhr2.onload = function(e) {
				$.publish('/auphonic/fileupload/done', [production_uuid])
				$.publish('/auphonic/createproduction/done', [production_uuid]);
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
	    		$status = $("#auphonic-production-creation-status");
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
    		$status.delay(1500).slideUp(300);
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
    		$status.delay(1500).slideUp(300);
    	};

    	init();
    	return {};
    }());

}(jQuery));

jQuery(function($) {
	if ($("#auphonic").length && pagenow && pagenow === "podcast") {
		PODLOVE.AuphonicImport();
	}
});
