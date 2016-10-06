<?php 
namespace Podlove\Jobs;

use Podlove\Model\Job;

/**
 * After restructuring this class should no longer exist.
 */
class Jobs {

	public static function clean() {
		$jobs = get_option('podlove_jobs', []);

		$clean_jobs = array_reduce(array_keys($jobs), function($agg, $job_id) use ($jobs) {

			// remove jobs with faulty total calculation
			if (!$jobs[$job_id]['status']['total'])
				return $agg;

			// remove old jobs
			if (time() - $jobs[$job_id]['updated_at'] > DAY_IN_SECONDS * 14)
				return $agg;

			$agg[$job_id]  = $jobs[$job_id];

			return $agg;
		}, []);

		update_option('podlove_jobs', $clean_jobs);
	}

	public static function getMostRecentIdForJobClass($job_class) {

		$job_class_name = explode('\\', $job_class);
		$job_class_name = end($job_class_name);

		$sql = '
			SELECT 
				*
			FROM
				' . Job::table_name() . ' j
			WHERE `class` LIKE "%' . $job_class_name . '"
			ORDER BY `updated_at` DESC
			LIMIT 0,1
		';

		$job = Job::find_one_by_sql($sql);

		if ($job) {
			return $job->id;
		} else {
			return NULL;
		}
	}
}
