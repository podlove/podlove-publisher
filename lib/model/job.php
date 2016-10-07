<?php 
namespace Podlove\Model;

class Job extends Base {
	
	use KeepsBlogReferenceTrait;	

	public function __construct() {
		$this->set_blog_id();
	}

	public function is_finished() {
		return $this->steps_progress >= $this->steps_total;
	}

	public static function find_one_recent_job($job_class) {

		// get class name without namespace
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

		return Job::find_one_by_sql($sql);
	}

	public static function find_one_recent_finished_job($job_class) {

		// get class name without namespace
		$job_class_name = explode('\\', $job_class);
		$job_class_name = end($job_class_name);

		$sql = '
			SELECT 
				*
			FROM
				' . Job::table_name() . ' j
			WHERE `class` LIKE "%' . $job_class_name . '" AND steps_total <= steps_progress
			ORDER BY `updated_at` DESC
			LIMIT 0,1
		';

		return Job::find_one_by_sql($sql);
	}

	public static function find_next_in_queue()
	{
		$sql = '
			SELECT
				*
			FROM
				' . self::table_name() .  '
			WHERE
				steps_total > steps_progress
			ORDER BY created_at ASC
			LIMIT 0, 1
		';

		return self::find_one_by_sql($sql);
	}

	public static function find_recently_finished_jobs($limit = 10) {
		$sql = '
			SELECT
				*
			FROM
				' . Job::table_name() .  '
			WHERE
				steps_total <= steps_progress
			ORDER BY created_at ASC
			LIMIT 0, ' . (int) $limit . '
		';

		return Job::find_all_by_sql($sql);
	}

	public static function find_running_jobs() {
		$sql = '
			SELECT
				*
			FROM
				' . Job::table_name() .  '
			WHERE
				steps_total > steps_progress
			ORDER BY created_at ASC
		';

		return Job::find_all_by_sql($sql);
	}

	public static function load($id)
	{
		$job = self::find_by_id($id);

		if (!$job) {
			return NULL;
		}

		$job->args  = maybe_unserialize($job->args);
		$job->state = maybe_unserialize($job->state);

		$classname = $job->class;
		$class = new $classname($job->args, $job);

		return $class;
	}

	public static function clean() {
	    global $wpdb;

	    $sql = '
			DELETE FROM
				' . self::table_name() . '
			WHERE
				created_at < "' . date('Y-m-d H:i:s', strtotime('-2 weeks')) . '"
	    ';

	    $wpdb->query($sql);  
	}

	/**
	 * Dedicated method to update state object.
	 * 
	 * Necessary because of "Indirect modification of overloaded property
	 * has no effect" warning if state is updated normally via
	 * $this->state['arg'] = 'newvalue';
	 * @see  http://stackoverflow.com/a/19749730/72448.
	 * 
	 * @param  string $attribute
	 * @param  mixed $value
	 */
	public function update_state($attribute, $value)
	{
		$state = $this->state;
		$state[$attribute] = $value;
		$this->state = $state;
	}
}

Job::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Job::property('class', 'VARCHAR(255)');
Job::property('args', 'LONGTEXT');
Job::property('steps_total', 'INT');
Job::property('steps_progress', 'INT');
Job::property('active_run_time', 'FLOAT');
Job::property('state', 'LONGTEXT');
Job::property('created_at', 'DATETIME');
Job::property('updated_at', 'DATETIME');
