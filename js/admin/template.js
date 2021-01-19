(function($) {

	$(document).ready(function() {

		var $editor     = $("#template-editor");
		var $title      = $(".editor .title input", $editor);
		var $toolbar    = $(".toolbar", $editor);
		var $footer    =  $("footer", $editor);
		var $navigation = $(".navigation", $editor);
		var $preview    = $("#podlove_template_shortcode_preview");

		var editor = ace.edit("ace-editor");

		$("#fullscreen").on( 'click', function () {
			$(document.body).toggleClass("fullScreen");
			$("#ace-editor").toggleClass("fullScreen-editor");
			$(this).toggleClass("fullscreen-on").toggleClass("fullscreen-off");
			editor.resize();
			window.scroll(0,0); // reset window scrolling to avoid fullscreen-button positioning issues
		} );

		// local cache
		var templates   = [];

		var template = function (id, title, content) {
			
			var $navigationItem = $("li a[data-id=" + id + "]", $navigation);
			var isMarked = false;

			var markAsUnsaved = function () {
				if (!isMarked) {
					isMarked = true;
					$navigationItem.html($navigationItem.html() + '<span class="unsaved" title="unsaved changes"> ● </span>');
				}
			};

			var markAsSaved = function () {
				if (isMarked) {
					isMarked = false;
					$navigationItem.find(".unsaved").remove();
					$preview.val('[podlove-template template="' + this.title + '"]');
				}
			};

			var activate = function () {
				$title.val(this.title);
				$preview.val('[podlove-template template="' + this.title + '"]');
				editor.getSession().setValue(this.content);
			};

			return {
				id: id,
				title: title,
				content: content,
				markAsUnsaved: markAsUnsaved,
				markAsSaved: markAsSaved,
				activate: activate
			}
		};

		editor.setTheme("ace/theme/chrome");
		editor.getSession().setMode("ace/mode/twig");
		editor.getSession().setUseWrapMode(true);

		var activate_template = function(e) {
			var $this = $(this);
			var template_id = $this.data('id');

			$this.closest("li")
				.addClass("active")
				.siblings().removeClass("active")
			;

			if (templates[template_id]) {
				templates[template_id].activate();
			} else {
				$.getJSON(ajaxurl, {
					id: template_id,
					action: 'podlove-template-get'
				}, function(data) {
					templates[template_id] = template(template_id, data.title, data.content);
					templates[template_id].activate();
				});
			}

			$this.blur(); // removes link outline

			if (e) {
				e.preventDefault();
			}
		};

		var save_template = function(e) {
			var save_button = $(this);
			var template_id = $("li.active a", $navigation).data("id");
			var template_title = $title.val();
			var template_content = editor.getSession().getValue();
			var saving_icon = '<i class="podlove-icon-spinner rotate"></i>';

			$("li.active a", $navigation).append(saving_icon);

			$.ajax(ajaxurl, {
				dataType: 'json',
				type: 'POST',
				data: {
					id: template_id,
					title: template_title,
					content: template_content,
					action: 'podlove-template-update'
				},
				success: function(data, status, xhr) {
					save_button.blur();
					$("li.active a i", $navigation).remove();
					if (!data.success) {
						console.log("Error: Could not save template.");
					} else {
						templates[template_id].markAsSaved();
					}
				}
			});

			e.preventDefault();
		};

		var update_title = function(e) {
			var $active_item = $("li.active a", $navigation);
			var template_id  = $active_item.data("id");
			var new_title    = $(this).val();

			// update cache
			templates[template_id].title = new_title;
			templates[template_id].markAsUnsaved();

			// update navigation element
			$(".filename", $active_item).html(new_title);
		};

		var update_editor_cache = function () {
			var $active_item = $("li.active a", $navigation);
			var template_id  = $active_item.data("id");
			var new_content  = editor.getSession().getValue();

			// update cache
			if (templates[template_id]) {
				templates[template_id].content = new_content;
				templates[template_id].markAsUnsaved();
			}
		};

		var handle_editor_change = function () {
			// only track user input, *not* programmatical change
			// @see https://github.com/ajaxorg/ace/issues/503#issuecomment-44525640
			if (editor.curOp && editor.curOp.command.name) { 
				update_editor_cache();
			}
		};

		var add_template = function(e) {

			$.ajax(ajaxurl, {
				dataType: 'json',
				type: 'POST',
				data: { action: 'podlove-template-create' },
				success: function(data, status, xhr) {
					$("ul", $navigation)
						.append("<li><a href=\"#\" data-id=\"" + data.id + "\"><span class='filename'>new template</span>&nbsp;</a></li>");

					$.proxy(activate_template, $("ul li:last a", $navigation))();

					$title.focus();
				}
			});

			e.preventDefault();
		};

		var delete_template = function(e) {
			var template_id = $("li.active a", $navigation).data('id');

			if (window.confirm("Delete template?")) {

				$.ajax(ajaxurl, {
					dataType: 'json',
					type: 'POST',
					data: {
						id: template_id,
						action: 'podlove-template-delete'
					},
					success: function(data, status, xhr) {
						if (data.success) {
							// delete navigation entry
							$("li a[data-id=" + template_id + "]", $navigation)
								.closest("li")
								.remove();

							// clear out editor
							$title.val("");
							editor.getSession().setValue("");

							// select other template, if available
							$("li:first a", $navigation).click();
						} else {
							console.log("Error: Could not delete template.");
						}
					}
				});
			}

			e.preventDefault();
		};

		$title.keyup(update_title);
		editor.on("change", handle_editor_change);
		editor.on("paste", update_editor_cache);

		$navigation.on("click", "a[data-id]", activate_template);
		$navigation.on("click", ".add a", add_template);
		$footer.on("click", "a.save", save_template);
		$footer.on("click", ".delete", delete_template);

		// select first template on page load
		$("li:first a", $navigation).click();

	});

}(jQuery));
