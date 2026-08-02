<?php

use Podlove\Downloads_List_Table;

/**
 * @internal
 *
 * @coversNothing
 */
class AnalyticsListRenderingSecurityTest extends WP_UnitTestCase
{
    public function testEpisodeTitleIsEscapedInAnalyticsTable(): void
    {
        $episode = [
            'id' => 7,
            'post_id' => 42,
            'title' => '<img src=x onerror=alert(1)>Episode',
            'post_date' => '2026-01-01 12:00:00',
            'post_date_gmt' => '2026-01-01 12:00:00',
        ];
        $table = new Downloads_List_Table();

        ob_start();
        $table->column_cb($episode);
        $html = $table->column_episode($episode).ob_get_clean();

        $this->assertStringNotContainsString('<img', strtolower($html));
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;Episode', $html);
    }
}
