jQuery(document).ready(function($) {

	/*
	 * Creates a Show feed preview based on a generic Podcast feed
	 */
	var $update_feed_preview = function () {
		$url_preview = $("#feed_subscribe_url_preview");
		$show_slug = $("#podlove_show_slug").val();
		
		if ( $show_slug !== '' ) {
			$slug_preview_url = $url_preview.data('show-preview-string') + ' ' +
				$url_preview.data('show-feed-base-url') + '/show/' +
				$show_slug + '/feed/' +
				$url_preview.data('show-feed-slug');
		} else {
			$slug_preview_url = '';
		}
		
		$url_preview.text($slug_preview_url);
	}

	$update_feed_preview();

	$("#podlove_show_slug").on( 'keyup', function () {
		$update_feed_preview();
	} );

});




