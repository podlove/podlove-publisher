<?php
namespace Podlove\Modules\Seasons\Model;

class SeasonsValidator {

	private $issues;

	function __construct() {
		$this->issues = [];
	}

	public function validate() {
		$this->checkForMultipleFirstSeasons();
		$this->checkForDuplicateStartDates();
	}

	public function issues() {
		return $this->issues;
	}

	private function checkForMultipleFirstSeasons() {
		$seasons = Season::find_all_by_where("start_date IS NULL");

		if (count($seasons) > 1) {
			$issue = new SeasonsIssue;
			$issue->type = 'multiple_first_seasons';
			$this->issues[] = $issue;
		}
	}

	public function checkForDuplicateStartDates() {
		global $wpdb;

		$sql = "
			SELECT
			  COUNT(*) cnt
			FROM
			  `" . Season::table_name() . "` s
			WHERE
			  start_date IS NOT NULL
			GROUP BY
			  start_date
			HAVING
			  cnt > 1
		";
		
		if ($wpdb->get_var($sql)) {
			$issue = new SeasonsIssue;
			$issue->type = 'duplicate_start_dates';
			$this->issues[] = $issue;
		}
	}
}
