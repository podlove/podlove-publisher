<?php 
namespace Podlove\Jobs;

trait JobTrait {

	private $args;
	private $status;
	private $id;

	public $created_at;
	public $updated_at;

	/**
	 * If there is any state that has to be persisted between steps, 
	 * it can be stored here.
	 * 
	 * @var mixed
	 */
	protected $state;

	/**
	 * Initialize job
	 * 
	 * - find out and persist how many steps there are
	 * 
	 * @trait
	 */
	public function init() {
		$this->status = [
			'total' => $this->get_total_steps(),
			'progress' => 0
		];

		$this->generate_job_id();
		$this->save_status();

		return $this;
	}

	public static function load($args) {
		$classname = get_called_class();

		$class = new $classname();
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
		}

		$job = array_merge($job, [
			'status'     => $this->status,
			'state'      => $this->state,
			'updated_at' => time()
		]);

		Jobs::save($this->id, $job);
	}

	public function get_status() {
		return [
			'total' => $this->status['total'],
			'progress' => $this->status['progress'],
			'percent' => $this->get_status_percent(),
			'text' => $this->get_status_text(),
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
	 */
	abstract protected function do_step();

	/**
	 * Do one step, and record the progress.
	 */
	public function step()
	{
		$this->do_step();
		$this->status['progress']++;
		$this->save_status();
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
