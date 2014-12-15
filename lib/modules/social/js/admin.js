(function($) {

	function update_chosen() {
		$(".chosen").chosen();
		$(".chosen-image").chosenImage();
	}

	function fetch_service(service_id, category) {
		service_id = parseInt(service_id, 10);

		if (!category) 
			return undefined;

		return $.grep(PODLOVE.Social[category].services, function(service, index) {
			return parseInt(service.id, 10) === service_id;
		})[0]; // Using [0] as the returned element has multiple indexes
	}

	function service_dropdown_handler() {
		$(document).on('change', 'select.podlove-service-dropdown', function() {
			var row = $(this).closest("tr");
			var i = $(this).closest("tr").index();
			var category = $(this).closest(".social_wrapper").data("category");
			var service = fetch_service(this.value, category);

			// Check for empty contributors / for new field
			if( typeof service === 'undefined' ) {
				row.find(".podlove-logo-column").html(""); // Empty avatar column and hide edit button
				row.find(".podlove-service-edit").hide();
				return;
			}

			// Setting data attribute and avatar field
			row.data("service-id", service.id);
			// Renaming all corresponding elements after the contributor has changed 
			row.find(".podlove-service-dropdown").attr("name", PODLOVE.Social[category].form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[id]");
			row.find(".podlove-service-value").attr("name", PODLOVE.Social[category].form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[value]");
			row.find(".podlove-service-value").attr("placeholder", service.description);
			row.find(".podlove-service-value").attr("title", service.description);
			row.find(".podlove-service-link").data("service-url-scheme", service.url_scheme);
			row.find(".podlove-service-title").attr("name", PODLOVE.Social[category].form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[title]");

			// If this is an Twitter or App.net account remove @
			if ( service.title == 'Twitter' || service.title == 'App.net' )
				row.find(".podlove-service-value").data("podlove-input-remove", "@");

			// If this is an Website, check if the URL is valid
			if ( service.title == 'Website' )
				row.find(".podlove-service-value").data("podlove-input-type", "url");
		});
	}

	$(document).on('click', '.podlove-service-link',  function() {
		if( $(this).parent().find(".podlove-service-value").val() !== '' )
			window.open( $(this).data("service-url-scheme").replace( '%account-placeholder%', $(this).parent().find(".podlove-service-value").val() ) );
	});	

	$(document).on('keydown', '.podlove-service-value',  function() {
		$(this).parent().find(".podlove-service-link").show();
	});

	$(document).on('focusout', '.podlove-service-value',  function() {
		if( $(this).val() == '' )
			$(this).parent().find(".podlove-service-link").hide();
	});

	$(document).ready(function() {
		service_dropdown_handler();

		$(".social_wrapper table").each(function(e) {

			var $this = $(this);
			var category = $this.closest(".social_wrapper").data("category");

			$this.podloveDataTable({
				rowTemplate: "#service-row-template-" + category,
				deleteHandle: ".service_remove",
				sortableHandle: ".reorder-handle",
				addRowHandle: "#add_new_service_button-" + category,
				data: PODLOVE.Social[category].existing_services,
				dataPresets: PODLOVE.Social[category].services,
				onRowLoad: function(o) {
					var i = $this.find("tr").length;

					o.row = o.row.replace(/\{\{service-id\}\}/g, o.object.id);
					o.row = o.row.replace(/\{\{id\}\}/g, i);
				},
				onRowAdd: function(o) {
					var row = $(".social_wrapper[data-category='" + category + "'] .services_table_body tr:last");

					// select object in object-dropdown
					row.find('select.podlove-service-dropdown option[value="' + o.object.id + '"]').attr('selected',true);
					// set value
					row.find('input.podlove-service-value').val(o.entry.value);
					// set title
					row.find('input.podlove-service-title').val(o.entry.title);
					// Show account/URL if not empty
					if( row.find('input.podlove-service-value').val() !== '' )
						row.find('input.podlove-service-value').parent().find(".podlove-service-link").show();

					// Update Chosen before we focus on the new service
					update_chosen();
					var new_row_id = row.find('select.podlove-service-dropdown').last().attr('id');	
					$('select.podlove-service-dropdown').change();
					
					// Focus new service
					$("#" + new_row_id + "_chzn").find("a").focus();
					clean_up_input();
				},
				onRowDelete: function(tr) {
					var object_id = tr.data("object-id"),
					    ajax_action = "podlove-services-delete-";

					switch(PODLOVE.Social[category].form_base_name) {
						case "podlove_contributor[donations]": /* fall through */
						case "podlove_contributor[services]":
							ajax_action += "contributor-services";
							break;
						case "podlove_podcast[donations]": /* fall through */
						case "podlove_podcast[services]":
							ajax_action += "podcast-services";
							break;
						default:
							console.log("Error when deleting social/donation entry: unknows form type");
					}

					var data = {
						action: ajax_action,
						object_id: object_id
					};

					$.ajax({
						url: ajaxurl,
						data: data,
						dataType: 'json'
					});
				}
			});
		});

	});
}(jQuery));
