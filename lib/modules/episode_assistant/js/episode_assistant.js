jQuery(function($) {

	function update_new_episode_file_url() {
		var show_id        = $("#new_episode_show").val();
		var shows          = $.parseJSON($("#new-episode-shows-data").html());
		var episode_number = $('#new-episode-modal [name="episode_number"]').val();
		var show_slug      = shows[show_id].slug;
		var url            = shows[show_id].media_location.template;

		url = url.replace('%media_file_base_url%', shows[show_id].base_url);
		url = url.replace('%episode_slug%', '<span id="episode_file_slug">' + show_slug.toLowerCase() + episode_number + '</span>');
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
		post_title = post_title.replace('%show_slug%', show_slug.toUpperCase());
		$("#new-episode-modal .post_title").html(post_title);
	}

	function select_show() {
		var show_id        = $("#new_episode_show").val();
		var shows          = $.parseJSON($("#new-episode-shows-data").html());
		var episode_number = shows[show_id].next_number;
		var show_slug      = shows[show_id].slug;

		if ($("#new_episode_show option").length < 2) {
			$("#new_episode_show").parent().hide();
		}

		$('#new-episode-modal [name="episode_number"]').val(episode_number);
		update_new_episode_file_url();
	}

	jQuery("a[href*='post-new.php?post_type=podcast']").on('click', function(e) {
		e.preventDefault();

		update_new_episode_file_url();
		select_show();

		$( "#new-episode-modal" ).dialog({
			resizable: false,
			modal: true,
			width: '67%',
			buttons: {
				"Create New Episode": function() {
					var show_id        = $("#new_episode_show").val();
					var post_title     = $("#new-episode-modal .post_title").html();

					$( this ).dialog( "close" );

					var data = {
						action: 'podlove-create-episode',
						show_id: show_id,
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
	$("#new_episode_show").on('change', select_show);

});