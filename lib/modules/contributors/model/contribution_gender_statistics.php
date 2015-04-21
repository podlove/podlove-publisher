<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Episode;

/**
 * Gender Statistics for Episode Contributions
 * 
 * Usage:
 * 
 *   $stats = (new ContributionGenderStatistics)->get();
 */
class ContributionGenderStatistics {

	public function __construct() {
		global $wpdb;

		$sql = "
		SELECT
			ec.id, ec.role_id, ec.group_id, 
			(case when c.gender = 'male' then 1 else 0 end)                  AS male,
			(case when c.gender = 'female' then 1 else 0 end)                AS female,
			(case when c.gender NOT IN ('male', 'female') then 1 else 0 end) AS notattributed
		FROM
			`" . EpisodeContribution::table_name() . "` ec
			JOIN `" . Episode::table_name() . "` e     ON e.id = ec.episode_id
			JOIN `" . $wpdb->posts . "` p              ON p.ID = e.`post_id` AND p.post_status IN ('publish', 'private')
			JOIN `" . Contributor::table_name() . "` c ON ec.`contributor_id` = c.id
		";

		$this->contributions = $wpdb->get_results($sql);
	}

	public function get() {
		$genders = [ 'global' => self::count_contributions($this->contributions) ];

		$genders['by_role'] = [];
		foreach ($this->role_ids() as $role_id) {
			$genders['by_role'][$role_id] = self::count_contributions($this->filter_by('role_id', $role_id));
		}

		$genders['by_group'] = [];
		foreach ($this->group_ids() as $group_id) {
			$genders['by_group'][$group_id] = self::count_contributions($this->filter_by('group_id', $group_id));
		}

		return $genders;
	}

	private function filter_by($filter_key, $filter_value) {
		return array_filter($this->contributions, function($c) use ($filter_key, $filter_value) {
			return $c->$filter_key == $filter_value;
		});
	}

	private function role_ids() {
		return array_reduce($this->contributions, function($agg, $c) {

			if (!in_array($c->role_id, $agg))
				$agg[] = $c->role_id;

			return $agg;
		}, []);
	}

	private function group_ids() {
		return array_reduce($this->contributions, function($agg, $c) {

			if (!in_array($c->group_id, $agg))
				$agg[] = $c->group_id;

			return $agg;
		}, []);
	}

	private static function count_contributions($contributions) {
		return [
			'by_gender' => [
				'male'   => array_reduce($contributions, function($agg, $c) { return $agg + $c->male;          }, 0),
				'female' => array_reduce($contributions, function($agg, $c) { return $agg + $c->female;        }, 0),
				'none'   => array_reduce($contributions, function($agg, $c) { return $agg + $c->notattributed; }, 0),
			],
			'total' => count($contributions)
		];
	}
}
