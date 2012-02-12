(function( $ ){

	// Link a checkbox to a taxonomy
	// When you (de)activate the checkbox, the referenced taxonomy will be updated.
	$.fn.taxonomize = function( hidden_taxonomy_field ) {  
	    return this.each(function() {
			$(this).on('change', function() {
				var slug = $(this).data("slug").toLowerCase();
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
			});
		});
	};

})( jQuery );

jQuery(function($) {
	
	$('.podcast_show_checkbox').taxonomize($("#tax-input-podcast_shows"));
	$('.podcast_format_checkbox').taxonomize($("#tax-input-podcast_file_formats"));
	
});