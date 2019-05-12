<?php
namespace Podlove\Settings;

use Podlove\Jobs\DownloadTimedAggregatorJob;
use \Podlove\Model;

class Analytics
{

    use \Podlove\HasPageDocumentationTrait;

    static $pagehook;

    public function __construct($handle)
    {

        if (\Podlove\get_setting('tracking', 'mode') !== "ptm_analytics") {
            return;
        }

        self::$pagehook = add_submenu_page(
            /* $parent_slug*/$handle,
            /* $page_title */__('Analytics', 'podlove-podcasting-plugin-for-wordpress'),
            /* $menu_title */__('Analytics', 'podlove-podcasting-plugin-for-wordpress'),
            /* $capability */'podlove_read_analytics',
            /* $menu_slug  */'podlove_analytics',
            /* $function   */array($this, 'page')
        );

        $this->init_page_documentation(self::$pagehook);

        // add_action( 'admin_init', array( $this, 'process_form' ) );
        add_action('admin_init', array($this, 'scripts_and_styles'));

        add_action("load-" . self::$pagehook, array($this, 'init_list_table'));

        if (isset($_GET['action']) && $_GET['action'] == 'show') {
            add_action('load-' . self::$pagehook, function () {
                add_action('add_meta_boxes_' . \Podlove\Settings\Analytics::$pagehook, function () {
                    add_meta_box(\Podlove\Settings\Analytics::$pagehook . '_release_downloads_chart', __('Downloads over Time', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Analytics::chart', \Podlove\Settings\Analytics::$pagehook, 'normal');
                    add_meta_box(\Podlove\Settings\Analytics::$pagehook . '_numbers', __('Download Numbers', 'podlove-podcasting-plugin-for-wordpress'), '\Podlove\Settings\Analytics::numbers', \Podlove\Settings\Analytics::$pagehook, 'normal');
                });
                do_action('add_meta_boxes_' . \Podlove\Settings\Analytics::$pagehook);

                wp_enqueue_script('postbox');
            });
        }

        if (isset($_GET['action']) && in_array($_GET['action'], ['export-csv', 'export-json'])) {
            $this->handle_export($_GET['action']);
        }

        add_filter('screen_settings', [$this, 'screen_settings'], 10, 2);
    }

    public function handle_export($action)
    {
        $posts      = isset($_GET['post']) ? $_GET['post'] : [];
        $ids        = count($posts) ? implode(",", $posts) : "";
        $format     = $action == 'export-csv' ? 'csv' : 'json';
        $route_base = '/podlove/v1/analytics/episodes';
        $route      = $ids ? $route_base . '/' . $ids : $route_base;

        self::rest_api_call($route, ['format' => $format]);
    }

    /**
     * API Call against WP REST API
     *
     * @param string $url
     * @param array $params
     * @return void
     */
    public static function rest_api_call($url, $params)
    {
        $request = new \WP_REST_Request('GET', $url);
        $request->set_query_params($params);
        $response = rest_do_request($request);

        $server = rest_get_server();
        $data   = $server->response_to_data($response, false);

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo wp_json_encode($data);
        die();
    }

    // needs to be initialized here so columns become configurable
    public function init_list_table()
    {
        $hidden_cols = DownloadTimedAggregatorJob::get_hidden_groups();

        // set default hidden cols
        if (!is_array($hidden_cols)) {
            update_user_meta(
                get_current_user_id(),
                DownloadTimedAggregatorJob::get_hidden_groups_key(),
                ['3y', '2y', '3q', '2q', '3w', '2w']
            );
        }

        $this->table = new \Podlove\Downloads_List_Table();
    }

    public function screen_settings($status, $args)
    {

        if ($args->base !== 'podlove_page_podlove_analytics') {
            return $status;
        }

        if (!isset($_GET['action']) || $_GET['action'] !== 'show') {
            return $status;
        }

        $tiles = [
            'download_source'  => __("Download Source", 'podlove-podcasting-plugin-for-wordpress'),
            'download_context' => __("Download Context", 'podlove-podcasting-plugin-for-wordpress'),
            'asset'            => __("Asset", 'podlove-podcasting-plugin-for-wordpress'),
            'podcast_client'   => __("Podcast Client", 'podlove-podcasting-plugin-for-wordpress'),
            'operating_system' => __("Operating System", 'podlove-podcasting-plugin-for-wordpress'),
            'geo_location'     => __("Client Location", 'podlove-podcasting-plugin-for-wordpress'),
        ];

        $option             = get_option('podlove_analytics_tiles', array());
        $option_compare_avg = get_option('podlove_analytics_compare_avg', true);

        $status .= "
		<h5>" . __('Show Analytics Tiles', 'podlove-podcasting-plugin-for-wordpress') . "</h5>
		<div class='metabox-prefs'>";

        foreach ($tiles as $id => $title) {
            $status .= "<label for='$id'>
				<input " . checked(!isset($option[$id]) || $option[$id], true, false) . " type='checkbox' value='$id' name='podlove_analytics_tiles' id='$id' />
				$title
			</label>";
        }

        $status .= "</div>";

        $status .= "
		<h5>" . __('Compare with Average Episode', 'podlove-podcasting-plugin-for-wordpress') . "</h5>
		<div class='metabox-prefs'>";

        $id = 'average-episode';
        $status .= "<label for='$id'>
			<input " . checked($option_compare_avg, true, false) . " type='checkbox' value='$id' name='podlove_analytics_compare_avg' id='$id' />
			" . __("Display average episode data", 'podlove-podcasting-plugin-for-wordpress') . "
		</label>";

        $status .= "</div>";

        return $status;
    }

    public function scripts_and_styles()
    {
        if (!isset($_REQUEST['page'])) {
            return;
        }

        if ($_REQUEST['page'] != 'podlove_analytics') {
            return;
        }

        // libraries
        wp_register_script('podlove-d3-js', \Podlove\PLUGIN_URL . '/js/admin/d3.min.js');
        wp_register_script('podlove-crossfilter-js', \Podlove\PLUGIN_URL . '/js/admin/crossfilter.min.js');

        // application

        wp_register_script('podlove-analytics-js', \Podlove\PLUGIN_URL . '/js/dist/podcast-stats.js', ['podlove-d3-js', 'podlove-crossfilter-js', 'underscore']);

        wp_localize_script('podlove-analytics-js', 'podlove_episode_names', self::episode_ids_to_names_map());

        wp_enqueue_script('podlove-analytics-js');

        wp_register_style('podlove-dc-css', \Podlove\PLUGIN_URL . '/css/dc.css', array(), \Podlove\get_plugin_header('Version'));
        wp_enqueue_style('podlove-dc-css');
    }

    public static function episode_ids_to_names_map()
    {
        global $wpdb;

        $sql = '
			SELECT
			  e.id, p.post_title
			FROM
			  ' . $wpdb->posts . ' p
			  INNER JOIN `' . Model\Episode::table_name() . '` e ON p.ID = e.`post_id`
		';
        $rows = $wpdb->get_results($sql);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->id] = $row->post_title;
        }

        return $map;
    }

    public function page()
    {

        ?>
		<div class="wrap">
			<?php
if (Model\DownloadIntentClean::first() === null) {
            $this->blank_template();
        } else {
            $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;
            switch ($action) {
                case 'show':
                    $this->show_template();
                    break;
                case 'index':
                default:
                    $this->view_template();
                    break;
            }
        }
        ?>
		</div>
		<?php
}

    public function blank_template()
    {
        ?>

		<h2><?php _e("Podcast Analytics", 'podlove-podcasting-plugin-for-wordpress');?></h2>

		<div id="welcome-panel" class="welcome-panel">
		    <div class="welcome-panel-content">
		        <h3 style="margin-top: 0px"><?php _e('Welcome to Podlove Publisher Analytics!', 'podlove-podcasting-plugin-for-wordpress')?></h3>
		        <p class="about-description">
		        	<?php if (Model\DownloadIntent::count() < 50): ?>
		        		<?php _e('There is not enough tracking data yet. Publish an episode, then come back after a while.', 'podlove-podcasting-plugin-for-wordpress');?>
		        	<?php else: ?>
		        		<span class="dashicons dashicons-hammer"></span> <?php _e('Busy crunching numbers. One plus one is&#8230; can you come back in a few minutes?', 'podlove-podcasting-plugin-for-wordpress');?>
		        	<?php endif?>
		        </p>
		        <div class="welcome-panel-column-container">
		            <div class="welcome-panel-column">
		                <h4><?php _e('While you wait ...', 'podlove-podcasting-plugin-for-wordpress')?></h4>
		                <ul>
		                	<li>
		                		<a target="_blank" href="http://docs.podlove.org/podlove-publisher/guides/download-analytics" class="welcome-icon welcome-learn-more">
		                			<?php _e('Learn more about how tracking works', 'podlove-podcasting-plugin-for-wordpress')?>
		                		</a>
		                	</li>
		                    <li>
		                        <a href="<?php echo admin_url('post-new.php?post_type=podcast') ?>" class="welcome-icon welcome-write-blog">
		                        	<?php _e('Add a new episode', 'podlove-podcasting-plugin-for-wordpress')?>
		                        </a>
		                    </li>
		                    <li>
		                        <a href="<?php echo home_url() ?>" class="welcome-icon welcome-view-site">
		                        	<?php _e('View your site', 'podlove-podcasting-plugin-for-wordpress')?>
		                        </a>
		                    </li>
		                </ul>
		            </div>
		        </div>
		    </div>
		</div>

		<?php
}

    public function view_template()
    {
        ?>

		<h2><?php _e("Podcast Analytics", 'podlove-podcasting-plugin-for-wordpress');?></h2>

		<div style="width: 100%">
			<div id="total-chart" style="height: 200px"></div>
		</div>

		<div class="clear"></div>

		<?php
$cache = \Podlove\Cache\TemplateCache::get_instance();

        $total         = $cache->cache_for('podlove_downloads_total', '\Podlove\Model\DownloadIntentClean::total_downloads', 5 * MINUTE_IN_SECONDS);
        $last_month    = $cache->cache_for('podlove_downloads_last_month', '\Podlove\Model\DownloadIntentClean::prev_month_downloads', DAY_IN_SECONDS);
        $last_7_days   = $cache->cache_for('podlove_downloads_last_7_days', '\Podlove\Model\DownloadIntentClean::last_7days_downloads', HOUR_IN_SECONDS);
        $last_24_hours = $cache->cache_for('podlove_downloads_last_day', '\Podlove\Model\DownloadIntentClean::last_24hours_downloads', HOUR_IN_SECONDS);

        $crunching_numbers_text = '(' . __('crunching numbers&#8230;', 'podlove-podcasting-plugin-for-wordpress') . ')';

        $status_html = '
			<div class="chart-loading" style="display: block;">
				<img src="' . admin_url('images/wpspin_light-2x.gif') . '" alt="Loading" width="16" height="16" />
			</div>
			<div class="chart-failed" style="display: none;">Loading Chart failed :(</div>
			<div class="chart-nodata" style="display: none;">No Chart Data</div>
		';
        ?>

		<div class="metabox-holder">
			<div class="postbox">
				<h2 class="hndle" style="cursor: inherit;"><?php _e('Downloads', 'podlove-podcasting-plugin-for-wordpress');?></h2>
				<div class="inside">

					<div class="analytics-metric-container">
						<div class="analytics-metric-box">
							<span class="analytics-description"><?php _e('All Time', 'podlove-podcasting-plugin-for-wordpress');?></span>
							<span class="analytics-value"><?php echo is_numeric($total) ? number_format_i18n($total) : $crunching_numbers_text; ?></span>
							<span class="analytics-subtext"><?php _e('Downloads of all Episodes', 'podlove-podcasting-plugin-for-wordpress');?></span>
						</div>

						<div class="analytics-metric-box">
							<span class="analytics-description"><?php _e('Last Month', 'podlove-podcasting-plugin-for-wordpress');?></span>
							<span class="analytics-value"><?php echo is_numeric($last_month['downloads']) ? number_format_i18n($last_month['downloads']) : $crunching_numbers_text; ?></span>
							<span class="analytics-subtext"><?php echo sprintf(__('Downloads in %s', 'podlove-podcasting-plugin-for-wordpress'), $last_month['homan_readable_month']); ?></span>
						</div>

						<div class="analytics-metric-box">
							<span class="analytics-description"><?php _e('Last 7 Days', 'podlove-podcasting-plugin-for-wordpress');?></span>
							<span class="analytics-value"><?php echo is_numeric($last_7_days) ? number_format_i18n($last_7_days) : $crunching_numbers_text; ?></span>
							<span class="analytics-subtext"><?php echo __('Downloads', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
						</div>

						<div class="analytics-metric-box">
							<span class="analytics-description"><?php _e('Last 24 Hours', 'podlove-podcasting-plugin-for-wordpress');?></span>
							<span class="analytics-value"><?php echo is_numeric($last_24_hours) ? number_format_i18n($last_24_hours) : $crunching_numbers_text; ?></span>
							<span class="analytics-subtext"><?php echo __('Downloads', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
						</div>

						<div class="clear"></div>

					</div>
				</div>
			</div>
		</div>

		<div class="metabox-holder">
			<div class="postbox">
				<h2 class="hndle" style="cursor: inherit;">
					<?php _e('Global Analytics for last 30 Days', 'podlove-podcasting-plugin-for-wordpress');?>
			  </h2>
				<div class="inside">

					<div id="podlove-analytics-app">
						<analytics-date-picker></analytics-date-picker>
					</div>

						<div style="float: none"></div>

						<!--
						<section id="analytics-chart-global-downloads-per-month-wrapper" class="chart-wrapper">
							<div id="analytics-chart-global-downloads-per-month" style="width: 570px">
								<h1>
									<?php _e('Downloads per Month', 'podlove-podcasting-plugin-for-wordpress');?>
								</h1>

								<?php echo $status_html; ?>
							</div>
						</section>
						-->

						<section id="analytics-global-top-episodes-wrapper" class="chart-wrapper">
							<div id="analytics-global-top-episodes">
								<h1>
									<?php _e('Top Episodes', 'podlove-podcasting-plugin-for-wordpress');?>
								</h1>

								<?php echo $status_html; ?>
							</div>
						</section>

						<section id="analytics-chart-global-assets-wrapper" class="chart-wrapper">
							<div id="analytics-chart-global-assets">
								<h1>
									<?php _e('Episode Asset', 'podlove-podcasting-plugin-for-wordpress');?>
								</h1>

								<?php echo $status_html; ?>
							</div>
						</section>

						<section id="analytics-chart-global-clients-wrapper" class="chart-wrapper">
							<div id="analytics-chart-global-clients">
								<h1>
									<?php _e('Podcast Client', 'podlove-podcasting-plugin-for-wordpress');?>
								</h1>

								<?php echo $status_html; ?>
							</div>
						</section>

						<section id="analytics-chart-global-systems-wrapper" class="chart-wrapper">
							<div id="analytics-chart-global-systems">
								<h1>
									<?php _e('Operating System', 'podlove-podcasting-plugin-for-wordpress');?>
								</h1>

								<?php echo $status_html; ?>
							</div>
						</section>

						<section id="analytics-chart-global-sources-wrapper" class="chart-wrapper">
							<div id="analytics-chart-global-sources">
								<h1>
									<?php _e('Download Source', 'podlove-podcasting-plugin-for-wordpress');?>
								</h1>

								<?php echo $status_html; ?>
							</div>
						</section>

						<div style="clear: both"></div>

				</div>
			</div>
		</div>

		<form id="podlove-analytics-export" method="get">
		<?php
$this->table->prepare_items();
        $this->table->display();
        ?>
		</form>
		<?php
}

    public static function numbers()
    {

        $episode = Model\Episode::find_one_by_id((int) $_REQUEST['episode']);

        $cache = \Podlove\Cache\TemplateCache::get_instance();
        echo $cache->cache_for('podlove_analytics_episode' . $episode->id, function () use ($episode) {

            $post = get_post($episode->post_id);

            $releaseDate = new \DateTime($post->post_date);
            $releaseDate->setTime(0, 0, 0);

            $diff             = $releaseDate->diff(new \DateTime());
            $daysSinceRelease = $diff->days;

            $downloads = array(
                'total'     => Model\DownloadIntentClean::total_by_episode_id($episode->id, "1000 years ago", "now"),
                'month'     => Model\DownloadIntentClean::total_by_episode_id($episode->id, "28 days ago", "yesterday"),
                'week'      => Model\DownloadIntentClean::total_by_episode_id($episode->id, "7 days ago", "yesterday"),
                'yesterday' => Model\DownloadIntentClean::total_by_episode_id($episode->id, "1 day ago"),
                'today'     => Model\DownloadIntentClean::total_by_episode_id($episode->id, "now"),
            );

            $peak = Model\DownloadIntentClean::peak_download_by_episode_id($episode->id);

            ob_start();
            ?>

			<div class="analytics-metric-container">
				<div class="analytics-metric-box">
					<span class="analytics-description"><?php _e('Average', 'podlove-podcasting-plugin-for-wordpress');?></span>
					<span class="analytics-value"><?php echo number_format_i18n($downloads['total'] / ($daysSinceRelease + 1), 1) ?></span>
					<span class="analytics-subtext"><?php _e('Downloads per Day', 'podlove-podcasting-plugin-for-wordpress');?></span>
				</div>

				<div class="analytics-metric-box">
					<span class="analytics-description"><?php _e('Peak', 'podlove-podcasting-plugin-for-wordpress');?></span>
					<span class="analytics-value"><?php echo number_format_i18n($peak['downloads']) ?></span>
					<span class="analytics-subtext"><?php _e('Downloads', 'podlove-podcasting-plugin-for-wordpress');?><br><?php _e('on', 'podlove-podcasting-plugin-for-wordpress');?> <?php echo mysql2date(get_option('date_format'), $peak['theday']) ?></span>
				</div>

				<div class="analytics-metric-box">
					<span class="analytics-description"><?php _e('Total', 'podlove-podcasting-plugin-for-wordpress');?></span>
					<span class="analytics-value"><?php echo number_format_i18n($downloads['total']) ?></span>
					<span class="analytics-subtext"><?php _e('Downloads', 'podlove-podcasting-plugin-for-wordpress');?></span>
				</div>

				<div class="analytics-metric-box">
					<table>
						<tbody>
							<tr>
								<td><?php _e('28 Days', 'podlove-podcasting-plugin-for-wordpress');?></td>
								<td><?php echo number_format_i18n($downloads['month']) ?></td>
							</tr>
							<tr>
								<td><?php _e('7 Days', 'podlove-podcasting-plugin-for-wordpress');?></td>
								<td><?php echo number_format_i18n($downloads['week']) ?></td>
							</tr>
							<tr>
								<td><?php _e('Yesterday', 'podlove-podcasting-plugin-for-wordpress');?></td>
								<td><?php echo number_format_i18n($downloads['yesterday']) ?></td>
							</tr>
							<tr>
								<td><?php _e('Today', 'podlove-podcasting-plugin-for-wordpress');?></td>
								<td><?php echo number_format_i18n($downloads['today']) ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="clear"></div>
			<?php

            $html = ob_get_contents();
            ob_end_clean();
            return $html;

        }, 600); // 10 minutes

    }

    public function show_template()
    {
        $episode = Model\Episode::find_one_by_id((int) $_REQUEST['episode']);
        $post    = get_post($episode->post_id);
        ?>

		<h2>
			<?php echo $post->post_title ?>
			<br><small>
				<?php echo sprintf(
            __('Released on %s (%d days ago)', 'podlove-podcasting-plugin-for-wordpress'),
            mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $post->post_date),
            number_format_i18n($episode->days_since_release())
        ) ?>
			</small>
		</h2>

		<style type="text/css">
		h2 small {
			color: #666;
		}
		</style>

		<div id="poststuff" class="metabox-holder">

			<!-- main -->
			<div id="post-body">
				<div id="post-body-content">
					<?php do_meta_boxes(self::$pagehook, 'normal', null);?>
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
			postboxes.add_postbox_toggles( '<?php echo self::$pagehook; ?>' );
		} );
		</script>

		<form style='display: none' method='get' action=''>
			<?php
wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
        wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
        ?>
		</form>

		<?php
}

    public static function chart()
    {
        $episode = Model\Episode::find_one_by_id((int) $_REQUEST['episode']);
        $post    = get_post($episode->post_id);

        ?>
		<div id="chart-zoom-selection" class="chart-menubar">
			<span><?php _e('Zoom', 'podlove-podcasting-plugin-for-wordpress');?></span>
			<a href="#" data-hours="24" class="button button-secondary"><?php _e('1d', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="168" class="button button-secondary"><?php _e('1w', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="672" class="button button-secondary"><?php _e('4w', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="0" class="button button-secondary"><?php _e('all', 'podlove-podcasting-plugin-for-wordpress');?></a>
		</div>

		<div id="chart-grouping-selection" class="chart-menubar">
			<span><?php _e('Unit', 'podlove-podcasting-plugin-for-wordpress');?></span>
			<a href="#" data-hours="1" class="button button-secondary"><?php _e('1h', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="2" class="button button-secondary"><?php _e('2h', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<!-- <a href="#" data-hours="3" class="button button-secondary">3h</a> -->
			<a href="#" data-hours="4" class="button button-secondary"><?php _e('4h', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="6" class="button button-secondary"><?php _e('6h', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="12" class="button button-secondary"><?php _e('12h', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="24" class="button button-secondary"><?php _e('1d', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="168" class="button button-secondary"><?php _e('1w', 'podlove-podcasting-plugin-for-wordpress');?></a>
			<a href="#" data-hours="672" class="button button-secondary"><?php _e('4w', 'podlove-podcasting-plugin-for-wordpress');?></a>
		</div>

		<div id="episode-performance-chart" data-episode="<?php echo $episode->id ?>">
		</div>

		<div id="episode-range-chart"></div>

		<section id="episode-source-chart-wrapper" class="chart-wrapper" data-tile-id="download_source">
			<div id="episode-source-chart">
				<h1><?php _e('Download Source', 'podlove-podcasting-plugin-for-wordpress');?><a href="#" class="reset" style="display: none"><small><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress');?></small></a></h1>
			</div>
		</section>

		<section id="episode-context-chart-wrapper" class="chart-wrapper" data-tile-id="download_context">
			<div id="episode-context-chart">
				<h1><?php _e('Download Context', 'podlove-podcasting-plugin-for-wordpress');?> <a href="#" class="reset" style="display: none"><small><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress');?></small></a></h1>
			</div>
		</section>

		<section id="episode-asset-chart-wrapper" class="chart-wrapper" data-tile-id="asset">
			<div id="episode-asset-chart">
				<h1><?php _e('Episode Asset', 'podlove-podcasting-plugin-for-wordpress');?> <a href="#" class="reset" style="display: none"><small><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress');?></small></a></h1>
			</div>
		</section>

		<section id="episode-client-chart-wrapper" class="chart-wrapper" data-tile-id="podcast_client">
			<div id="episode-client-chart">
				<h1><?php _e('Podcast Client', 'podlove-podcasting-plugin-for-wordpress');?> <a href="#" class="reset" style="display: none"><small><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress');?></small></a></h1>
			</div>
		</section>

		<section id="episode-system-chart-wrapper" class="chart-wrapper" data-tile-id="operating_system">
			<div id="episode-system-chart">
				<h1><?php _e('Operating System', 'podlove-podcasting-plugin-for-wordpress');?> <a href="#" class="reset" style="display: none"><small><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress');?></small></a></h1>
			</div>
		</section>

		<section id="episode-geo-chart-wrapper" class="chart-wrapper" data-tile-id="geo_location">
			<div id="episode-geo-chart">
				<h1><?php _e('Client Location', 'podlove-podcasting-plugin-for-wordpress');?> <a href="#" class="reset" style="display: none"><small><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress');?></small></a></h1>
			</div>
		</section>

		<div style="clear: both"></div>

		<script type="text/javascript">
		var assetNames = <?php
$assets = Model\EpisodeAsset::all();
        echo json_encode(
            array_combine(
                array_map(function ($a) {return $a->id;}, $assets),
                array_map(function ($a) {return $a->title;}, $assets)
            )
        );
        ?>;
		</script>
		<?php
}

}
