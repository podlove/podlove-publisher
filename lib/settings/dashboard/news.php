<?php
namespace Podlove\Settings\Dashboard;

class News {

	public static function content() {
		$feeds = [
			'podlove' => [
				'link'         => 'http://podlove.org/',
				'url'          => 'http://podlove.org/feed/',
				'title'        => 'Podlove News',
				'items'        => 5,
				'show_summary' => 1,
				'show_author'  => 0,
				'show_date'    => 1,
			]
		];
		require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
		$success = \wp_dashboard_cached_rss_widget( 'podlove_dashboard_news', 'wp_dashboard_primary_output', $feeds );

		if (!$success) {
			?>
<script type="text/javascript">
jQuery.ajax(ajaxurl, {
	dataType: 'html',
	type: 'GET',
	data: { action: 'podlove-admin-news' },
	success: function(response, status, xhr) {
		jQuery("#toplevel_page_podlove_settings_handle_news .inside").html(response);
	}
});
</script>
			<?php
		}
	}
}