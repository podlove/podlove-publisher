(function($){
	var detect_duration = function(e) {
		var button = $("#podlove_detect_duration"),
		    status = $("#podlove_detect_duration_status")
		    url    = choose_asset_for_detection();

		var setStatusSuccess = function() {
			status.html('<i class="podlove-icon-ok"></i>');
		};

		var setStatusError = function(message) {
			status.html('<i class="podlove-icon-remove"></i> <em>' + message + '</em>');
		};

		var loader = PODLOVE.AudioDurationLoader({
			before: function() {
				status.html('<i class="podlove-icon-spinner rotate"></i>');
			},
			success: function(audio, event) {
				var duration;

				if (!audio || !audio.duration) {
					setStatusError("Could not determine duration (Error Code: #1)");
					return;
				}

				duration = PODLOVE.toDurationFormat(audio.duration);

				if (!duration) {
					setStatusError("Could not determine duration (Error Code: #2)");
					return;
				}
				
				$("#_podlove_meta_duration").val(duration);
				status.html('<i class="podlove-icon-ok"></i>');
			},
			error: function() {
				setStatusError("Could not determine duration (Error Code: #3)");
			}
		});
		
		if (url) {
			loader.load(url);
		} else {
			setStatusError("You need at least one validated media file.");
		}

		e.preventDefault();
	};

	var choose_asset_for_detection = function() {
		var url;

		if ($(".media_file_row").length) {
			url = $(".media_file_row .url a")
				.map(function() {
					return $(this).attr("href");
				})
				.filter(function() {
					return this.match(/\.(mp3|m4a|ogg|oga|opus)$/);
				})[0];
		} else {
			url = $(".podlove-permalink a").first().attr("href");
		}

		return url;
	};

	$(document).ready(function() {

		// inject detect-duration-button
		$(".row__podlove_meta_duration > span > label")
			.after(" <a href=\"#\" id=\"podlove_detect_duration\">detect duration</a> <span id=\"podlove_detect_duration_status\"></span>");

		$("#podlove_podcast").on('click', '#podlove_detect_duration', detect_duration);
	});
}(jQuery));
