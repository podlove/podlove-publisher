<?php 
namespace Podlove\Jobs;

class CountingJob {
	use JobTrait;

	public function setup() {
		$this->state = $this->args['from'];
	}

	public static function defaults() {
		return [
			'from'     => 0,
			'to'       => 100,
			'stepsize' => 1
		];
	}

	public function get_total_steps() {
		return $this->args['to'] - $this->args['from'];
	}

	protected function do_step() {
		$this->state += $this->args['stepsize'];
		// generate CPU intensive task
		// for ($i=0; $i < 7000000; $i++) { 
		// 	pow($i, 42);
		// }
	}
}
