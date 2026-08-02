<?php

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\Networks\Podcast_List_Table;

/**
 * @internal
 *
 * @coversNothing
 */
class NetworkDashboardRenderingSecurityTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_test_reset_podcast_episodes();
    }

    public function tearDown(): void
    {
        podlove_test_reset_podcast_episodes();
        delete_option('podlove_podcast');

        parent::tearDown();
    }

    public function testPodcastAndEpisodeTitlesAreEscapedInNetworkDashboard(): void
    {
        global $wpdb;

        $podcast = Podcast::get();
        $podcast->title = '<script>alert(1)</script>';
        $podcast->subtitle = '<img src=x onerror=alert(1)>';
        $podcast->save();

        $post_id = wp_insert_post([
            'post_title' => 'Latest',
            'post_type' => 'podcast',
            'post_status' => 'publish',
        ]);
        $wpdb->update($wpdb->posts, ['post_title' => '<svg onload=alert(1)>Latest'], ['ID' => $post_id]);
        clean_post_cache($post_id);
        $episode = new Episode();
        $episode->post_id = $post_id;
        $episode->slug = 'network-dashboard-rendering-test';
        $episode->save();

        $table = new Podcast_List_Table();
        $html = $table->column_title($podcast).$table->column_latest_episode($podcast);

        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('<img', strtolower($html));
        $this->assertStringNotContainsString('<svg', strtolower($html));
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('&lt;svg onload=alert(1)&gt;Latest', $html);
    }
}
