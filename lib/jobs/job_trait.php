<?php 
namespace Podlove\Jobs;

trait JobTrait {

	private $args;
	private $status;
	private $id;

	protected $hooks = [];

	public $created_at;
	public $updated_at;

	/**
	 * If there is any state that has to be persisted between steps, 
	 * it can be stored here.
	 * 
	 * @var mixed
	 */
	protected $state;

	public function __construct($args = [])
	{
		$this->args = wp_parse_args($args, self::defaults());
		$this->setup();
	}

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
	 * Initialize job
	 * 
	 * - find out and persist how many steps there are
	 * 
	 * @trait
	 */
	public function init() {

		if (!is_array($this->status))
			$this->status = [];
		
		$this->generate_job_id();

		if (isset($this->hooks['init'])) {
			call_user_func($this->hooks['init']);
		}
		
		$this->status = array_merge([
			'total' => $this->get_total_steps(),
			'progress' => 0,
			'active_run_time' => 0.0
		], $this->status);

		$this->save_status();

		return $this;
	}

	public static function load($args) {
		$classname = get_called_class();

		$class = new $classname($args['args']);
		$class->id = $args['id'];
		$class->status = $args['status'];
		$class->args = $args['args'];
		$class->state = $args['state'];
		$class->created_at = $args['created_at'];
		$class->updated_at = $args['updated_at'];
		$class->class = $args['class'];

		return $class;
	}

	private function generate_job_id() {
		if (function_exists('openssl_random_pseudo_bytes')) {
			$this->id = bin2hex(openssl_random_pseudo_bytes(7));
		} else {
			$this->id = dechex(mt_rand());
		}
	}

	public function get_job_id() {
		return $this->id;
	}

	public function is_finished() {
		return $this->status['progress'] >= $this->status['total'];
	}

	public function save_status() {

		$job = Jobs::get($this->id);

		if (!isset($job)) {
			$job = [
				'args'       => $this->args,
				'class'      => get_called_class(),
				'created_at' => time(),
			];
			
			$this->created_at = $job['created_at'];
		}

		$job = array_merge($job, [
			'status'     => $this->status,
			'state'      => $this->state,
			'updated_at' => time()
		]);

		$this->updated_at = $job['updated_at'];

		Jobs::save($this->id, $job);
	}

	private function log_active_run_time($time_ms) {
		$this->status['active_run_time'] += $time_ms;
	}

	public function get_status() {
		return [
			'total' => $this->status['total'],
			'progress' => $this->status['progress'],
			'percent' => $this->get_status_percent(),
			'text' => $this->get_status_text(),
			'time' => $this->status['active_run_time'],
			'updated_at' => $this->updated_at,
			'created_at' => $this->created_at
		];
	}

	public function get_state() {
		return $this->state;
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

		$this->status['progress'] += ($progress > 0) ? $progress : 1;
		$this->save_status();

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
