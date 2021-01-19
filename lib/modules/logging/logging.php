<?php

namespace Podlove\Modules\Logging;

use Monolog\Logger;
use Podlove\Log;
use Podlove\Model;

class Logging extends \Podlove\Modules\Base
{
    protected $module_name = 'Logging';
    protected $module_description = 'View podlove related logs in dashboard. (writes logs to database)';
    protected $module_group = 'system';

    public function load()
    {
        add_action('podlove_uninstall_plugin', [$this, 'uninstall']);
        add_action('podlove_module_was_activated_logging', [$this, 'was_activated']);
        add_action('init', [$this, 'register_database_logger']);

        if (current_user_can('administrator')) {
            add_action('podlove_support_page_footer', [$this, 'dashoard_template']);
        }

        self::schedule_crons();
        add_action('podlove_cleanup_logging_table', [__CLASS__, 'cleanup_logging_table']);
    }

    public function uninstall()
    {
        LogTable::destroy();
    }

    public static function schedule_crons()
    {
        if (!wp_next_scheduled('podlove_cleanup_logging_table')) {
            wp_schedule_event(time(), 'daily', 'podlove_cleanup_logging_table');
        }
    }

    public static function cleanup_logging_table()
    {
        LogTable::cleanup();
    }

    public function was_activated($module_name)
    {
        LogTable::build();
    }

    public function register_database_logger()
    {
        global $wpdb;

        if (Logger::API > 1) {
            // WPDBHandler is not compatible to monolog 2.x so we need to bail here
            // I can't upgrade to monolog 2.x because it raises minimum PHP to 7.2
            // long term solution, maybe https: //packagist.org/packages/humbug/php-scoper
            return;
        }

        $log = Log::get();
        // write logs to database
        $log->pushHandler(new WPDBHandler($wpdb, $log->get_log_level()));
        // send critical logs via email
        // $log->pushHandler( new WPMailHandler( get_option( 'admin_email' ), "Podlove | Critical notice for " . get_option( 'blogname' ), Logger::CRITICAL ) );
    }

    public function dashoard_template()
    {
        ?>
<style type="text/css">

#podlove-log-wrapper {
	max-height: 500px;
	width: 100%;
	overflow-y: auto;
	overflow-x: inherit;
}

#podlove-log {
	font-family: monospace;
	font-size: 14px;
	line-height: 18px;
	margin-top: 5px;
}

#podlove-log td {
	vertical-align: top;
}

.log-date {
	width: 175px;
	padding: 0px 4px;
}

#podlove-log-filter {
	text-align: right;
	width: 100%;
}

#podlove-log-filter .log-level {
	padding: 0px 4px 2px 4px;
}

.log-level {
	display: inline-block;
	margin-left: 10px;
}

.log-level-200 {  } /* info */
.log-level-300,
.log-level-300 td:first-child { border-left: 2px solid #ffb900; background: #fff8e5; } /* warning */
.log-level-400,
.log-level-400 td:first-child { border-left: 2px solid #dc3232; background: #fbeaea; } /* error */
.log-level-550 { background: #95002B; color: #FAD4AF; }
.log-level-550 a { color: #F4E6AD; }

code.details {
	display: inline-block;
	margin: 0;
	padding: 5px 15px;
	font-size: smaller;
	line-height: 115%;
	color: #666;
	background: #F9F9F9;
	word-break: break-all;
	word-wrap: break-word;
}

#podlove-debug-log {
	width: 80%;
	max-width: 80%;
}
</style>

<script type="text/javascript">
(function ($) {

function filter_log() {
	var filterWrapper = $("#podlove-log-filter"),
		debug    = filterWrapper.find(".log-level.log-level-100 input[type=checkbox]:checked").length,
		info    = filterWrapper.find(".log-level.log-level-200 input[type=checkbox]:checked").length,
		warning = filterWrapper.find(".log-level.log-level-300 input[type=checkbox]:checked").length,
		error   = filterWrapper.find(".log-level.log-level-400 input[type=checkbox]:checked").length,
		log = $("#podlove-log")
	;
	
	log.find(".log-entry.log-level-100").toggle(!!debug);
	log.find(".log-entry.log-level-200").toggle(!!info);
	log.find(".log-entry.log-level-300").toggle(!!warning);
	log.find(".log-entry.log-level-400").toggle(!!error);

	// always scroll to newest when filtering
	$("#podlove-log-wrapper").scrollTop($("#podlove-log-wrapper")[0].scrollHeight);
}

$(document).ready(function() {
	// scroll down
	$("#podlove-log").on('click', '.log-details .toggle a', function(e) {
		e.preventDefault();
		$(this).closest('.log-details').find('.details').toggle();
	});
	$("#podlove-log-filter input").change(filter_log);
	filter_log();
});

})(jQuery);
</script>

		<?php
        if ($timezone = get_option('timezone_string')) {
            date_default_timezone_set($timezone);
        } ?>

		<h3><?php echo __('Debug Logging', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

		<div id="podlove-debug-log" class="card">

		<div id="podlove-log-filter">
			<div class="log-level log-level-100">
				<label>
					<input type="checkbox">
					debug
				</label>
			</div>
			<div class="log-level log-level-200">
				<label>
					<input type="checkbox">
					info
				</label>
			</div>
			<div class="log-level log-level-300">
				<label>
					<input type="checkbox" checked>
					warning
				</label>
			</div>
			<div class="log-level log-level-400">
				<label>
					<input type="checkbox" checked>
					error
				</label>
			</div>
		</div>


		<div id="podlove-log-wrapper">
		<table id="podlove-log" cellspacing="0" border="0">
			<tbody>
			<?php foreach (LogTable::find_all_by_where('time > '.strtotime('-2 weeks')) as $log_entry) { ?>
				<tr class="log-entry log-level-<?php echo $log_entry->level; ?>">
					<td class="log-date">
						<?php echo date('Y-m-d H:i:s', $log_entry->time); ?>
					</td>
					<td class="log-content">
						<span class="log-message">
							<?php echo $log_entry->message; ?>
						</span>
						<span class="log-extra">
							<?php
                            $data = json_decode($log_entry->context);
        if (isset($data->media_file_id)) {
            if ($media_file = Model\MediaFile::find_by_id($data->media_file_id)) {
                if ($episode = $media_file->episode()) {
                    if ($asset = $media_file->episode_asset()) {
                        echo sprintf('<a href="%s">%s/%s</a>', get_edit_post_link($episode->post_id), $episode->slug, $asset->title);
                    }
                }
            }
        }
        if (isset($data->error)) {
            echo sprintf(' "%s"', $data->error);
        }
        if (isset($data->episode_id)) {
            if ($episode = Model\Episode::find_by_id($data->episode_id)) {
                echo sprintf(' <a href="%s">%s</a>', get_edit_post_link($episode->post_id), get_the_title($episode->post_id));
            }
        }
        if (isset($data->http_code)) {
            echo ' HTTP Status: '.$data->http_code;
        }
        if (isset($data->mime_type, $data->expected_mime_type)) {
            echo " Expected: {$data->expected_mime_type}, but found: {$data->mime_type}";
        }
        if (isset($data->type) && $data->type == 'twig') {
            echo sprintf('in template "%s" line %d', print_r($data->template, true), $data->line);
        }

        $data = (array) $data;
        $remove_keys = ['type', 'mime_type', 'expected_mime_type', 'error', 'episode_id'];
        $extra = $data;

        foreach ($remove_keys as $key) {
            if (isset($extra[$key])) {
                unset($extra[$key]);
            }
        }

        if (count($extra) > 0) {
            ?>
								<span class="log-details">
									<span class="toggle"><a href="#"><?php echo __('toggle details', 'podlove-podcasting-plugin-for-wordpress'); ?></a></span>
									<code class="details" style="display: none"><pre><?php
                                    print_r((new \Spyc())->dump($extra, true)); ?></pre></code>
								</span>
								<?php
        } elseif (!$data && !empty($log_entry->context)) {
            ?>
								<span class="log-details">
									<span class="toggle"><a href="#"><?php echo __('toggle details', 'podlove-podcasting-plugin-for-wordpress'); ?></a></span>
									<code class="details" style="display: none"><pre><?php
                                    echo str_replace(',"', ','."\n".'"', $log_entry->context); ?></pre></code>
								</span>
								<?php
        } ?>
						</span>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		</div>
		</div>
		<?php
    }
}
