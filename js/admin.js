jQuery(function($) {

	$('.podcast_show_checkbox').on('change', function() {
		var slug = $(this).data("slug").toLowerCase();
		var old_val = $("#tax-input-podcast_shows").val().toLowerCase().split(",");
		var new_val = null;
		
		if ($(this).is(":checked")) {
			// add show
			old_val.push(slug);
		} else {
			// remove show
			index = $.inArray(slug, old_val);
			old_val.splice(index, 1);
		}
		
		new_val = old_val.join(",");
		
		$("#tax-input-podcast_shows").val(new_val);
	});
	
	$('.podcast_format_checkbox').on('change', function() {
		var slug = $(this).data("slug").toLowerCase();
		var old_val = $("#tax-input-podcast_file_formats").val().toLowerCase().split(",");
		var new_val = null;
		
		if ($(this).is(":checked")) {
			// add show
			old_val.push(slug);
		} else {
			// remove show
			index = $.inArray(slug, old_val);
			old_val.splice(index, 1);
		}
		
		new_val = old_val.join(",");
		console.log(new_val);
		$("#tax-input-podcast_file_formats").val(new_val);
	});
	
});