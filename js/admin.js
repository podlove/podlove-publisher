(function( $ ){

	// Link a checkbox to a taxonomy
	// When you (de)activate the checkbox, the referenced taxonomy will be updated.
	$.fn.taxonomize = function( hidden_taxonomy_field ) {  
	    return this.each(function() {
			$(this).on('change', function() {
				var slug = $(this).data("name").toLowerCase();
				var old_val = hidden_taxonomy_field.val().toLowerCase().split(",");
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
				hidden_taxonomy_field.val(new_val);
				update_enclosure_list();
			});
		});
	};
	
	update_enclosure_list = function () {
		var shows = [];
		var formats = [];
		var html = "";
		
		var episode_id = $("#podlove_meta_id").val();
		var episode_slug = $("#podlove_meta_slug").val();
		
		$(".podcast_show_checkbox:checked").each(function() {
			shows.push(this);
		});
		
		$(".podcast_format_checkbox:checked").each(function() {
			formats.push(this);
		});
		
		jQuery.each(shows, function(show_index, show) {
			jQuery.each(formats, function(format_index, format) {
				var show_data   = $(show).data();
				var format_data = $(format).data();
				
				html = html
					+ show_data.media_file_base_uri
					+ show_data.episode_prefix
					+ episode_id
					+ show_data.uri_delimiter
					+ episode_slug
					+ show_data.uri_delimiter
					+ format_data.slug
					+ "."
					+ format_data.extension
					+ "</br>";
			});
		});
		
		$("#podlove_enclosure_list").html(html);
	}

})( jQuery );

jQuery(function($) {
	
	$('.podcast_show_checkbox').taxonomize($("#tax-input-podcast_shows"));
	$('.podcast_format_checkbox').taxonomize($("#tax-input-podcast_file_formats"));
	
	update_enclosure_list();
	$("#podlove_meta_id, #podlove_meta_slug").on("change", function() {
		update_enclosure_list();
	});
	
});