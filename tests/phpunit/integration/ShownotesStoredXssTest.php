<?php

use Podlove\Model\Episode;
use Podlove\Modules\Shownotes\Model\Entry;
use Podlove\Modules\Shownotes\Shownotes;
use Podlove\Template\TwigFilter;

/**
 * @internal
 *
 * @coversNothing
 */
class ShownotesStoredXssTest extends WP_UnitTestCase
{
    private $episode;
    private $post;
    private $hostile_entry;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_test_activate_module('shownotes', Shownotes::class);
        Entry::build();
        Entry::delete_all();
        podlove_test_reset_podcast_episodes();

        $post_id = wp_insert_post([
            'post_title' => 'Shownotes Rendering Test Episode',
            'post_type' => 'podcast',
            'post_status' => 'publish',
        ]);
        $this->episode = Episode::find_or_create_by_post_id($post_id);
        $this->post = get_post($post_id);

        $this->create_entry([
            'type' => 'topic',
            'title' => '&lt;img src=x onerror=alert(1)&gt;Topic',
            'position' => 0,
        ]);
        $this->hostile_entry = $this->create_entry([
            'type' => 'link',
            'title' => '&lt;script&gt;alert(1)&lt;/script&gt;Legacy',
            'url' => 'javascript:alert(1)',
            'original_url' => 'data:text/html,<script>alert(1)</script>',
            'icon' => 'data:image/svg+xml,<svg onload=alert(1)>',
            'position' => 1,
        ]);
        $this->create_entry([
            'type' => 'link',
            'title' => 'Original URL fallback',
            'url' => 'javascript:alert(1)',
            'original_url' => 'https://example.test/original',
            'position' => 2,
        ]);
        $this->create_entry([
            'type' => 'link',
            'title' => 'Fish & Chips "quoted" 🎙️',
            'url' => 'https://example.test/search?q=one&lang=de#result',
            'original_url' => 'https://example.test/search?q=one&lang=de#result',
            'icon' => 'https://example.test/favicon.ico',
            'position' => 3,
        ]);
    }

    public function tearDown(): void
    {
        wp_reset_postdata();

        if (Entry::table_exists()) {
            Entry::delete_all();
        }

        podlove_test_reset_podcast_episodes();

        parent::tearDown();
    }

    /**
     * @dataProvider shownotesTemplates
     */
    public function testBundledTemplatesKeepLegacyPayloadsInert(string $template): void
    {
        $html = $this->render($template);

        $this->assertSafeRenderedHtml($html);
        $this->assertStringContainsString('Fish &amp; Chips &quot;quoted&quot; 🎙️', $html);
        $this->assertStringContainsString(
            'href="https://example.test/search?q=one&#038;lang=de#result"',
            $html
        );
        $this->assertStringContainsString('href="https://example.test/original"', $html);
    }

    public function shownotesTemplates(): array
    {
        return [
            'shortcode' => ['@shownotes/shownotes.twig'],
            'plain list' => ['@shownotes/plain-html-list.twig'],
            'grouped list' => ['@shownotes/plain-html-list-grouped.twig'],
        ];
    }

    public function testGroupedLinksPreventOpenerAccess(): void
    {
        $html = $this->render('@shownotes/plain-html-list-grouped.twig');

        $this->assertStringContainsString(
            'target="_blank" rel="noopener noreferrer"',
            $html
        );
    }

    public function testMainTemplateFeedBranchIsEscaped(): void
    {
        global $wp_query;

        $was_feed = $wp_query->is_feed;
        $wp_query->is_feed = true;
        $html = $this->render('@shownotes/shownotes.twig');
        $wp_query->is_feed = $was_feed;

        $this->assertSafeRenderedHtml($html);
        $this->assertStringContainsString('Fish &amp; Chips &quot;quoted&quot; 🎙️', $html);
    }

    public function testRenderingDoesNotRewriteLegacyRows(): void
    {
        global $wpdb;

        $this->render('@shownotes/shownotes.twig');
        $stored = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT title, url, original_url, icon FROM '.Entry::table_name().' WHERE id = %d',
                $this->hostile_entry->id
            ),
            ARRAY_A
        );

        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;Legacy', $stored['title']);
        $this->assertSame('javascript:alert(1)', $stored['url']);
        $this->assertSame('data:text/html,<script>alert(1)</script>', $stored['original_url']);
        $this->assertSame('data:image/svg+xml,<svg onload=alert(1)>', $stored['icon']);
    }

    private function create_entry(array $data): Entry
    {
        $entry = new Entry();
        $entry->episode_id = $this->episode->id;

        foreach ($data as $field => $value) {
            $entry->{$field} = $value;
        }

        $entry->save();

        return $entry;
    }

    private function render(string $template): string
    {
        global $post;

        $post = $this->post;
        setup_postdata($post);

        return TwigFilter::apply_to_html($template);
    }

    private function assertSafeRenderedHtml(string $html): void
    {
        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('onerror=', strtolower($html));
        $this->assertStringNotContainsString('onload=', strtolower($html));
        $this->assertStringNotContainsString('javascript:', strtolower($html));
        $this->assertStringNotContainsString('data:text/html', strtolower($html));
        $this->assertStringNotContainsString('data:image/', strtolower($html));
    }
}
