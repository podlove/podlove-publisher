<?php 
namespace Podlove\Jobs;

use Podlove\Model\Job;

/**
 * After restructuring this class should no longer exist.
 */
class Jobs {

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
