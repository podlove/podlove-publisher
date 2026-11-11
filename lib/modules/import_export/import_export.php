<?php

namespace Podlove\Modules\ImportExport;

class Import_Export extends \Podlove\Modules\Base
{
    protected $module_group = 'system';

    public function get_module_name()
    {
        return __('Import &amp; Export', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function get_module_description()
    {
        return __('Import &amp; export podlove data for backup or migration to another WordPress instance.', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function load()
    {
        add_action('admin_init', function () {
            Export\PodcastExporter::init();
            Import\PodcastImporter::init();
            Export\TrackingExporter::init();
            Import\TrackingImporter::init();
            $this->register_tools();
        });

        add_action('admin_enqueue_scripts', function () {
            if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'podlove_tools_settings_handle') {
                wp_enqueue_script('podlove_admin_import_script', $this->get_module_url().'/js/import.js', ['jquery'], \Podlove\get_plugin_header('Version'));
                wp_enqueue_script('podlove_admin_export_script', $this->get_module_url().'/js/export.js', [], \Podlove\get_plugin_header('Version'), true);
            }
        });

        add_action('admin_notices', function () {
            if (!isset($_GET['page'])) {
                return false;
            }

            if ($_GET['page'] != 'podlove_tools_settings_handle') {
                return false;
            }

            if (!isset($_GET['status'])) {
                return false;
            } ?>
			<div class="updated">
				<p>
					<?php
                    switch ($_GET['status']) {
                        case 'success':
                            echo __('Import successful. Happy podcasting!');

                            break;
                        case 'version-warning':
                            echo __('Heads up: Your export file was exported from a Publisher with a different version. If possible, both Publisher versions should be identical. However, that might not be a problem. Happy podcasting!');

                            break;
                    } ?>
				</p>
			</div>
			<?php
        });
    }

    public function register_tools()
    {
        \Podlove\add_tools_section(
            'import-export',
            __('Import & Export', 'podlove-podcasting-plugin-for-wordpress'),
            function () {
                if (defined('SAVEQUERIES') && SAVEQUERIES) {
                    ?>
					<div class="error">
						<p>
							<b><?php echo __('Heads up!', 'podlove-podcasting-plugin-for-wordpress'); ?></b>
							<?php echo __('The WordPress debug option <code>SAVEQUERIES</code> is active. This might lead to memory issues when exporting or importing tracking data.<br>It is probably defined in <code>wp-config.php</code>. Please turn it off before using the export tool.', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</p>
					</div>
					<?php
                }
            }
        );

        \Podlove\add_tools_field(
            'export-podcast',
            __('Podcast Export', 'podlove-podcasting-plugin-for-wordpress'),
            [$this, 'tools_podcast_export'],
            'import-export'
        );

        \Podlove\add_tools_field(
            'export-tracking',
            __('Tracking Export', 'podlove-podcasting-plugin-for-wordpress'),
            [$this, 'tools_tracking_export'],
            'import-export'
        );

        \Podlove\add_tools_field(
            'import-podcast',
            __('Podcast Import', 'podlove-podcasting-plugin-for-wordpress'),
            [$this, 'tools_podcast_import'],
            'import-export'
        );

        \Podlove\add_tools_field(
            'import-tracking',
            __('Tracking Import', 'podlove-podcasting-plugin-for-wordpress'),
            [$this, 'tools_tracking_import'],
            'import-export'
        );
    }

    public function tools_podcast_export()
    {
        echo sprintf(
            __('This export complements the existing %sWordPress export tool%s. It contains all relevant podcast data to enable you to move from this WordPress instance to another. Step by step:', 'podlove-podcasting-plugin-for-wordpress'),
            '<a href="'.admin_url('export.php').'">',
            '</a>'
        ); ?>
		<ol>
			<li>
				<?php echo sprintf(
				    __('Go to the %sWordPress export tool%s and export all data.', 'podlove-podcasting-plugin-for-wordpress'),
				    '<a href="'.admin_url('export.php').'">',
				    '</a>'
				); ?>
			</li>
			<li><?php echo __('Import this file to your new WordPress instance.', 'podlove-podcasting-plugin-for-wordpress'); ?></li>
			<li><?php echo __('Use the button below to export the podcast data file.', 'podlove-podcasting-plugin-for-wordpress'); ?></li>
			<li><?php echo __('In your new WordPress instance, import that file.', 'podlove-podcasting-plugin-for-wordpress'); ?></li>
		</ol>

        <p>
            <a id="podlove_podcast_export" href="<?php echo esc_url(add_query_arg(['podlove_export' => 1, '_podlove_nonce' => wp_create_nonce('podlove_export')], admin_url('admin.php'))); ?>" class="button"><?php echo esc_html__('Export Podcast Data', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
            <a id="podlove_podcast_download" class="button button-primary" style="display:none"><?php echo esc_html__('Download podcast data', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
        </p>
        <p id="podlove_podcast_export_status" role="status" aria-live="polite"
            data-preparing="<?php echo esc_attr__('Preparing podcast export… Keep this page open.', 'podlove-podcasting-plugin-for-wordpress'); ?>"
            data-slow="<?php echo esc_attr__('Still preparing the export. Large podcasts can take a few minutes.', 'podlove-podcasting-plugin-for-wordpress'); ?>"
            data-ready="<?php echo esc_attr__('Your export is ready. Use Download podcast data to save it. You can download it again while this page stays open.', 'podlove-podcasting-plugin-for-wordpress'); ?>"
            data-failed="<?php echo esc_attr__('The export could not be prepared. Please try again. If it keeps failing, check the server logs for database, memory or temporary storage errors.', 'podlove-podcasting-plugin-for-wordpress'); ?>"
            data-expired="<?php echo esc_attr__('This export link has expired or you no longer have access. Reload the Tools page and try again.', 'podlove-podcasting-plugin-for-wordpress'); ?>"
            data-timeout="<?php echo esc_attr__('The export took too long. Please try again. If this repeats, ask your administrator to check the server resource limits.', 'podlove-podcasting-plugin-for-wordpress'); ?>"></p>
		<?php
    }

    public function tools_tracking_export()
    {
        ?>
        <p><?php echo esc_html__('Export tracking data for import into another WordPress installation. You can leave this page open while the file is prepared.', 'podlove-podcasting-plugin-for-wordpress'); ?></p>
        <p>
            <button type="button" id="podlove_tracking_export" class="button" disabled><?php echo esc_html__('Export Tracking Data', 'podlove-podcasting-plugin-for-wordpress'); ?></button>
            <a id="podlove_tracking_download" class="button button-primary" style="display:none" href="<?php echo esc_url(add_query_arg(['podlove_export_tracking' => 1, '_podlove_nonce' => wp_create_nonce('podlove_export_tracking_download')], admin_url('admin.php'))); ?>"><?php echo esc_html__('Download tracking data', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
        </p>
        <p id="podlove_tracking_export_status" role="status" aria-live="polite"></p>
        <script>
        (function($) {
            var timer, starting = false, failures = 0, requestFailed = false;
            var button = $('#podlove_tracking_export');
            var download = $('#podlove_tracking_download');
            var status = $('#podlove_tracking_export_status');
            var messages = <?php echo wp_json_encode([
                'starting' => __('Preparing export…', 'podlove-podcasting-plugin-for-wordpress'),
                'ready' => __('Your export is ready. Use Download tracking data to save it. It remains available for 24 hours or until the next export.', 'podlove-podcasting-plugin-for-wordpress'),
                'failed' => __('The export could not be completed. Please try again.', 'podlove-podcasting-plugin-for-wordpress'),
                'retry' => __('Unable to check export status. Retrying…', 'podlove-podcasting-plugin-for-wordpress'),
                'disconnected' => __('Unable to check export status. Reload this page to reconnect.', 'podlove-podcasting-plugin-for-wordpress'),
            ]); ?>;

            function schedule() {
                window.clearTimeout(timer);
                timer = window.setTimeout(check, 1500);
            }
            function check() {
                $.ajax({url: ajaxurl, data: {action: 'podlove-export-tracking-status'}, dataType: 'json', cache: false, timeout: 15000})
                    .done(function(result) {
                        failures = 0;
                        if (result.finished && !starting) {
                            button.prop('disabled', false);
                            download.show();
                            status.text(messages.ready);
                        } else if (result.all !== false || starting) {
                            button.prop('disabled', true);
                            download.hide();
                            var total = Number(result.all);
                            status.text(total > 0 ? Math.min(100, Math.round(100 * Number(result.progress) / total)) + '%' : messages.starting);
                            schedule();
                        } else {
                            button.prop('disabled', false);
                            download.hide();
                            status.text(result.error || requestFailed ? messages.failed : '');
                        }
                    }).fail(function() {
                        status.text(++failures < 5 ? messages.retry : messages.disconnected);
                        if (failures < 5) schedule();
                    });
            }
            button.on('click', function() {
                starting = true;
                requestFailed = false;
                button.prop('disabled', true);
                download.hide();
                status.text(messages.starting);
                $.ajax({
                    url: ajaxurl, type: 'POST', dataType: 'json', timeout: 30000,
                    data: {action: 'podlove-export-tracking', _podlove_nonce: <?php echo wp_json_encode(wp_create_nonce('podlove_export_tracking')); ?>}
                }).fail(function() {
                    requestFailed = true;
                }).always(function() {
                    starting = false;
                    schedule();
                });
                schedule();
            });
            check();
        }(jQuery));
        </script>
		<?php
    }

    public function tools_podcast_import()
    {
        ?>
		<p>
			<?php echo __('Use this import on <strong>fresh installs only</strong>! Otherwise you may lose data. In any case, you should have backups.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>

		<form method="POST" enctype="multipart/form-data">
			(<span><?php echo self::get_maximum_upload_size_text(); ?></span>)
			<input type="file" name="podlove_import"/>
			<input type="submit" value="<?php echo __('Import Podcast Data', 'podlove-podcasting-plugin-for-wordpress'); ?>" class="button" />
			<?php wp_nonce_field('podlove_import', '_podlove_nonce'); ?>
		</form>
		<?php
    }

    public function tools_tracking_import()
    {
        ?>
		<form method="POST" enctype="multipart/form-data">
			(<span><?php echo self::get_maximum_upload_size_text(); ?></span>)
			<input type="file" name="podlove_import_tracking"/>
			<input type="submit" value="<?php echo __('Import Tracking Data', 'podlove-podcasting-plugin-for-wordpress'); ?>" class="button" />
			<?php wp_nonce_field('podlove_import_tracking', '_podlove_nonce'); ?>
		</form>
		<?php
    }

    public static function get_maximum_upload_size_text()
    {
        // this is exactly the same way it is done in wp_import_upload_form()
        $bytes = apply_filters('import_upload_size_limit', \wp_max_upload_size());
        $size = \size_format($bytes);

        return sprintf(__('Maximum size: %s'), $size);
    }
}
