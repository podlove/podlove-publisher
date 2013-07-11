var PODLOVE = PODLOVE || {};

(function($){

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
		
		/**
		 * Create Auphonic production.
		 */				
		 function create_auphonic_production(token) {
		 	var presetuuid = $("#create_auphonic_production_button").data('presetuuid');
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
		 	
		 	$("#fetch_create_production_status").html('<i class="podlove-icon-spinner rotate"></i>').show();
		 	
		 	if(typeof presetuuid !== undefined && presetuuid !== "") {
		 		auphonic_production_data.preset = presetuuid;
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

			$.post(module_url + "/create_auphonic_production.php?access_token=" + token, { data: JSON.stringify(auphonic_production_data) }).always(function(data) {
				console.log(data);
				var new_episode_data = data.data;						
				$("#fetch_create_production_status")
					.html('<i class="podlove-icon-ok"></i>')
					.delay(250)
					.fadeOut(500);
				$("#new_created_episode_data")
					.html('<i>The production was successfully created. You can edit this episode <a href="https://auphonic.com/engine/upload/edit/' + new_episode_data.uuid + '" target="_blank">here</a>.')
					.delay(250);
				delete new_episode_data;
				fetch_episodes(token);
			});
		 }

		function fetch_production_data(token) {
			var uuid = $("#import_from_auphonic option:selected").val(),
			    module_url = $("#auphonic").data("module-url"),
			    chapter_asset_assignment = $("#auphonic").data("assignment-chapter");

			$("#fetch_production_status").html('<i class="podlove-icon-spinner rotate"></i>').show();
			$.getJSON(module_url + '/fetch_episode.php?uuid=' + uuid + '&access_token=' + token, function(data) {

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
			}).done(function() {
				$("#fetch_production_status")
					.html('<i class="podlove-icon-ok"></i>')
					.delay(250)
					.fadeOut(500);
			});
		}
		
		function fetch_episodes(token) {
			// $("#reload_episodes_status").html('<i class="podlove-icon-spinner rotate"></i>').show();				
			
			$button = $("#reload_productions_button");
			$idle    = $button.find(".state_idle");
			$working = $button.find(".state_working");
			$success = $button.find(".state_success");

			// button: working mode
			$idle.hide();
			$working.show();

			var module_url = $("#auphonic").data("module-url");
			var productions = $.getJSON(module_url + '/fetch_episodes.php?access_token=' + token, function(data) {
				var production_list = new Array();
				var auphonic_productions = data.data;
				$("#import_from_auphonic").empty();
				$(auphonic_productions).each(function(key, value) {				
					var date = new Date(value.change_time);				
					$("#import_from_auphonic").append('<option value="' + value.uuid + '">' + value.output_basename + ' (' + date.getFullYear() + '-' + ("0" + (date.getMonth() + 1)).slice(-2) + '-' + ("0" + (date.getDay() + 1)).slice(-2) + ' ' + date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds() + ') [' + value.status_string + ']</option>\n');
				});
				
				delete auphonic_productions;
			}).done(function() {
				// button: back to idle mode
				$working.hide();
				$success.show().delay(750).fadeOut(200);
				$idle.delay(750).fadeIn(200);
			});
		}

		$("#fetch_production_data_button").click(function () {
			fetch_production_data($(this).data('token'));
		});
		
		$("#create_auphonic_production_button").click(function () {
			create_auphonic_production($(this).data('token'));
		});
		
		$("#reload_productions_button").click(function () {
			fetch_episodes($(this).data('token'));
		});
		
		$("#open_production_button").click(function () {
			window.open('https://auphonic.com/engine/upload/edit/' + $("#import_from_auphonic").find(":selected").val());
		});



		$(document).ready(function() {
			fetch_episodes($("#auphonic").data('api-key'));
			$("#auphonic-box").tabs({ active: 1 });
		});



	}
}(jQuery));

jQuery(function($) {
	PODLOVE.AuphonicImport();
});