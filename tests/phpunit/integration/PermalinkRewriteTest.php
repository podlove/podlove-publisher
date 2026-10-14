<?php

/**
 * @internal
 *
 * @coversNothing
 */
class PermalinkRewriteTest extends WP_UnitTestCase
{
    private $original_rewrite;

    public function set_up()
    {
        parent::set_up();

        $this->original_rewrite = $GLOBALS['wp_rewrite'];
        $GLOBALS['wp_rewrite'] = clone $this->original_rewrite;
    }

    public function tear_down()
    {
        $GLOBALS['wp_rewrite'] = $this->original_rewrite;

        parent::tear_down();
    }

    /**
     * @dataProvider permalinkConfigurations
     */
    public function testPageStillResolvesAfterLateRewriteFlush(
        string $post_structure,
        string $use_post_permastruct,
        string $episode_structure
    ): void {
        $this->configurePermalinks($post_structure, $use_post_permastruct, $episode_structure);
        $page_id = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'permalink-page',
        ]);
        $url = get_permalink($page_id);

        $this->go_to($url);
        $this->assertTrue(is_page($page_id));

        // Yoast and other plugins can flush after the main query's wp hook.
        flush_rewrite_rules(false);

        $this->startRequest();
        $this->go_to($url);

        $this->assertFalse(is_404());
        $this->assertTrue(is_page($page_id));
    }

    public function permalinkConfigurations(): array
    {
        return [
            'shared post-name URLs' => ['/%postname%/', 'on', '/podcast/%podcast%/'],
            'post-name posts, prefixed episodes' => ['/%postname%/', 'off', '/episodes/%podcast%/'],
            'date posts, root episodes' => ['/%year%/%monthnum%/%day%/%postname%/', 'off', '/%podcast%/'],
            'date posts, prefixed episodes' => ['/%year%/%monthnum%/%day%/%postname%/', 'off', '/episodes/%podcast%/'],
        ];
    }

    public function testRootEpisodeUrlResolvesAfterMainQueryWithDateBasedPosts(): void
    {
        $this->configurePermalinks('/%year%/%monthnum%/%day%/%postname%/', 'off', '/%podcast%/');
        $episode_id = self::factory()->post->create([
            'post_type' => 'podcast',
            'post_status' => 'publish',
            'post_name' => 'permalink-episode',
        ]);
        $url = get_permalink($episode_id);

        $this->go_to($url);
        $this->assertSame($episode_id, get_queried_object_id());
        $this->assertSame($episode_id, url_to_postid($url));

        flush_rewrite_rules(false);

        $this->startRequest();
        $this->go_to($url);

        $this->assertFalse(is_404());
        $this->assertSame($episode_id, get_queried_object_id());
        $this->assertSame($episode_id, url_to_postid($url));
    }

    public function testDateBasedPostsAndPrefixedEpisodesResolveAfterLateFlush(): void
    {
        $this->configurePermalinks('/%year%/%monthnum%/%day%/%postname%/', 'off', '/episodes/%podcast%/');

        foreach (['post', 'podcast'] as $post_type) {
            $post_id = self::factory()->post->create([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'post_name' => 'permalink-'.$post_type,
            ]);
            $url = get_permalink($post_id);

            $this->startRequest();
            $this->go_to($url);
            flush_rewrite_rules(false);

            $this->startRequest();
            $this->go_to($url);

            $this->assertFalse(is_404());
            $this->assertSame($post_id, get_queried_object_id());
            $this->assertSame($post_id, url_to_postid($url));
        }
    }

    private function configurePermalinks(
        string $post_structure,
        string $use_post_permastruct,
        string $episode_structure
    ): void {
        update_option('permalink_structure', $post_structure);
        update_option('podlove_website', [
            'use_post_permastruct' => $use_post_permastruct,
            'custom_episode_slug' => $episode_structure,
        ]);

        // The test bootstrap initially loads the plugin with plain permalinks.
        // Register the production hooks after selecting this test's structure.
        remove_filter('request', 'podlove_podcast_permalink_proxy');
        podlove_register_permalink_hooks();
        $this->startRequest();
        flush_rewrite_rules(false);
    }

    private function startRequest(): void
    {
        $GLOBALS['wp_rewrite']->init();
        podlove_add_podcast_rewrite_rules();
    }
}
