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
    protected function tearDown(): void
    {
        PodcastList::with_network_scope(function () {
            PodcastList::destroy();
        });

        parent::tearDown();
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

    private function tableExists(): bool
    {
        return PodcastList::with_network_scope(function () {
            return PodcastList::table_exists();
        });
    }
}
