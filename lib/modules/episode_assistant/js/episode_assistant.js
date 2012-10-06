jQuery(function($) {

	function update_new_episode_file_url() {
		var podcast        = $.parseJSON($("#new-episode-podcast-data").html());
		var episode_number = $('#new-episode-modal [name="episode_number"]').val();
		var url            = podcast.episode_asset.template;

		url = url.replace('%media_file_base_url%', podcast.base_url);
		url = url.replace('%episode_slug%', '<span id="episode_file_slug">' + podcast.slug + episode_number + '</span>');
		url = url.replace('%format_extension%', 'extension');

		$(".media_file_info .url").html(url);

		$("#episode_file_slug").editable({
			submit: 'save',
			cancel: 'cancel',
			type: 'text'
		});

		var post_title = $("#new-episode-modal .post_title").data('template');
		post_title = post_title.replace('%episode_title%', $('[name*="episode_title"]').val());
		post_title = post_title.replace('%episode_number%', episode_number);
		post_title = post_title.replace('%podcast_slug%', podcast.slug.toUpperCase());
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
					var post_title = $("#new-episode-modal .post_title").html();

					$( this ).dialog("close");

					var data = {
						action: 'podlove-create-episode',
						slug: $("#episode_file_slug").html(),
						title: post_title
					};

					$.ajax({
						url: ajaxurl,
						data: data,
						dataType: 'json',
						beforeSend: function(xhr, settings) {
							$("<div>Creating episode ...</div>").dialog({modal: true});
						},
						success: function(result) {
							if (!result || !result.post_edit_url) alert("Oops, couldn't create Episode :/");
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

});