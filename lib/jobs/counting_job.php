<?php 
namespace Podlove\Jobs;

class CountingJob {
	use JobTrait;

	public function __construct($args = []) {
		// maybe separate func so that argument parsing can be done in trait?
		$defaults = [
			'from'     => 0,
			'to'       => 100,
			'stepsize' => 1
		];

		$this->args = wp_parse_args($args, $defaults);

		$this->state = $this->args['from'];
	}

	public function get_total_steps() {
		return $this->args['to'] - $this->args['from'];
	}

	protected function do_step() {
		$this->state += $this->args['stepsize'];
	}
}
