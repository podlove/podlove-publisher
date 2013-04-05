jQuery(document).ready(function($) {

	$("#_podlove_meta_slug").bind('change', function() {
		var slug = $("#_podlove_meta_slug").val();
		var url = podlove_media_base_url + slug + ".json";
		update_auphonic_data(url);
	});

	function update_auphonic_data (param)  {
		var data = {
			action: 'get_auphonic_data',
			url: param
		};
		$.ajax({
	    	type: "GET",
			dataType: "json",
			data: data,
			url: ajaxurl,
			success: function (data) {
				if(confirm("Want to set some meta data for "+data.metadata.title + "?")) {
					$("#title").val(data.metadata.title);
					$("#_podlove_meta_subtitle").val(data.metadata.subtitle);
					$("#_podlove_meta_summary").val(data.metadata.summary);
					$("#_podlove_meta_duration").val(data.length_timestring);
				}
			},
			error: function(data) {
				// could ne anything. just ignore.
			}
		});
	} 

});
