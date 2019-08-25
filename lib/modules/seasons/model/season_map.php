<?php
namespace Podlove\Modules\Seasons\Model;

use \Podlove\Model\Episode;

/**
 * Maps seasons to episode IDs
 * 
 * Solves "n+1" problem in, for example, feeds.
 * Instead of looking up the season for every item, fetch them all once and keep 
 * the information in memory.
 * 
 * Singleton
 */
class SeasonMap {

	/**
	 * Contains property values.
	 * @var  array
	 */
	private $data = [];

	public static function get() {
    static $instance = null;
    if ($instance === null) {
        $instance = new self();
    }
    return $instance;
	}

	protected function __construct() {
		$this->init();
	}

	final private function __clone() { }

	public function get_season_for_episode_id($episode_id)
	{
		foreach ($this->data as $season_id => $episode_ids) {
			if (in_array($episode_id, $episode_ids)) {
				return Season::find_by_id($season_id);
			}
		}
		return null;
	}
	
	private function init()
	{
		global $wpdb;

		$sql = "select * from " . Season::table_name() . " s order by start_date ASC";
		$seasons = Season::find_all_by_sql($sql);

		$sql = '
			SELECT
				e.id, p.post_date
			FROM
				`' . Episode::table_name() . '` e 
				JOIN `' . $wpdb->posts . '` p ON e.post_id = p.ID
			WHERE
				p.post_type = "podcast"
			ORDER BY
				p.post_date DESC';
		$episodes = $wpdb->get_results($sql);

		$groups = [];
		foreach ($seasons as $season) {
      $id = $season->id;
      $is_running = $season->is_running();
			$groups[$id] = [];

			$first_episode = $season->first_episode();
			$last_episode = $season->last_episode();

			if ($first_episode && $last_episode) {
				$start = strtotime(get_post($first_episode->post_id)->post_date);
				$end   = strtotime(get_post($last_episode->post_id)->post_date);

				foreach ($episodes as $episode) {
						$timestamp = strtotime($episode->post_date);
						if ($start <= $timestamp && ($end >= $timestamp || $is_running)) {
								$groups[$id][] = (int) $episode->id;
						}
				}
			}
		}

		$this->data = $groups;
	}
}
