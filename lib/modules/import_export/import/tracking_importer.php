<?php

namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\CronJobRunner;

class TrackingImporter
{
    // path to import file
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public static function init()
    {
        if (!is_admin()) {
            return;
        }

        if (!isset($_FILES['podlove_import_tracking'])) {
            return;
        }

        if (!current_user_can('administrator')) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'], 'podlove_import_tracking')) {
            return;
        }

        set_time_limit(10 * MINUTE_IN_SECONDS);

        add_filter('upload_mimes', function ($mimes_types) {
            $mimes_types['gz'] = 'application/x-gzip';

            return $mimes_types;
        }, 99);

        add_filter('wp_check_filetype_and_ext', function ($types, $file, $filename, $mimes) {
            $wp_filetype = wp_check_filetype($filename, $mimes);
            $ext = $wp_filetype['ext'];
            $type = $wp_filetype['type'];
            if (in_array($ext, ['gz'])) {
                $types['ext'] = $ext;
                $types['type'] = $type;
            }

            return $types;
        }, 99, 4);

        require_once ABSPATH.'/wp-admin/includes/file.php';

        $file = wp_handle_upload($_FILES['podlove_import_tracking'], ['test_form' => false]);
        if ($file && (!isset($file['error']) || !$file['error'])) {
            update_option('podlove_import_tracking_file', $file['file']);
            if (!($file = get_option('podlove_import_tracking_file'))) {
                return;
            }

            CronJobRunner::create_job('\Podlove\Modules\ImportExport\Import\TrackingImporterJob');
        } else {
            echo '<div class="error"><p>'.$file['error'].'</p></div>';
        }

        add_action('admin_notices', [__CLASS__, 'print_notice']);
    }

    public static function print_notice()
    {
        ?>
		<div class="updated">
			<p>
				<strong>
					<?php echo __('Tracking Import Started', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</strong>
			</p>
			<p>
				<?php echo __('See "Background Jobs" section below for progress.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>
		</div>
		<?php
    }
}
