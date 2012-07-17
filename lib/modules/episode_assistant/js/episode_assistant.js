jQuery(function($) {

	function update_new_episode_file_url() {
		var data = jQuery.parseJSON($("#new-episode-data").html());

		var url          = data.template;
		var episode_slug = $("#new-episode-modal [name='episode_number']").val();

		url = url.replace( '%media_file_base_url%', data.base_url );
		url = url.replace( '%episode_slug%', '<span id="episode_file_slug">' + episode_slug.toLowerCase() + '</span>' );
		url = url.replace( '%suffix%', data.suffix ? data.suffix : '' );
		url = url.replace( '%format_extension%', 'extension' );

		$(".media_file_info .url").html(url);

		$("#episode_file_slug").editable({
			submit: 'save',
			cancel: 'cancel',
			type: 'text'
		});

		// TODO use title template
		$("#new-episode-modal .post_title").html(episode_slug + " " + $("#new-episode-modal .episode_title").val());
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