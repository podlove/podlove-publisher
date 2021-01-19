<?php

namespace Podlove\Modules\Networks\Settings;

class Dashboard
{
    public static $pagehook;

    public function __construct()
    {
        // use \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE to replace
        // default first item name
        Dashboard::$pagehook = add_submenu_page(
            // $parent_slug
            \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE,
            // $page_title
            __('Dashboard', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Dashboard', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE,
            // $function
            [$this, 'settings_page']
        );

        add_action(Dashboard::$pagehook, function () {
            wp_enqueue_script('postbox');
            add_screen_option('layout_columns', [
                'max' => 2, 'default' => 2,
            ]);

            wp_register_script(
                'cornify-js',
                \Podlove\PLUGIN_URL.'/js/admin/cornify.js'
            );
            wp_enqueue_script('cornify-js');
        });
    }

    public static function settings_page()
    {
        add_meta_box(Dashboard::$pagehook.'_right_now', __('At a glance', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Modules\Networks\Settings\Dashboard::right_now', Dashboard::$pagehook, 'normal');
        add_meta_box(Dashboard::$pagehook.'_about', __('About', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Dashboard\About::content', Dashboard::$pagehook, 'side');
        add_meta_box(Dashboard::$pagehook.'_podcast_overview', __('Podcasts', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Modules\Networks\Settings\Dashboard::podcast_overview', Dashboard::$pagehook, 'normal');
        add_meta_box(Dashboard::$pagehook.'_list_overview', __('Lists', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Modules\Networks\Settings\Dashboard::list_overview', Dashboard::$pagehook, 'normal');

        do_action('podlove_network_dashboard_meta_boxes'); ?>
		<div class="wrap">
			<h2><?php echo __('Podlove Network Dashboard', 'podlove-podcasting-plugin-for-wordpress'); ?></h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				
				<!-- sidebar -->
				<div id="side-info-column" class="inner-sidebar">
					<?php do_action('podlove_settings_before_sidebar_boxes'); ?>
					<?php do_meta_boxes(Dashboard::$pagehook, 'side', null); ?>
					<?php do_action('podlove_settings_after_sidebar_boxes'); ?>
				</div>

				<!-- main -->
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_action('podlove_settings_before_main_boxes'); ?>
						<?php do_meta_boxes(Dashboard::$pagehook, 'normal', null); ?>
						<?php do_meta_boxes(Dashboard::$pagehook, 'additional', null); ?>
						<?php do_action('podlove_settings_after_main_boxes'); ?>						
					</div>
				</div>

				<br class="clear"/>

			</div>

			<!-- Stuff for opening / closing metaboxes -->
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ){
				// close postboxes that should be closed
				$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				// postboxes setup
				postboxes.add_postbox_toggles( '<?php echo \Podlove\Podcast_Post_Type::NETWORK_SETTINGS_PAGE_HANDLE; ?>' );
			} );
			</script>

			<form style='display: none' method='get' action=''>
				<?php
                wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
        wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
			</form>

		</div>
		<?php
    }

    public static function right_now()
    {
        $podcasts = \Podlove\Modules\Networks\Model\Network::podcast_blog_ids();
        $number_of_podcasts = count($podcasts);

        if (!$number_of_podcasts) {
            echo __('No podcasts exist yet.', 'podlove-podcasting-plugin-for-wordpress');

            return;
        }

        $episodes_total = 0;
        $episodes_total_per_status = [
            'publish' => 0,
            'private' => 0,
            'future' => 0,
            'draft' => 0,
        ];
        $episodes_total_length = 0;
        $episode_total_average_length = 0;
        $media_file_total_average_size = 0;
        $media_file_total_size = 0;

        foreach ($podcasts as $podcast) {
            switch_to_blog($podcast);
            $statistics = \Podlove\Settings\Dashboard\Statistics::prepare_statistics();

            $episodes_total += $statistics['total_number_of_episodes'];

            array_walk($statistics['episodes'], function ($posts, $type) use (&$episodes_total_per_status) {
                switch ($type) {
                    case 'publish':
                        $episodes_total_per_status['publish'] += $posts;

                    break;
                    case 'publish':
                        $episodes_total_per_status['private'] += $posts;

                    break;
                    case 'future':
                        $episodes_total_per_status['future'] += $posts;

                    break;
                    case 'draft':
                        $episodes_total_per_status['draft'] += $posts;

                    break;
                }
            });

            $episodes_total_length += $statistics['total_episode_length'];
            $media_file_total_size += $statistics['total_media_file_size'];
            restore_current_blog();
        }

        // Devide stats by number of Podcasts
        $episode_total_average_length = $episodes_total_length / $episodes_total;
        $media_file_total_average_size = $media_file_total_size / $episodes_total; ?>
		<div class="podlove-dashboard-statistics-wrapper">
			<h4>Episodes</h4>
			<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo $episodes_total_per_status['publish']; ?>
					</td>
					<td>
						<span style="color: #2c6e36;"><?php echo __('Published', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo $episodes_total_per_status['private']; ?>
					</td>
					<td>
						<span style="color: #b43f56;"><?php echo __('Private', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo $episodes_total_per_status['future']; ?>
					</td>
					<td>
						<span style="color: #a8a8a8;"><?php echo __('To be published', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo $episodes_total_per_status['draft']; ?>
					</td>
					<td>
						<span style="color: #c0844c;"><?php echo __('Drafts', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column podlove-dashboard-total-number">
						<?php echo $episodes_total; ?>
					</td>
					<td class="podlove-dashboard-total-number">
						<?php echo __('Total', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</td>
				</tr>
			</table>
		</div>
		<div class="podlove-dashboard-statistics-wrapper">
			<h4><?php echo __('Statistics', 'podlove-podcasting-plugin-for-wordpress'); ?></h4>
			<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo gmdate('H:i:s', $episode_total_average_length); ?>
					</td>
					<td>
						<?php echo __('is the average length of an episode', 'podlove-podcasting-plugin-for-wordpress'); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php
                            $days = round($episodes_total_length / 3600 / 24, 1);
        echo sprintf(_n('%s day', '%s days', $days, 'podlove-podcasting-plugin-for-wordpress'), $days); ?>
					</td>
					<td>
						<?php echo __('is the total playback time of all episodes', 'podlove-podcasting-plugin-for-wordpress'); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo \Podlove\format_bytes($media_file_total_average_size, 1); ?>
					</td>
					<td>
						<?php echo __('is the average media file size', 'podlove-podcasting-plugin-for-wordpress'); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo \Podlove\format_bytes($media_file_total_size, 1); ?>
					</td>
					<td>
						<?php echo __('is the total media file size', 'podlove-podcasting-plugin-for-wordpress'); ?>.
					</td>
				</tr>
				<tr>
					<td class="podlove-dashboard-number-column">
						<?php echo sprintf(_n('%s podcast', '%s podcasts', $number_of_podcasts, 'podlove-podcasting-plugin-for-wordpress'), $number_of_podcasts); ?>
					</td>
					<td>
						<?php echo __('exist in your WordPress installation', 'podlove-podcasting-plugin-for-wordpress'); ?>.
					</td>
				</tr>
				<?php do_action('podlove_dashboard_statistics_network'); ?>
			</table>
		</div>
		<p>
			<?php echo sprintf(__('You are using %s', 'podlove-podcasting-plugin-for-wordpress'), '<strong>Podlove Publisher '.\Podlove\get_plugin_header('Version').'</strong>'); ?>.
		</p>
		<?php
    }

    public static function podcast_overview()
    {
        switch_to_blog(1);
        $table = new \Podlove\Modules\Networks\Podcast_List_Table();
        $table->prepare_items();
        $table->display();
        restore_current_blog();
    }

    public static function list_overview()
    {
        switch_to_blog(1);
        $table = new \Podlove\Modules\Networks\PodcastList_List_Table();
        $table->prepare_items();
        $table->display();
        restore_current_blog();
    }
}
