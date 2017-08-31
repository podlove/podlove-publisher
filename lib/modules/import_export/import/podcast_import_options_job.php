<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Jobs\CronJobRunner;
use Podlove\Model;
use Podlove\Log;

class PodcastImportOptionsJob {
	use JobTrait;
	use PodcastImportJobTrait;

	public static function title()
	{
		return 'Podcast Import: Options';
	}

	public static function description()
	{
		return 'Imports Podcast Options';
	}
	
	public function setup()
	{
		$this->setupXml();
		$this->hooks['init'] = [$this, 'init_job'];
		$this->hooks['finished'] = [$this, 'init_additional_jobs'];
	}

	public function init_job()
	{
		$this->job->state = 0;
	}

	/**
	 * Initialize additional jobs via hook.
	 * 
	 * Jobs registered by modules can only be run after options are imprted
	 * because modules must be active for import hooks to be registered.
	 */
	public function init_additional_jobs()
	{
		$jobs = apply_filters('podlove_import_jobs', []);

		if (is_array($jobs) && count($jobs) > 0) {
			foreach ($jobs as $job) {
				CronJobRunner::create_job($job);
			}
		}
	}

	public function get_total_steps() {
		return count($this->xml->xpath('//wpe:options')[0]->children('wpe', true));
	}

	protected function do_step() {

		$options = (array) $this->xml->xpath('//wpe:options')[0]->children('wpe', true);

		$keys = array_keys($options);
		$key = $keys[$this->job->state];

		$option = $options[$key];

		$option_string = (string) $option;

		// Replace lone '&' characters with '&amp;'.
		// Why? When exporting, the same conversion needs to be done to
		// make strings XML compatible. When importing, it is automatically
		// converted back to '&' which breaks `maybe_unserialize` (because
		// it changes the length of the content). So we need to convert it back.
		if (strpos($option_string, '&') !== false) {
			$option_string = preg_replace('/&([^#])(?![a-z1-4]{1,8};)/i', '&amp;$1', $option_string);
		}

		$skip_options = [
			'podlove_import_file',
			'podlove_repair_log',
			'podlove_cron_diagnosis',
			'podlove_cron_diagnosis_tries',
			'podlove_global_messages'
		];

		if (!in_array($key, $skip_options)) {
			update_option($key, maybe_unserialize($option_string));
		}

		$this->job->state++;

		return 1;
	}

}
