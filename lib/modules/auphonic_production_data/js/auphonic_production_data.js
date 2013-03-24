jQuery(document).ready(function($) {

	$("#_podlove_meta_slug").bind('change', function() {	
		slug = $("#_podlove_meta_slug").val();
		url = podlove_media_base_url + slug + ".json";
		update_auphonic_data(url);
	});

	function update_auphonic_data (url)  {
		$.ajax({
	    	type: "GET",
			dataType: "json",
			url: url,
			success: function (data) {
				if(confirm("Want to set some meta data for "+data.metadata.title + "?")) {
					$("#title").val(data.metadata.title);
					$("#_podlove_meta_subtitle").val(data.metadata.subtitle);
					$("#_podlove_meta_summary").val(data.metadata.summary);
					$("#_podlove_meta_duration").val(data.length_timestring);
				}
			}
		});
	} 

});