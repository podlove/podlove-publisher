jQuery(function($) {

	function update_new_episode_file_url() {
		var data = jQuery.parseJSON($("#new-episode-data").html());

		var url            = data.template;
		var episode_number = $("#new-episode-modal [name='episode_number']").val();
		var show_slug      = $("#new-episode-modal .show_slug").val();

		url = url.replace('%media_file_base_url%', data.base_url);
		url = url.replace('%episode_slug%', '<span id="episode_file_slug">' + show_slug.toLowerCase() + episode_number + '</span>');
		url = url.replace('%suffix%', data.suffix ? data.suffix : '');
		url = url.replace('%format_extension%', 'extension');

		$(".media_file_info .url").html(url);

		$("#episode_file_slug").editable({
			submit: 'save',
			cancel: 'cancel',
			type: 'text'
		});

		var post_title = $("#new-episode-modal .post_title").data('template');
		post_title = post_title.replace('%episode_title%', $('[name*="episode_title"]').val());
		post_title = post_title.replace('%number%', $("#new-episode-modal [name='episode_number']").val());
		post_title = post_title.replace('%show_slug%', show_slug.toUpperCase());
		$("#new-episode-modal .post_title").html(post_title);
	}

	jQuery("a[href*='post-new.php?post_type=podcast']").on('click', function(e) {
		e.preventDefault();

		update_new_episode_file_url();

		$( "#new-episode-modal" ).dialog({
			resizable: false,
			modal: true,
			width: '67%',
			buttons: {
				"Create New Episode": function() {
					var show_id        = $("#new-episode-modal .show_id").val();
					var episode_number = $("#new-episode-modal .episode_number").val();
					var episode_title  = $("#new-episode-modal .episode_title").val();
					var post_title     = $("#new-episode-modal .post_title").html();
					var episode_slug   = $("#episode_file_slug").html();

					$( this ).dialog( "close" );

					var data = {
						action: 'podlove-create-episode',
						show_id: show_id,
						slug: episode_slug,
						title: post_title
					};

					$.ajax({
						url: ajaxurl,
						data: data,
						dataType: 'json',
						success: function(result) {
							if (!result.post_edit_url) {
								alert("Oops, couldn't create Episode :/");
							}
							window.location = result.post_edit_url.replace("&amp;","&");
						}
					});
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});

		return false;
	});

	$("#new-episode-modal [name^='episode_']").on('keyup', update_new_episode_file_url);

	// debug
	// jQuery("a[href*='post-new.php?post_type=podcast']:first").click();

	
});