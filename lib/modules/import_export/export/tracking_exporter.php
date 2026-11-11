<?php

namespace Podlove\Modules\ImportExport\Export;

class TrackingExporter
{
    public static function init()
    {
        add_action('wp_ajax_podlove-export-tracking', [__CLASS__, 'export_tracking']);
        add_action('wp_ajax_podlove-export-tracking-status', [__CLASS__, 'export_tracking_status']);

        self::init_download();
    }

    public static function init_download()
    {
        if (!is_admin()) {
            return;
        }

        if (isset($_GET['podlove_export_tracking']) && $_GET['podlove_export_tracking']) {
            if (!current_user_can('administrator')) {
                exit;
            }

            if (!wp_verify_nonce($_REQUEST['_podlove_nonce'] ?? '', 'podlove_export_tracking_download')) {
                exit;
            }

            $lock = fopen(self::get_tracking_export_file_path().'.lock', 'c');
            if (!$lock || !flock($lock, LOCK_SH | LOCK_NB)) {
                wp_die(__('Tracking export is busy. Please try again shortly.', 'podlove-podcasting-plugin-for-wordpress'));
            }

            if (!get_transient('podlove_tracking_export_finished') || !is_readable(self::get_tracking_export_file_path())) {
                wp_die(__('No completed tracking export is available. Please export again.', 'podlove-podcasting-plugin-for-wordpress'));
            }

            header('Content-Type: application/octet-stream');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename='.TrackingExporter::getDownloadFileName());
            header('Cache-control: private');
            header('Expires: -1');

            readfile(TrackingExporter::get_tracking_export_file_path());
            exit;
        }
    }

    public static function get_tracking_export_file_path()
    {
        $upload_dir = wp_upload_dir();

        return $upload_dir['basedir'].DIRECTORY_SEPARATOR.'tracking.tmp';
    }

    public static function export_tracking()
    {
        global $wpdb;

        if (!current_user_can('administrator')) {
            exit;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'] ?? '', 'podlove_export_tracking')) {
            exit;
        }

        // A file lock is released even if the worker is terminated. Stale progress must not block retries.
        $lock = fopen(self::get_tracking_export_file_path().'.lock', 'c');
        if (!$lock) {
            wp_send_json_error(null, 500);
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            wp_send_json_success();
        }

        ignore_user_abort(true);
        delete_transient('podlove_tracking_export_finished');
        delete_transient('podlove_tracking_export_error');
        $completed = false;
        register_shutdown_function(function () use (&$completed, $lock) {
            if (!$completed) {
                set_transient('podlove_tracking_export_error', true, DAY_IN_SECONDS);
            }
            delete_option('podlove_tracking_export_all');
            delete_option('podlove_tracking_export_progress');
            flock($lock, LOCK_UN);
            fclose($lock);
        });

        update_option('podlove_tracking_export_all', $wpdb->get_var('SELECT COUNT(*) FROM '.\Podlove\Model\DownloadIntent::table_name()));
        update_option('podlove_tracking_export_progress', 0);

        $rowsPerQuery = 1000;
        $lastId = 0;
        $progress = 0;

        $fp = gzopen(self::get_tracking_export_file_path(), 'w');

        if (!$fp || $wpdb->last_error) {
            wp_send_json_error(null, 500);
        }

        do {
            // Keeping track of the $lastId is (roughly) a bajillion times faster than paging via LIMIT.
            $sql = '
				SELECT
					id,
					user_agent_id,
					media_file_id,
					request_id,
					accessed_at,
					source,
					context,
					geo_area_id,
					lat,
					lng,
					httprange
				FROM
					'.\Podlove\Model\DownloadIntent::table_name().'
					WHERE id > '.(int) $lastId."
				ORDER BY id ASC
				LIMIT 0, {$rowsPerQuery}";
            $rows = $wpdb->get_results($sql, ARRAY_A);
            if ($wpdb->last_error) {
                gzclose($fp);
                wp_send_json_error(null, 500);
            }
            foreach ($rows as $row) {
                $line = implode(',', $row)."\n";
                if (gzwrite($fp, $line) !== strlen($line)) {
                    gzclose($fp);
                    wp_send_json_error(null, 500);
                }
                $lastId = $row['id'];
                ++$progress;
            }

            update_option('podlove_tracking_export_progress', $progress);
        } while (count($rows) > 0);

        if (!gzclose($fp)) {
            wp_send_json_error(null, 500);
        }

        $completed = true;
        set_transient('podlove_tracking_export_finished', true, DAY_IN_SECONDS);
        delete_option('podlove_tracking_export_all');
        delete_option('podlove_tracking_export_progress');
        wp_send_json_success();
    }

    public static function export_tracking_status()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error(null, 403);
        }

        // Detect workers killed without executing PHP shutdown handlers.
        $lock = fopen(self::get_tracking_export_file_path().'.lock', 'c');
        if ($lock) {
            if (flock($lock, LOCK_EX | LOCK_NB)) {
                if (get_option('podlove_tracking_export_all') !== false) {
                    delete_option('podlove_tracking_export_all');
                    delete_option('podlove_tracking_export_progress');
                    set_transient('podlove_tracking_export_error', true, DAY_IN_SECONDS);
                }
                flock($lock, LOCK_UN);
            }
            fclose($lock);
        }

        wp_send_json([
            'all' => get_option('podlove_tracking_export_all'),
            'progress' => get_option('podlove_tracking_export_progress'),
            'error' => (bool) get_transient('podlove_tracking_export_error'),
            'finished' => (bool) get_transient('podlove_tracking_export_finished'),
        ]);
        exit;
    }

    private static function getDownloadFileName()
    {
        $sitename = sanitize_key(get_bloginfo('name'));

        if (!empty($sitename)) {
            $sitename .= '.';
        }

        return $sitename.'tracking.'.date('Y-m-d').'.csv.gz';
    }
}
