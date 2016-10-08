<?php

use \Podlove\Jobs\CountingJob;
// use \Podlove\Modules\Seasons\Model\Season;


class JobsTest extends WP_UnitTestCase {

	/**
	 * @before
	 */
	function prepareTests() {
		// \Podlove\Modules\Base::activate('seasons');
		// $this->episode_factory = new EpisodeFactory($this->factory);
	}

	public function testTruth() {
		$this->assertEquals(true, true);
	}

	// public function testNewJobIsNotStarted() {
	// 	$job = (new CountingJob(['from' => 1, 'to' => 5]))->init();
	// 	$this->assertEquals($job->get_status()['text'], 'not_started');
	// 	$this->assertEquals($job->get_status()['total'], 4);
	// 	$this->assertEquals($job->get_status()['progress'], 0);
	// 	$this->assertEquals($job->get_status()['percent'], 0);
	// }

	// public function testNewJobHasId() {
	// 	$job = (new CountingJob([]))->init();
	// 	$this->assertNotEmpty($job->get_job_id());
	// }

	// public function testFirstStep() {
	// 	$job = (new CountingJob(['from' => 1, 'to' => 5]))->init();
	// 	$this->assertEquals(1, $job->get_state());

	// 	$job->step();
	// 	$this->assertEquals(2, $job->get_state());

	// 	$job->step();
	// 	$this->assertEquals(3, $job->get_state());
	// }

	// public function testRunningUntilFinished() {
	// 	$job = (new CountingJob(['from' => 1, 'to' => 5]))->init();
	// 	$this->assertEquals(1, $job->get_state());

	// 	$job->run();

	// 	$this->assertEquals(5, $job->get_state());
	// 	$this->assertEquals($job->get_status()['text'], 'done');
	// 	$this->assertEquals($job->get_status()['progress'], 4);
	// 	$this->assertEquals($job->get_status()['percent'], 100);
	// }

	// public function testInitializedJobIsSaved() {
	// 	$this->assertEquals(0, Job::count());
	// 	$job = (new CountingJob(['from' => 1, 'to' => 5]))->init();
	// 	$this->assertEquals(1, Job::count());
	// 	$this->assertNotNull(Jobs::get($job->get_job_id()));
	// }

	// public function testStepIsSaved() {
	// 	$job = (new CountingJob(['from' => 1, 'to' => 5]))->init();
	// 	$job->step();

	// 	$savedJob = Jobs::get($job->get_job_id());
	// 	$this->assertEquals(2, $savedJob['state']);
	// }

	// public function testJobCanBeRestored() {
	// 	$job = (new CountingJob(['from' => 1, 'to' => 5]))->init();
	// 	$savedJob = Jobs::load($job->get_job_id());
		
	// 	$this->assertEquals($savedJob->get_job_id(), $job->get_job_id());
	// 	$this->assertEquals($savedJob->get_state(),  $job->get_state());
	// 	$this->assertEquals($savedJob->get_status(), $job->get_status());
	// }

}
