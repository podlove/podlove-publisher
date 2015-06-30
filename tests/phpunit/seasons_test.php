<?php

use \Podlove\Modules\Seasons\Seasons;
use \Podlove\Modules\Seasons\Model\Season;

class SeasonsTest extends WP_UnitTestCase {

	/**
	 * @before
	 */
	function activateSeasonsModule() {
		\Podlove\Modules\Base::activate('seasons');
	}

	public function testSeasonsAreNumberedCorrectly() {

		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2012-01-01']);
		$season3 = Season::create(['start_date' => '2013-01-01']);

		$this->assertTrue($season1->number() === 1);
		$this->assertTrue($season2->number() === 2);
		$this->assertTrue($season3->number() === 3);
	}

	public function testPreviousSeason() {

		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2012-01-01']);

		$this->assertTrue($season2->previous_season()->id == $season1->id);		
		$this->assertTrue($season1->previous_season() == null);		
	}

	public function testNextSeason() {

		$season0 = Season::create();
		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2012-01-01']);

		$this->assertTrue($season0->next_season()->id == $season1->id);		
		$this->assertTrue($season1->next_season()->id == $season2->id);		
		$this->assertTrue($season2->next_season() == null);		
	}
}