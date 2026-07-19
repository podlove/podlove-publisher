<?php

use Podlove\Modules\Seasons\Model\Season;
use Podlove\Modules\Seasons\Model\SeasonsValidator;

/**
 * @internal
 *
 * @coversNothing
 */
class SeasonsValidatorTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        podlove_test_activate_module('seasons', \Podlove\Modules\Seasons\Seasons::class);
    }

    public function tearDown(): void
    {
        podlove_test_truncate_seasons_table();
        parent::tearDown();
    }

    public function testDetectsMultipleFirstSeasons()
    {
        $this->createSeason();
        $this->createSeason();

        $validator = new SeasonsValidator();
        $validator->validate();
        $issues = $validator->issues();

        $this->assertEquals(1, count($issues));

        $issue = $issues[0];

        $this->assertEquals('multiple_first_seasons', $issue->type);
        $this->assertEquals('Only one season can have an empty start date.', $issue->message());
    }

    public function testDetectsDuplicateStartDates()
    {
        $this->createSeason('2011-01-01');
        $this->createSeason('2011-01-01');

        $validator = new SeasonsValidator();
        $validator->validate();
        $issues = $validator->issues();

        $this->assertEquals(1, count($issues));

        $issue = $issues[0];

        $this->assertEquals('duplicate_start_dates', $issue->type);
        $this->assertEquals('Some of your seasons have the same start date.', $issue->message());
    }

    private function createSeason($start_date = null)
    {
        $season = new Season();
        if ($start_date !== null) {
            $season->start_date = $start_date;
        }
        $season->save();

        return $season;
    }
}
