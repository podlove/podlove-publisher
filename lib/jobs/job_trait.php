<?php 
namespace Podlove\Jobs;

use Podlove\Model\Job;

trait JobTrait {

	protected $hooks = [];

	/**
	 * @var Podlove\Model\Job
	 */
	protected $job;

	public function __construct($args = [], $job = NULL)
	{
		$this->job = is_null($job) ? new Job : $job;
		$this->job->args = wp_parse_args($args, self::defaults());
		$this->setup();
	}

	/**
	 * Human readable title
	 * @return string
	 */
	public static function title() { return ''; }

	/**
	 * Human readable description of what the job does
	 * @return string
	 */
	public static function description() { return ''; }

	/**
	 * Called once on class construction.
	 * 
	 * Does nothing by default. Override for custom setup behaviour.
	 */
	public function setup() {}

	/**
	 * Return default job arguments
	 * 
	 * @return array
	 */
	public static function defaults() { return []; }

	/**
	 * If a job is unique, only one can be active at any point in time.
	 * 
	 * @todo  needs to be checked by job runner
	 * @return boolean
	 */
	public static function is_unique() {
		return true;
	}

	/**
	 * Initialize job
	 * 
	 * - find out and persist how many steps there are
	 * 
	 * @trait
	 */
	public function init() {

		if (isset($this->hooks['init'])) {
			call_user_func($this->hooks['init']);
		}
		
		$this->job->steps_total = $this->get_total_steps();

		if (!$this->job->steps_progress) {
			$this->job->steps_progress = 0;
		}

		if (!$this->job->active_run_time) {
			$this->job->active_run_time = 0;
		}

		$this->job->wakeups = $this->job->wakeups ? $this->job->wakeups : 0;
		$this->job->sleeps  = $this->job->sleeps ? $this->job->sleeps   : 0;

		$this->save_job();

		return $this;
	}

	public function get_job_id() {
		return $this->job->id;
	}

	public function is_finished() {
		return $this->job->is_finished();
	}

	public function save_job() {

		$current_time = current_time('mysql', true);

		if ($this->job->is_new()) {
			$this->job->class = str_replace('\\', '\\\\', get_called_class());
			$this->job->created_at = $current_time;
		}

		$this->job->updated_at = $current_time;

		$this->job->save();
	}

	private function log_active_run_time($time_ms) {
		$this->job->active_run_time += $time_ms;
	}

	public function get_job() {
		return $this->job;
	}

	protected function get_status_percent() {
		if (!$this->status['total'])
			return null;

		return round($this->status['progress'] / $this->status['total'] * 100, 2);
	}

	protected function get_status_text() {
		if ($this->status['progress'] === 0) {
			return 'not_started';
		} elseif (!$this->is_finished()) {
			return 'running';
		} else {
			return 'done';
		}
	}

	/**
	 * How many steps does it take to complete the job?
	 * 
	 * @return int
	 */
	abstract public function get_total_steps();
	
	/**
	 * Implement one step of the job
	 * 
	 * @return  int How much progress did the step make?
	 */
	abstract protected function do_step();

	/**
	 * Do one step, and record the progress.
	 */
	public function step()
	{
		$start = microtime(true);
		$progress = $this->do_step();
		$end = microtime(true);
		$this->log_active_run_time($end - $start);

		$this->job->steps_progress += ($progress > 0) ? $progress : 1;
		$this->save_job();

		if ($this->is_finished() && isset($this->hooks['finished'])) {
			call_user_func($this->hooks['finished']);
		}
	}

	/**
	 * Finish the job
	 */
	public function run() {
		while (!$this->is_finished()) {
			$this->step();
		}
	}
}
