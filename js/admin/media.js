var PODLOVE = PODLOVE || {};
PODLOVE.media = PODLOVE.media || {};

(function($) {
	"use strict";
	
	PODLOVE.media.init =  function() {
		$(".podlove-media-upload-wrap").each(function() {
			PODLOVE.media.init_field($(this));
		});
	};

	PODLOVE.media.init_field = function(container) {
		var $upload_link = $(".podlove-media-upload", container),
		    options = $upload_link.data(),
			params  = {	
				frame:   options.frame,
				library: { type: options.type },
				button:  { text: options.button },
				className: options['class'],
				title: options.title
			}
		;
		
		if (typeof options.state != "undefined" ) params.state = options.state;
		
		options.input_target = $('#'+options.target);
		options.container = container;

		if (options.preview) {
			options.input_target.on("change", function() {
				PODLOVE.media.render_preview(options.container);
			});
		}

		// set size that is selected by default
		if (options.size) {
			wp.media.view.settings.defaultProps.size = options.size;
		}

		var file_frame = wp.media(params);
		
		file_frame.states.add([
			new wp.media.controller.Library({
				id:         'podlove_select_single_image',
				priority:   20,
				toolbar:    'select',
				filterable: 'uploaded',
				library:    wp.media.query( file_frame.options.library ),
				multiple:   false,
				editable:   true,
				displaySettings: true,
				allowLocalEdits: true
			}),
		]);
		
		file_frame.on('select update insert', function() { PODLOVE.media.insert(file_frame, options); });

		$upload_link.on('click', function() {
			file_frame.open();
		});

		container.on('click', '.podlove_reset_image', {options: options}, PODLOVE.media.reset);

		PODLOVE.media.render_preview(container);
	}

	PODLOVE.media.reset = function(e) {
		var options = e.data.options;

		options.container.find(".podlove_preview_pic").empty().hide();
		options.input_target.val("");
	};

	PODLOVE.media.render_preview = function(wrapper) {
		var preview  = $(".podlove_preview_pic", wrapper)[0],
		    $input   = $("input", wrapper).first(),
		    url      = $input.val();

		if (!url) {
			return;
		}

		$(".podlove_preview_pic", wrapper).empty().hide();

		var image = document.createElement('img');
		image.width = 300;
		image.src = url;

		var remove = document.createElement('button');
		remove.className = 'podlove_reset_image button';
		remove.appendChild(document.createTextNode('remove'));

		preview.appendChild(image);
		preview.appendChild(remove);
		preview.style.display = "block";
	};
	
	PODLOVE.media.insert = function(file_frame , options) {
		var state		= file_frame.state(), 
			selection	= state.get('selection').first().toJSON(),
			value		= selection.id,
			fetch_val   = typeof options.fetch != 'undefined' ? fetch_val = options.fetch : false
		
		/*fetch custom val like url*/
		if (fetch_val) {
			value = state.get('selection').map( function( attachment ) {
				var element = attachment.toJSON();
				
				if (fetch_val == 'url') {
					var display = state.display( attachment ).toJSON();
					
					if (element.sizes && element.sizes[display.size] && element.sizes[display.size].url) {
						return element.sizes[display.size].url;
					} else if (element.url) {
						return element.url;
					}
				}
			});
		}	
		
		// change the target input value
		options.input_target.val(value).trigger('change')
		
		// trigger event in case it is necessary (uploads)
		if (typeof options.trigger != "undefined") {
			$("body").trigger(options.trigger, [selection, options]);
		}
	}

	$(document).ready(function () {
		PODLOVE.media.init();
	});

})(jQuery);	 
