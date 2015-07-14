<?php

use \Podlove\Modules\Seasons\Model\Season;
use \Podlove\Modules\Seasons\Model\SeasonsValidator;

class SeasonsValidatorTest extends WP_UnitTestCase {

	/**
	 * @before
	 */
	function activateSeasonsModule() {
		\Podlove\Modules\Base::activate('seasons');
	}

	public function testDetectsMultipleFirstSeasons() {
		
		Season::create();
		Season::create();

		$validator = new SeasonsValidator;
		$validator->validate();
		$issues = $validator->issues();

		$this->assertEquals(1, count($issues));

		$issue = $issues[0];

		$this->assertEquals('multiple_first_seasons', $issue->type);
		$this->assertEquals('Only one season can have an empty start date.', $issue->message());
	}

	public function testDetectsDuplicateStartDates() {
		
		Season::create(['start_date' => '2011-01-01']);
		Season::create(['start_date' => '2011-01-01']);

		$validator = new SeasonsValidator;
		$validator->validate();
		$issues = $validator->issues();

		$this->assertEquals(1, count($issues));

		$issue = $issues[0];

		$this->assertEquals('duplicate_start_dates', $issue->type);
		$this->assertEquals('Some of your seasons have the same start date.', $issue->message());
	}

}
