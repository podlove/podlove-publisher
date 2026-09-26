<?php

/**
 * @internal
 *
 * @coversNothing
 */
class ModuleUninstallTest extends WP_UnitTestCase
{
    protected function tearDown(): void
    {
        \Podlove\Modules\Social\Model\Service::destroy();
        \Podlove\Modules\Social\Model\ShowService::destroy();
        \Podlove\Modules\Social\Model\ContributorService::destroy();
        \Podlove\Modules\Shownotes\Model\Entry::destroy();
        \Podlove\Modules\Seasons\Model\Season::destroy();
        \Podlove\Modules\AnalyticsHeartbeat\Model\Heartbeat::destroy();

        parent::tearDown();
    }

    public function testSocialUninstallRemovesAllModuleTables()
    {
        \Podlove\Modules\Social\Model\Service::build();
        \Podlove\Modules\Social\Model\ShowService::build();
        \Podlove\Modules\Social\Model\ContributorService::build();

        $this->assertTrue(\Podlove\Modules\Social\Model\Service::table_exists());
        $this->assertTrue(\Podlove\Modules\Social\Model\ShowService::table_exists());
        $this->assertTrue(\Podlove\Modules\Social\Model\ContributorService::table_exists());

        \Podlove\Modules\Social\Social::instance()->uninstall();

        $this->assertFalse(\Podlove\Modules\Social\Model\Service::table_exists());
        $this->assertFalse(\Podlove\Modules\Social\Model\ShowService::table_exists());
        $this->assertFalse(\Podlove\Modules\Social\Model\ContributorService::table_exists());
    }

    public function testShownotesUninstallRemovesEntryTable()
    {
        \Podlove\Modules\Shownotes\Model\Entry::build();

        $this->assertTrue(\Podlove\Modules\Shownotes\Model\Entry::table_exists());

        \Podlove\Modules\Shownotes\Shownotes::instance()->uninstall();

        $this->assertFalse(\Podlove\Modules\Shownotes\Model\Entry::table_exists());
    }

    public function testSeasonsUninstallRemovesSeasonTable()
    {
        \Podlove\Modules\Seasons\Model\Season::build();

        $this->assertTrue(\Podlove\Modules\Seasons\Model\Season::table_exists());

        \Podlove\Modules\Seasons\Seasons::instance()->uninstall();

        $this->assertFalse(\Podlove\Modules\Seasons\Model\Season::table_exists());
    }

    public function testAnalyticsHeartbeatUninstallRemovesHeartbeatTable()
    {
        \Podlove\Modules\AnalyticsHeartbeat\Model\Heartbeat::build();

        $this->assertTrue(\Podlove\Modules\AnalyticsHeartbeat\Model\Heartbeat::table_exists());

        \Podlove\Modules\AnalyticsHeartbeat\Analytics_Heartbeat::instance()->uninstall();

        $this->assertFalse(\Podlove\Modules\AnalyticsHeartbeat\Model\Heartbeat::table_exists());
    }
}
