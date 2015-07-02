<?php

use \Podlove\Modules\Seasons\Seasons;
use \Podlove\Modules\Seasons\Model\Season;

class SeasonsTest extends WP_UnitTestCase {

	/**
	 * @before
	 */
	function activateSeasonsModule() {
		\Podlove\Modules\Base::activate('seasons');
		$this->episode_factory = new EpisodeFactory($this->factory);
	}

	public function testSeasonsAreNumberedCorrectly() {

		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2012-01-01']);
		$season3 = Season::create(['start_date' => '2013-01-01']);

		$this->assertEquals($season1->number(), 1);
		$this->assertEquals($season2->number(), 2);
		$this->assertEquals($season3->number(), 3);
	}

	public function testPreviousSeason() {

		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2012-01-01']);

		$this->assertEquals($season2->previous_season()->id, $season1->id);		
		$this->assertNull($season1->previous_season());		
	}

	public function testNextSeason() {

		$season0 = Season::create();
		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2012-01-01']);

		$this->assertEquals($season0->next_season()->id, $season1->id);
		$this->assertEquals($season1->next_season()->id, $season2->id);
		$this->assertNull($season2->next_season());
	}

	/**
	 * Single season contains all episodes.
	 */
	public function testEpisodesForSingleSeason() {
		$season = Season::create();

		$episode1 = $this->episode_factory->create();
		$episode2 = $this->episode_factory->create();

		$this->assertEquals(2, count($season->episodes()));
	}

	public function testEpisodesForFirstSeason() {
		$season0 = Season::create();
		$season1 = Season::create(['start_date' => '2011-01-01']);

		$this->_generate_episodes_for_dates([
			'2010-10-10',
			'2010-10-09',
			'2012-10-09',
		]);

		$this->assertEquals(2, count($season0->episodes()));
	}

	public function testEpisodesForRunningSeason() {
		$season0 = Season::create();
		$season1 = Season::create(['start_date' => '2011-01-01']);

		$this->_generate_episodes_for_dates([
			'2010-10-10',
			'2013-10-09',
			'2012-10-09',
		]);

		$this->assertEquals(2, count($season1->episodes()));
	}

	public function testEpisodesForInbetweenSeason() {
		$season0 = Season::create();
		$season1 = Season::create(['start_date' => '2011-01-01']);
		$season2 = Season::create(['start_date' => '2014-01-01']);

		$this->_generate_episodes_for_dates([
			'2010-10-10',
			'2011-02-02',
			'2011-04-10',
			'2015-10-09',
		]);

		$this->assertEquals(2, count($season1->episodes()));
	}

	public function testCurrentSeasonHasNoEndDate() {
		$season = Season::create();
		$episode = $this->episode_factory->create();

		$this->assertTrue($season->is_running());
		$this->assertNull($season->end_date());
	}

	public function testEndDateOfSeason() {
		$season0 = Season::create();
		$season1 = Season::create(['start_date' => '2011-01-01']);

		$this->_generate_episodes_for_dates(['2010-10-10']);

		$this->assertEquals('2010-10-10', $season0->end_date('Y-m-d'));
	}

	private function _generate_episodes_for_dates(array $dates) {
		foreach ($dates as $date) {
			$this->episode_factory->create([
				'post_id' => $this->factory->post->create(['post_date' => strftime("%Y-%m-%d %H:%M:%S", strtotime($date))])
			]);
		}
	}
}