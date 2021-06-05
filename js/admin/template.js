(function() {

	var initTemplateComponent = function() {

		var $editor     = document.querySelector("#template-editor");
		var $title      = $editor.querySelector(".editor .title input");
		var $footer    =  $editor.querySelector("footer");
		var $navigation = $editor.querySelector(".navigation");
		var $preview    = document.querySelector("#podlove_template_shortcode_preview");

		var editor = ace.edit("ace-editor");

		document.querySelector("#fullscreen").addEventListener( 'click', function () {
			document.querySelector("body").classList.toggle("fullScreen");
			document.querySelector("#ace-editor").classList.toggle("fullScreen-editor");
			this.classList.toggle("fullscreen-on").classList.toggle("fullscreen-off");
			editor.resize();
			window.scroll(0,0); // reset window scrolling to avoid fullscreen-button positioning issues
		} );

		// local cache
		var templates   = [];

		var template = function (id, title, content) {

			var $navigationItem = $navigation.querySelector("li a[data-id='" + id + "']");
			var isMarked = false;

			var markAsUnsaved = function () {
				if (!isMarked) {
					isMarked = true;
					$navigationItem.insertAdjacentHTML('beforeend', '<span class="unsaved" title="unsaved changes"> ● </span>');
				}
			};

			var markAsSaved = function () {
				if (isMarked) {
					isMarked = false;

                    $navigationItem.querySelector(".unsaved").remove();

                    $preview.value = '[podlove-template template="' + this.title + '"]'
				}
			};

			var activate = function () {
				$title.value = this.title;
				$preview.value = '[podlove-template template="' + this.title + '"]';
				editor.getSession().setValue(this.content || "");
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
			var $this = this;
			var template_id = $this.dataset['id'];

            $navigation.querySelectorAll("li").forEach(e => e.classList.remove("active"));
			$this.closest("li").classList.add("active");

			if (templates[template_id]) {
				templates[template_id].activate();
			} else {

                const params = new URLSearchParams({
                    id: template_id,
                    action: 'podlove-template-get'
                });

                fetch(ajaxurl + '?' + params.toString())
                .then((response) => response.json())
                .then((data) => {
					templates[template_id] = template(template_id, data.title, data.content);
					templates[template_id].activate();
                })

			}

			$this.blur(); // removes link outline

			if (e) {
				e.preventDefault();
			}
		};

		var save_template = function(e) {
			var save_button = this;
			var template_id = $navigation.querySelector("li.active a").dataset["id"];
			var template_title = $title.value;
			var template_content = editor.getSession().getValue();
			var saving_icon = `<i class="podlove-icon-spinner rotate"></i>`;

			$navigation.querySelector("li.active a").insertAdjacentHTML('beforeend', saving_icon);

            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({
                    id: template_id,
					title: template_title,
					content: template_content,
                    action: 'podlove-template-update'
                })
            })
            .then((response) => response.json())
            .then((data) => {
                save_button.blur();
                $navigation.querySelector("li.active a i").remove();
                if (!data.success) {
                    console.log("Error: Could not save template.");
                } else {
                    templates[template_id].markAsSaved();
                }
            })

			e.preventDefault();
		};

		var update_title = function(e) {
			var $active_item = $navigation.querySelector("li.active a");
			var template_id  = $active_item.dataset["id"];
			var new_title    = this.value;

			// update cache
			templates[template_id].title = new_title;
			templates[template_id].markAsUnsaved();

			// update navigation element
			$active_item.querySelector(".filename").innerHTML = new_title;
		};

		var update_editor_cache = function () {
			var $active_item = $navigation.querySelector("li.active a");
			var template_id  = $active_item.dataset["id"];
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

            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({action: 'podlove-template-create'})
            })
            .then((response) => response.json())
            .then((data) => {
                if (data) {
					$navigation.querySelector("ul").insertAdjacentHTML('beforeend', "<li><a href=\"#\" data-id=\"" + data.id + "\"><span class='filename'>new template</span>&nbsp;</a></li>");

                    activate_template.bind($navigation.querySelector("ul li:last-child a"))()

					$title.focus();
                }
            })

			e.preventDefault();
		};

		var delete_template = function(e) {
			var template_id = $navigation.querySelector("li.active a").dataset['id'];

			if (window.confirm("Delete template?")) {

                fetch(ajaxurl, {
                    method: 'POST',
                    body: new URLSearchParams({action: 'podlove-template-delete', id: template_id})
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // delete navigation entry
                        $navigation
                          .querySelector("li a[data-id='" + template_id + "']")
                          .closest("li")
                          .remove()

                        // clear out editor
                        $title.value = "";
                        editor.getSession().setValue("");

                        // select other template, if available
                        $navigation.querySelector("li:first-child a").click();
                    } else {
                        console.log("Error: Could not delete template.");
                    }
                })

			}

			e.preventDefault();
		};

		$title.addEventListener('keyup', update_title);
		editor.addEventListener("change", handle_editor_change);
		editor.addEventListener("paste", update_editor_cache);

		$navigation.querySelectorAll("a[data-id]").forEach(el => el.addEventListener("click", activate_template));
		$navigation.querySelectorAll(".add a").forEach(el => el.addEventListener("click", add_template));
		$footer.querySelectorAll("a.save").forEach(el => el.addEventListener("click", save_template));
		$footer.querySelectorAll(".delete").forEach(el => el.addEventListener("click", delete_template));

		// select first template on page load
		$navigation.querySelector("li:first-child a").click();
	};

    if (document.readyState !== 'loading') {
        initTemplateComponent();
    } else {
        document.addEventListener('DOMContentLoaded', initTemplateComponent);
    }

}());
