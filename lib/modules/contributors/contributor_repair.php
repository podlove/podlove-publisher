<?php 
namespace Podlove\Modules\Contributors;

use Podlove\Repair;

class ContributorRepair {

	public static function init() {
		add_action('podlove_repair_do_repair', array(__CLASS__, 'fix_duplicate_contributions'));
		add_filter('podlove_repair_descriptions', array(__CLASS__, 'description'));
	}

	public static function description($descriptions) {
		return array_merge($descriptions, array("<strong>removes duplicate contributions</strong> if you have any"));
	}

	public static function fix_duplicate_contributions()
	{
		global $wpdb;

		$contributions = self::find_duplicate_episode_contributions();

		if (!is_array($contributions) || empty($contributions)) {
			Repair::add_to_repair_log(__('Contributions did not need repair', 'podlove'));
			return;
		}

		foreach ($contributions as $contribution) {
			$sql = "
				DELETE FROM
					" . \Podlove\Modules\Contributors\Model\EpisodeContribution::table_name() . "
				WHERE
					id != " . $contribution['id'] . "
					AND `contributor_id` = \"" . $contribution['contributor_id'] . "\"
					AND `episode_id` = \"" . $contribution['episode_id'] . "\"
					AND `role_id` = \"" . $contribution['role_id'] . "\"
					AND `group_id` = \"" . $contribution['group_id'] . "\"
				";
			$wpdb->query($sql);

			$ec = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_by_id($contribution['id']);
			$ec->save(); // recalculates contribution count
		}

		Repair::add_to_repair_log(
			sprintf(
				_n( 'Deleted 1 duplicate contribution', 'Deleted %s duplicate contributions', count($contributions), 'podlove' ),
				count($contributions)
			)
		);
	}

	private static function find_duplicate_episode_contributions() {
		global $wpdb;

		$sql = "
			SELECT
				id, contributor_id, episode_id, role_id, group_id, COUNT(*) cnt
			FROM
				" . \Podlove\Modules\Contributors\Model\EpisodeContribution::table_name() . "
			GROUP BY
				contributor_id, episode_id, role_id, group_id
			HAVING
				cnt > 1
			ORDER BY
				cnt DESC
		";
		return $wpdb->get_results($sql, ARRAY_A);
	}
}