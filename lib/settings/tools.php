<?php
namespace Podlove\Settings;

use Podlove\Model\Job;

class Tools {

	use \Podlove\HasPageDocumentationTrait;

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Tools::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Tools',
			/* $menu_title */ 'Tools',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_tools_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		$this->init_page_documentation(self::$pagehook);

		add_action( 'admin_init', array( $this, 'process_actions' ) );

		add_action('admin_print_styles', function() {
			wp_enqueue_script('podlove_admin_jobs');
		}, 20);

		\Podlove\add_tools_section('general-maintenance', __('General Maintenance', 'podlove-podcasting-plugin-for-wordpress'));
		\Podlove\add_tools_section('tracking-analytics', __('Tracking & Analytics', 'podlove-podcasting-plugin-for-wordpress'));
	
		/**
		 * Fields for section "General Maintenance"
		 */
		\Podlove\add_tools_field('gm-clear-caches', __('Clear Caches', 'podlove-podcasting-plugin-for-wordpress'), function() {
			?>
			<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=clear_caches') ?>" class="button">
				<?php echo __('Clear Caches', 'podlove-podcasting-plugin-for-wordpress') ?>
			</a>
			<p class="description">
				<?php echo __('Sometimes an issue is already fixed but you still see the faulty output. Clearing the cache avoids this. However, if you use a third party caching plugin, you should clear that cache, too.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>
			<?php
		}, 'general-maintenance');

		\Podlove\add_tools_field('gm-repair', __('Repair', 'podlove-podcasting-plugin-for-wordpress'), function() {
			\Podlove\Repair::page();
		}, 'general-maintenance');


		/**
		 * Fields for section "Tracking & Analytics"
		 */
		$job_class = 'Podlove\Jobs\UserAgentRefreshJob';
		\Podlove\add_tools_field('ta-recals-agents', $job_class::title(), function() use ($job_class) {
				$recent_job = Job::find_one_recent_job($job_class);
				$recent_job_id = $recent_job ? $recent_job->id : '';
			?>

			<div 
				class="podlove-job" 
				data-job="Podlove-Jobs-UserAgentRefreshJob" 
				data-button-text="<?php echo $job_class::title() ?>"
				data-recent-job-id="<?php echo $recent_job_id ?>"
				>
				
			</div>

			<div class="clear"></div>

			<p class="description">
				<?php echo __('Runs automatically on plugin updates. Update user agent metadata based on <code>device-detector</code> library.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>
			<?php
		}, 'tracking-analytics');

		$job_class = 'Podlove\Jobs\DownloadIntentCleanupJob';
		\Podlove\add_tools_field('ta-recalc-analytics', $job_class::title(), function() use ($job_class) {
				$recent_job = Job::find_one_recent_job($job_class);
				$recent_job_id = $recent_job ? $recent_job->id : '';
			?>

			<div 
				class="podlove-job" 
				data-job="Podlove-Jobs-DownloadIntentCleanupJob" 
				data-button-text="<?php echo $job_class::title() ?>"
				data-recent-job-id="<?php echo $recent_job_id ?>"
				>
				
			</div>

			<div class="clear"></div>

			<p class="description">
				<?php echo __('Runs automatically once per hour. Recalculates contents of <code>podlove_download_intent_clean</code> table based on <code>podlove_download_intent</code> table. Clears cache. This is useful if you don\'t get updated analytics or you played with data in <code>podlove_download_intent_clean</code> and messed up.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>
			<?php
		}, 'tracking-analytics');

		$job_class = 'Podlove\Jobs\DownloadTimedAggregatorJob';
		\Podlove\add_tools_field('ta-recalc-downloads-table', $job_class::title(), function() use ($job_class) {
				$recent_job = Job::find_one_recent_job($job_class);
				$recent_job_id = $recent_job ? $recent_job->id : '';
			?>
			<div 
				class="podlove-job" 
				data-job="Podlove-Jobs-DownloadTimedAggregatorJob"
				data-args="<?php echo esc_attr(json_encode(['force' => true])); ?>" 
				data-button-text="<?php echo $job_class::title() ?>"
				data-recent-job-id="<?php echo $recent_job_id ?>"
				>
				
			</div>

			<p class="description">
				<?php echo __('Runs automatically twice per day. Calculates downloads per episode in time segments (first day, first two days, ... first year) for the Analytics Dashboard.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>
			<?php
		}, 'tracking-analytics');

		$job_class = 'Podlove\Jobs\DownloadTotalsAggregatorJob';
		\Podlove\add_tools_field('ta-recalc-downloads-totals', $job_class::title(), function() use ($job_class) {
				$recent_job = Job::find_one_recent_job($job_class);
				$recent_job_id = $recent_job ? $recent_job->id : '';
			?>
			<div 
				class="podlove-job" 
				data-job="Podlove-Jobs-DownloadTotalsAggregatorJob"
				data-args="<?php echo esc_attr(json_encode(['force' => true])); ?>" 
				data-button-text="<?php echo $job_class::title() ?>"
				data-recent-job-id="<?php echo $recent_job_id ?>"
				>
				
			</div>

			<p class="description">
				<?php echo __('Runs automatically once per hour. Calculates total downloads per episode.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>
			<?php
		}, 'tracking-analytics');
	}

	function process_actions() {

		if (filter_input(INPUT_GET, 'page') != 'podlove_tools_settings_handle')
			return;

		switch (filter_input(INPUT_GET, 'action')) {
			case 'clear_caches':
				\Podlove\Repair::clear_podlove_cache();
				\Podlove\Repair::clear_podlove_image_cache();
				wp_redirect(admin_url('admin.php?page=' . $_REQUEST['page']));
				break;
			default:
				# code...
				break;
		}

	}

	public function page() {

		wp_enqueue_script('podlove-tools-useragent', \Podlove\PLUGIN_URL . '/js/admin/tools/useragent.js', ['jquery'], \Podlove\get_plugin_header('Version'));

		wp_enqueue_script('jquery-ui-progressbar');

		?>

  <style>
  .ui-progressbar {
    position: relative;
    margin-left: 225px;
  }
  .progress-label {
    position: absolute;
    left: 50%;
    top: 4px;
    font-weight: bold;
    text-shadow: 1px 1px 0 #fff;
  }

  .progressbar-button {
  	float: left;
  }

.ui-progressbar {
	height: 2em;
	text-align: left;
	overflow: hidden;
}
/*.ui-progressbar .ui-progressbar-value {
	margin: -1px;
	height: 100%;
}*/
.ui-progressbar .ui-progressbar-overlay {
	background: url("data:image/gif;base64,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");
	height: 100%;
	filter: alpha(opacity=25); /* support: IE8 */
	opacity: 0.25;
}
.ui-progressbar-indeterminate .ui-progressbar-value {
	background-image: none;
}

.podlove-recent-job-info {
    display: inline-block;
    line-height: 28px;
    padding-left: 8px;
    color: #666;
}

  </style>

		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Tools', 'podlove-podcasting-plugin-for-wordpress' ); ?></h2>

			<?php 
			$sections = \Podlove\get_tools_sections();
			$fields   = \Podlove\get_tools_fields();
			?>

			<?php foreach ($sections as $section_id => $section): ?>
				<div class="card" style="max-width: 100%">

					<h3><?php echo $section['title'] ?></h3>

					<?php
					if (is_callable($section['callback'])) {
						call_user_func($section['callback']);
					}
					?>

					<table class="form-table">
						<tbody>
						<?php if (isset($fields[$section_id]) && is_array($fields[$section_id])): ?>
						<?php foreach ($fields[$section_id] as $field_id => $field): ?>
							<tr>
								<th>
									<?php echo $field['title'] ?>
								</th>
								<td>
									<?php
									if (is_callable($field['callback'])) {
										call_user_func($field['callback']);
									}
									?>
								</td>
							</tr>
						<?php endforeach ?>
						<?php endif ?>
						</tbody>
					</table>
				</div>
			<?php endforeach ?>

		</div>	
		<?php
	}

}
