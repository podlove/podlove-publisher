<?php

use Podlove\Modules\Networks\Model\PodcastList;
use Podlove\Modules\Networks\Settings\PodcastLists;

/**
 * @internal
 *
 * @coversNothing
 */
class NetworkPodcastListTableTest extends WP_UnitTestCase
{
    private $original_base_prefix;

    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $this->original_base_prefix = $wpdb->base_prefix;
        $wpdb->base_prefix = 'plt_'.substr(md5(uniqid('', true)), 0, 8).'_';

        // SHOW TABLES cannot see temporary tables. Use a separate real table
        // for these schema tests without touching an existing network's lists.
        remove_filter('query', [$this, '_create_temporary_tables']);
        remove_filter('query', [$this, '_drop_temporary_tables']);
    }

    protected function tearDown(): void
    {
        global $wpdb;

        try {
            PodcastList::with_network_scope(function () {
                PodcastList::destroy();
            });
        } finally {
            $wpdb->base_prefix = $this->original_base_prefix;
            parent::tearDown();
        }
    }

    public function testEnsureTableRecreatesMissingNetworkTable()
    {
        PodcastList::with_network_scope(function () {
            PodcastList::destroy();
        });

        $this->assertFalse($this->tableExists());

        PodcastLists::ensure_table();

        $this->assertTrue($this->tableExists());
    }

    public function testEnsureTableKeepsExistingLists()
    {
        PodcastLists::ensure_table();

        PodcastList::with_network_scope(function () {
            $list = new PodcastList();
            $list->slug = 'example';
            $list->title = 'Example';
            $list->save();
        });

        PodcastLists::ensure_table();

        $list = PodcastList::with_network_scope(function () {
            return PodcastList::find_one_by_slug('example');
        });

        $this->assertNotNull($list);
        $this->assertEquals('Example', $list->title);
    }

    public function testSiteUninstallKeepsNetworkPodcastListTable()
    {
        PodcastLists::ensure_table();

        \Podlove\Modules\Networks\Networks::instance()->uninstall();

        $this->assertTrue($this->tableExists());
    }

    public function testNetworkUninstallRemovesNetworkPodcastListTable()
    {
        PodcastLists::ensure_table();
        $this->assertTrue($this->tableExists());

        \Podlove\Modules\Networks\Networks::instance()->uninstall_network();

        $this->assertFalse($this->tableExists());
    }

    private function tableExists(): bool
    {
        return PodcastList::with_network_scope(function () {
            return PodcastList::table_exists();
        });
    }
}
