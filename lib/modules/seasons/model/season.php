<?php 
namespace Podlove\Modules\Seasons\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Image;
use \Podlove\Model\Episode;

class Season extends Base
{
	use \Podlove\Model\KeepsBlogReferenceTrait;

	public function __construct() {
		$this->set_blog_id();
	}

	public static function for_episode(Episode $episode) {
		return self::by_date(strtotime(get_post($episode->post_id)->post_date));
	}

	public static function by_date($timestamp) {
		
		if (!is_numeric($timestamp))
			throw new InvalidArgumentException("Season::by_date expects a timestamp as parameter");

		$seasons = Season::all();
		$seasons = array_filter($seasons, function($season) use ($timestamp) {
			$start = strtotime(get_post($season->first_episode()->post_id)->post_date);
			$end   = strtotime(get_post($season->last_episode()->post_id)->post_date);
			return $start <= $timestamp && ($end >= $timestamp || $season->is_running());
		});

		if (count($seasons) > 0) {
			return reset($seasons);
		} else {
			return null;
		}
	}

	public function title() {
		if ($this->title) {
			return $this->title;
		} else {
			return __('Season', 'podlove') . ' ' . $this->number();
		}
	}

	public function image() {
		return new Image($this->image, $this->title);
	}

	/**
	 * First day of the season.
	 * 
	 * Season 1 may have an empty start_date.
	 * 
	 * @param  string $format date format, defaults to WordPress setting
	 * @return string|null
	 */
	public function start_date($format = null) {
		
		if (is_null($format))
			$format = get_option('date_format');

		if ($time = strtotime($this->start_date)) {
			return date($format, $time);
		} else {
			return null;
		}
	}

	public function next_season() {
		if (!$this->start_date) {
			return Season::find_one_by_where('start_date IS NOT NULL ORDER BY start_date ASC');
		} else {
			return Season::find_one_by_where('start_date > \'' . $this->start_date('Y-m-d') . '\' ORDER BY start_date ASC');
		}
	}

	public function previous_season() {
		if (!$this->start_date) {
			return null;
		} else {
			return Season::find_one_by_where('
					start_date < \'' . $this->start_date('Y-m-d') . '\' 
					OR start_date IS NULL 
				ORDER BY
					start_date DESC'
			);
		}
	}

	/**
	 * Is this season currently running?
	 * 
	 * @return boolean
	 */
	public function is_running() {
		return is_null($this->next_season());
	}

	public function first_episode() {
		global $wpdb;

		$previous_season = $this->previous_season();

		if ($previous_season) {
			$date_condition = "AND DATE(p.post_date) > '" . $previous_season->end_date('Y-m-d') . "'";
		} else {
			$date_condition = "";
		}

		$sql = "SELECT
				e.*
			FROM
				`" . Episode::table_name() . "` e
				JOIN `" . $wpdb->posts . "` p ON e.post_id = p.ID
			WHERE
				p.post_type = 'podcast' AND
				p.post_status = 'publish' 
				$date_condition
			ORDER BY
				p.post_date ASC
			LIMIT 0,1
		";

		return Episode::find_one_by_sql($sql);
	}

	public function last_episode() {
		global $wpdb;

		$next_season = $this->next_season();

		if ($next_season) {
			$date_condition = "AND DATE(p.post_date) < '" . $next_season->start_date('Y-m-d') . "'";
		} else {
			$date_condition = "";
		}

		$sql = "SELECT
				e.*
			FROM
				`" . Episode::table_name() . "` e
				JOIN `" . $wpdb->posts . "` p ON e.post_id = p.ID
			WHERE
				p.post_type = 'podcast' AND
				p.post_status = 'publish' 
				$date_condition
			ORDER BY
				p.post_date DESC
			LIMIT 0,1
		";

		return Episode::find_one_by_sql($sql);
	}

	/**
	 * Last day of the season.
	 * 
	 * The current season has no end_date.
	 * Otherwise the end date equals the publication date of the last episode
	 * in that season.
	 * 
	 * @param  string $format date format, defaults to WordPress setting
	 * @return string|null
	 */
	public function end_date($format = null) {
		global $wpdb;
		
		if ($this->is_running())
			return null;

		if (is_null($format))
			$format = get_option('date_format');

		$episode = $this->last_episode();

		if (is_null($episode))
			return null;

		return get_the_date($format, $episode->post_id);
	}

	public function episodes() {
		global $wpdb;

		$prev = $this->previous_season();
		$next = $this->next_season();

		if (is_null($prev) && is_null($next)) { // first and only season
			$date_range = '1 = 1';
		} elseif (is_null($prev)) { // first, completed season
			$date_range = "DATE(p.post_date) < '" . $next->start_date('Y-m-d') . "'";
		} elseif (is_null($next)) { // current running season
			$date_range = "DATE(p.post_date) > '" . $prev->end_date('Y-m-d') . "'";
		} else { // anything inbetween
			$date_range = "DATE(p.post_date) >= '" . $this->start_date('Y-m-d') . "' AND DATE(p.post_date) <= '" . $this->end_date('Y-m-d') . "'";
		}

		$sql = "SELECT
				e.*
			FROM
				`" . Episode::table_name() . "` e
				JOIN `" . $wpdb->posts . "` p ON e.post_id = p.ID
			WHERE
				p.post_type = 'podcast' AND
				p.post_status = 'publish' AND
				$date_range
			ORDER BY
				p.post_date ASC
		";

		return Episode::find_all_by_sql($sql);
	}

	/**
	 * Season number.
	 * 
	 * Automatically determined season number.
	 * 
	 * One season may have no start date and is assumed to be the first season.
	 * 
	 * @return int
	 */
	public function number() {
		$seasons = Season::find_all_by_where("start_date < '" . $this->start_date. "' OR start_date IS NULL AND id != " . $this->id);
		return count($seasons) + 1;
	}
}

Season::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Season::property('title', 'VARCHAR(255)');
Season::property('subtitle', 'TEXT');
Season::property('summary', 'TEXT');
Season::property('image', 'TEXT');
Season::property('start_date', 'DATE');
