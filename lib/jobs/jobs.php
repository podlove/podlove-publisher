<?php 
namespace Podlove\Jobs;

class Jobs {

	public static function all() {
		return get_option('podlove_jobs', []);
	}

	public static function count() {
		return count(Jobs::all());
	}

	// returns job _data_
	public static function get($id) {
		$jobs = Jobs::all();
		return isset($jobs[$id]) ? $jobs[$id] : NULL;
	}

	public static function load($id) {
		$job = Jobs::get($id);
		$job['id'] = $id;

		return call_user_func_array([$job['class'], 'load'], [$job]);
	}

	public static function save($id, $job) {
		$jobs = Jobs::all();
		$jobs[$id] = $job;
		update_option('podlove_jobs', $jobs);
	}
}
