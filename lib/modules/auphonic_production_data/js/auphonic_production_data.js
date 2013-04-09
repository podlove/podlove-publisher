var PODLOVE = PODLOVE || {};

(function($){
	PODLOVE.Auphonic = function (container) {

		var o = {};
		var data_cache;
		var field_cache;

		var typewatch = (function() {
			var timer = 0;
			return function(callback, ms) {
				clearTimeout (timer);
				timer = setTimeout(callback, ms);
			}
		})();

		var update_auphonic_data = function (param)  {
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
					data_cache = data;
					override_fields();
				}
			});
		};

		var maybe_update_auphonic_metadata = function () {
			var slug = $("#_podlove_meta_slug").val();
			var url = podlove_media_base_url + slug + ".json";
			update_auphonic_data(url);
		};

		var override_fields = function () {
			if (!data_cache) return;

			var fields = {
				'title': data_cache.metadata.title,
				'_podlove_meta_subtitle': data_cache.metadata.subtitle,
				'_podlove_meta_summary': data_cache.metadata.summary,
				'_podlove_meta_duration': data_cache.length_timestring,
			};

			field_cache = fields;

			$.each(fields, function(field_id, remote_value) {
				var input = $("#" + field_id)
				    current_value = input.val();

				if (current_value.length == 0) {
					input.val(remote_value);
				} else if (current_value.split(/\r\n|\r|\n/g).join() != remote_value.split(/\r\n|\r|\n/g).join()) {
					var label = $('label[for="' + field_id + '"]');
					if (!label.find(".auphonic_update").length) {
						label.append(" <span class='button auphonic_update' data-field='" + field_id + "'>Import update from Auphonic</span>");
					}
				}
			});
		};

		$(container).on('click', '.auphonic_update', function(e) {
			if (!field_cache) return;

			var field = $(this).data('field'),
			    remote_value = field_cache[field];

			$("#" + field).val(remote_value);
			$(this).remove();
		});

		$("#_podlove_meta_slug").on('slugHasChanged', function() {
			maybe_update_auphonic_metadata();
		});
		maybe_update_auphonic_metadata();

		return o;

	}
}(jQuery));

jQuery(function($) {
	PODLOVE.Auphonic($("#podlove_podcast"));
});
