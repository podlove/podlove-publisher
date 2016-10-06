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
		LIMIT 0,1
		';

		return self::find_one_by_sql($sql);
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
