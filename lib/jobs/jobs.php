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

	public static function getMostRecentIdForJobClass($job_class) {
		$job_class = trim($job_class, "\\");

		$jobs = Jobs::all();

		// filter by job class
		$jobs = array_filter($jobs, function($job) use ($job_class) {
			return trim($job['class'], "\\") == $job_class;
		});

		// get max
		$job_id = array_reduce(array_keys($jobs), function($max_key, $cur_key) use ($jobs) {

			if (is_null($max_key))
				return $cur_key;

			$max = $jobs[$max_key];
			$cur = $jobs[$cur_key];

			if ($cur['updated_at'] > $max['updated_at']) {
				return $cur_key;
			} else {
				return $max_key;
			}

		}, null);

		return $job_id;
	}

	public static function save($id, $job) {
		$jobs = Jobs::all();
		$jobs[$id] = $job;
		update_option('podlove_jobs', $jobs);
	}
}
