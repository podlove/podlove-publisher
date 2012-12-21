(function($){

	// twitter-like character counter
	$.fn.count_characters = function(options) {

	    var settings = $.extend( {
	        limit: 140,
	        on_negative: function($textarea, $counter_div) {
	            $counter_div.css('color', 'red')
	        },
	        on_positive: function($textarea, $counter_div) {
	            $counter_div.css('color', '#333')
	        },
	        title: ''
	    }, options);

	    this.filter('textarea').each(function() {

	    	var title = settings.title ? 'title="' + settings.title + '"' : '';
	        $(this).after("<div class='character_counter' " + title + ">...</div>")

	        $(this).keyup(function(e) {
	            var characters = $(this).val().length,
	                characters_left = settings.limit - characters,
	                $counter_div = $(this).next("div");

	            $counter_div.html(characters_left);

	            if (characters_left < 0) {
	                if (settings.on_negative)
	                    settings.on_negative($(this), $counter_div);
	            } else {
	                if (settings.on_positive)
	                    settings.on_positive($(this), $counter_div);
	            }

	        }).keyup();

	    });

	};

})(jQuery);