<?php

namespace Podlove\Jobs;

class ToolsSectionCronDiagnostics
{
    public static function init()
    {
        add_action('podlove_jobs_tools_end', [__CLASS__, 'view']);

        add_action('wp_ajax_podlove-cron-diag-start', [__CLASS__, 'diagnosis_start']);
        add_action('wp_ajax_podlove-cron-diag-check', [__CLASS__, 'diagnosis_check']);
        add_action('podlove_cron_diagnosis_cron', [__CLASS__, 'register_cron_executed']);
    }

    public static function diagnosis_start()
    {
        if (!current_user_can('administrator')) {
            exit;
        }

        if (!wp_verify_nonce($_REQUEST['nonce'], 'podlove_ajax')) {
            http_response_code(401);
            exit;
        }

        update_option('podlove_cron_diagnosis', 'started');
        update_option('podlove_cron_diagnosis_tries', 0);
        wp_schedule_single_event(time(), 'podlove_cron_diagnosis_cron');
        \Podlove\AJAX\Ajax::respond_with_json(['success' => true]);
    }

    public static function diagnosis_check()
    {
        if (!current_user_can('administrator')) {
            exit;
        }

        if (!wp_verify_nonce($_REQUEST['nonce'], 'podlove_ajax')) {
            http_response_code(401);
            exit;
        }

        $tries = get_option('podlove_cron_diagnosis_tries', 0);
        update_option('podlove_cron_diagnosis_tries', $tries + 1);
        \Podlove\AJAX\Ajax::respond_with_json([
            'tries' => $tries + 1,
            'success' => get_option('podlove_cron_diagnosis') == 'executed',
        ]);
    }

    public static function register_cron_executed()
    {
        update_option('podlove_cron_diagnosis', 'executed');
    }

    public static function view()
    {
        $cron_constants = [
            'ALTERNATE_WP_CRON',
            'DISABLE_WP_CRON',
        ];
        $cron_constants = array_map(function ($constant) {
            return $constant.': '.(defined($constant) ? (constant($constant) ? 'on' : 'off') : __('not defined', 'podlove-podcasting-plugin-for-wordpress'));
        }, $cron_constants); ?>

		<div id="podlove-cron-diagnosis-teaser">
			<?php echo esc_html__('Jobs not working properly?', 'podlove-podcasting-plugin-for-wordpress'); ?> <button class="button" id="podlove-cron-diagnosis"><?php echo esc_html__('Run WP Cron Diagnosis', 'podlove-podcasting-plugin-for-wordpress'); ?></button>
		</div>

		<div id="podlove-cron-diagnosis-wrapper">
			<h4><?php echo esc_html__('WP Cron Diagnostics', 'podlove-podcasting-plugin-for-wordpress'); ?></h4>

			<p>
				<strong><?php echo esc_html__('PHP Constants', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
				<code style="display: block">
					<?php echo implode('<br>', $cron_constants); ?>
				</code>
			</p>


			<ul>
				<li id="diagnosis-item-reach-wp-cron">
					<?php echo sprintf(esc_html__('Is %s accessible?', 'podlove-podcasting-plugin-for-wordpress'), '<code>'.esc_html(site_url('wp-cron.php')).'</code>'); ?> <i class="podlove-icon-spinner rotate" style="display: none"></i> <span class="result"></span>
				</li>
				<li id="diagnosis-item-check-cron-exec">
					<?php echo esc_html__('Are scheduled crons run?', 'podlove-podcasting-plugin-for-wordpress'); ?> <i class="podlove-icon-spinner rotate" style="display: none"></i> <span class="result"></span>
				</li>
			</ul>
		</div>

<script type="text/javascript">
(function($) {

	var diagnosisButton = $("#podlove-cron-diagnosis");

	var initReachWpCron = function() {
		var taskWrapper = $("#diagnosis-item-reach-wp-cron");
		var cronUrl = '<?php echo esc_js(site_url('wp-cron.php')); ?>';
		var spinner = taskWrapper.find('i.podlove-icon-spinner');

		spinner.show();

		$.ajax({
			url: cronUrl,
		}).done(function (data, textStatus, jqXHR) {
			taskWrapper.find(".result").html(<?php echo wp_json_encode(__('Yes, good!', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> + " <i class=\"podlove-icon-ok\"></i>");
		}).fail(function (data, textStatus, jqXHR) {
			taskWrapper.find(".result").html(<?php echo wp_json_encode(__('ERROR!', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> + " " + data.status + " " + data.textStatus + " <i class=\"podlove-icon-remove\"></i>");
		}).always(function() {
			spinner.hide();
		})
		;
	}

	var initLookForCronSuccess = function() {
		var taskWrapper = $("#diagnosis-item-check-cron-exec");
		var result = taskWrapper.find(".result");
		var helpHtml = <?php echo wp_json_encode(__('There are many reasons why WP Cron may not work. <a href="https://encrypted.google.com/search?hl=en&q=wordpress%20cron%20not%20working" target="_blank">Try this Google search to find out why.</a>', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
		var maxAttempts = 30;
		var spinner = taskWrapper.find('i.podlove-icon-spinner');

		$.ajax({
			url: ajaxurl,
			data: {
				action: 'podlove-cron-diag-check',
				nonce: podlove_admin_global.nonce_ajax
			}
		}).always(function(data) {
			if (data && data.success) {
				result.html(<?php echo wp_json_encode(__('Yes, good!', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> + " <i class=\"podlove-icon-ok\"></i>");
				spinner.hide();
			} else {
				if (data && data.tries > maxAttempts) {
					result.html(<?php echo wp_json_encode(__('Sorry, it looks like WP Cron is not working.', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> + " " + helpHtml + " <i class=\"podlove-icon-remove\"></i>");
					spinner.hide();
				} else if (data && data.tries > 4) {
					result.html(<?php echo wp_json_encode(__('Hmm, this is taking longer than expected. %1$s/%2$s failed attempts so far.', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>.replace('%1$s', data.tries).replace('%2$s', maxAttempts));
					window.setTimeout(initLookForCronSuccess, 2500);
				} else if (data && data.tries) {
					window.setTimeout(initLookForCronSuccess, 2500);
				} else {
					result.html(<?php echo wp_json_encode(__('Something unexpected went wrong.', 'podlove-podcasting-plugin-for-wordpress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> + " " + helpHtml + " <i class=\"podlove-icon-remove\"></i>");
					spinner.hide();
				}
			}
		});
	};

	var initExecWpCron = function() {
		var taskWrapper = $("#diagnosis-item-check-cron-exec");

		taskWrapper.find("i").show();

		$.ajax({
			url: ajaxurl,
			data: {
				action: 'podlove-cron-diag-start',
				nonce: podlove_admin_global.nonce_ajax
			}
		}).always(function() {
			initLookForCronSuccess();
		});
	};

	var initDiagnosis = function() {

		// hide teaser
		$("#podlove-cron-diagnosis-teaser").hide(400);

		// show diagnosis
		$("#podlove-cron-diagnosis-wrapper").show(400);

		// start diagnosis
		initReachWpCron();
		initExecWpCron();
	};

	diagnosisButton.on('click', initDiagnosis)

}(jQuery));
</script>
<style type="text/css">
li span.result { font-style: italic; }
#podlove-cron-diagnosis-teaser { line-height: 28px; }
#podlove-cron-diagnosis-wrapper { display: none; }
</style>
	<?php
    }
}
