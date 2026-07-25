<?php

use Podlove\Model\Episode;
use Podlove\Modules\Shownotes\Model\Entry;

/**
 * @internal
 *
 * @coversNothing
 */
class ShownotesApiTest extends WP_UnitTestCase
{
    private $server;
    private $contributor_user_id;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_test_activate_module('shownotes', \Podlove\Modules\Shownotes\Shownotes::class);
        Entry::build();
        Entry::delete_all();
        podlove_test_reset_podcast_episodes();

        global $wp_rest_server;
        $wp_rest_server = null;
        $this->server = rest_get_server();

        $this->contributor_user_id = $this->factory->user->create(['role' => 'contributor']);
        wp_set_current_user($this->contributor_user_id);
    }

    public function tearDown(): void
    {
        if (Entry::table_exists()) {
            Entry::delete_all();
        }

        podlove_test_reset_podcast_episodes();
        wp_set_current_user(0);

        parent::tearDown();
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testCreateRejectsClientUnfurlData(string $route): void
    {
        $episode = $this->create_episode();
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('episode_id', $episode->id);
        $request->set_param('type', 'link');
        $request->set_param('original_url', 'https://example.test/reject-unfurl');
        $request->set_param('title', 'Reject Unfurl');
        $request->set_param('unfurl_data', ['title' => 'Client Data']);

        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('podlove_rest_invalid_unfurl_data', $response->get_data()['code']);
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testCreateRejectsSerializedRequestValues(string $route): void
    {
        $episode = $this->create_episode();
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('episode_id', $episode->id);
        $request->set_param('type', 'topic');
        $request->set_param('title', $this->serialized_string_payload());

        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('podlove_rest_invalid_serialized_data', $response->get_data()['code']);
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testCreateIgnoresProtectedEntryFields(string $route): void
    {
        $episode = $this->create_episode();
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('id', 12345);
        $request->set_param('episode_id', $episode->id);
        $request->set_param('type', 'link');
        $request->set_param('state', 'failed');
        $request->set_param('affiliate_url', 'https://attacker.test/affiliate');
        $request->set_param('original_url', 'https://example.test/protected-fields');
        $request->set_param('title', 'Protected Fields');

        $response = $this->server->dispatch($request);
        $data = $response->get_data();

        $this->assertSame(201, $response->get_status());
        $this->assertNotSame(12345, (int) $data['id']);
        $this->assertNull($data['state']);
        $this->assertNull($data['affiliate_url']);
        $this->assertSame('link', $data['type']);
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testUpdateRejectsClientUnfurlData(string $route): void
    {
        $episode = $this->create_episode();
        $entry = new Entry();
        $entry->episode_id = $episode->id;
        $entry->type = 'link';
        $entry->original_url = 'https://example.test/update-unfurl';
        $entry->title = 'Update Unfurl';
        $entry->save();

        $request = new WP_REST_Request('PUT', $route.'/'.$entry->id);
        $request->set_param('title', 'Updated');
        $request->set_param('unfurl_data', ['title' => 'Client Data']);

        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('podlove_rest_invalid_unfurl_data', $response->get_data()['code']);
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testCreateUsesEpisodeOwnership(string $route): void
    {
        $owner_id = $this->factory->user->create(['role' => 'author']);
        $other_author_id = $this->factory->user->create(['role' => 'author']);
        $episode = $this->create_episode($owner_id);

        wp_set_current_user($other_author_id);
        $denied_response = $this->server->dispatch($this->create_request($route, $episode));

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame(0, Entry::count());

        wp_set_current_user($owner_id);
        $allowed_response = $this->server->dispatch($this->create_request($route, $episode));

        $this->assertSame(201, $allowed_response->get_status());
        $this->assertSame(1, Entry::count());
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testChildMutationHidesForeignAndMissingEntries(string $route): void
    {
        $owner_id = $this->factory->user->create(['role' => 'author']);
        $other_author_id = $this->factory->user->create(['role' => 'author']);
        $editor_id = $this->factory->user->create(['role' => 'editor']);
        $episode = $this->create_episode($owner_id);
        $entry = $this->create_entry($episode, 'Original');

        wp_set_current_user($other_author_id);
        $foreign_request = new WP_REST_Request('PUT', $route.'/'.$entry->id);
        $foreign_request->set_param('title', 'Foreign update');
        $foreign_response = $this->server->dispatch($foreign_request);
        $missing_response = $this->server->dispatch(new WP_REST_Request('DELETE', $route.'/999999'));

        $this->assertSame(404, $foreign_response->get_status());
        $this->assertSame(404, $missing_response->get_status());
        $this->assertSame('Original', Entry::find_by_id($entry->id)->title);

        wp_set_current_user($editor_id);
        $editor_request = new WP_REST_Request('PUT', $route.'/'.$entry->id);
        $editor_request->set_param('title', 'Editor update');
        $editor_response = $this->server->dispatch($editor_request);

        $this->assertSame(200, $editor_response->get_status());
        $this->assertSame('Editor update', Entry::find_by_id($entry->id)->title);
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testUnfurlDenialOccursBeforeHttpRequest(string $route): void
    {
        $owner_id = $this->factory->user->create(['role' => 'author']);
        $other_author_id = $this->factory->user->create(['role' => 'author']);
        $episode = $this->create_episode($owner_id);
        $entry = $this->create_entry($episode, 'Original');
        $http_requests = 0;
        $block_http = function () use (&$http_requests) {
            ++$http_requests;

            return new WP_Error('unexpected_http_request');
        };
        add_filter('pre_http_request', $block_http);

        wp_set_current_user($other_author_id);
        $response = $this->server->dispatch(new WP_REST_Request('PUT', $route.'/'.$entry->id.'/unfurl'));

        remove_filter('pre_http_request', $block_http);

        $this->assertSame(404, $response->get_status());
        $this->assertSame(0, $http_requests);
        $this->assertNull(Entry::find_by_id($entry->id)->state);
    }

    /**
     * @dataProvider shownotesImportRoutes
     */
    public function testHtmlImportUsesPostOwnership(string $route): void
    {
        $owner_id = $this->factory->user->create(['role' => 'author']);
        $other_author_id = $this->factory->user->create(['role' => 'author']);
        $episode = $this->create_episode($owner_id);

        wp_set_current_user($other_author_id);
        $denied_response = $this->server->dispatch($this->import_request($route, $episode->post_id));

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame(0, Entry::count());

        wp_set_current_user($owner_id);
        $allowed_response = $this->server->dispatch($this->import_request($route, $episode->post_id));

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertSame(1, Entry::count());
    }

    /**
     * @dataProvider shownotesImportRoutes
     */
    public function testHtmlImportDoesNotCreateMissingEpisode(string $route): void
    {
        $author_id = $this->factory->user->create(['role' => 'author']);
        $post_id = wp_insert_post([
            'post_title' => 'Podcast Post Without Episode',
            'post_type' => 'podcast',
            'post_status' => 'draft',
            'post_author' => $author_id,
        ]);
        Episode::find_one_by_post_id($post_id)->delete();
        $episode_count = Episode::count();

        wp_set_current_user($author_id);
        $response = $this->server->dispatch($this->import_request($route, $post_id));

        $this->assertSame(404, $response->get_status());
        $this->assertSame($episode_count, Episode::count());
        $this->assertNull(Episode::find_one_by_post_id($post_id));
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testCreateNormalizesShownotesFields(string $route): void
    {
        $episode = $this->create_episode();
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('episode_id', $episode->id);
        $request->set_param('type', 'link');
        $request->set_param('original_url', 'https://example.test/search?q=one&lang=de#result');
        $request->set_param('url', 'https://example.test/canonical?q=one&lang=de');
        $request->set_param('title', '<strong>Rock &amp; Roll 🎙️</strong>');
        $request->set_param('description', "Line 1\n<script>alert(1)</script>\nLine 2");
        $request->set_param('site_name', '&lt;img src=x onerror=alert(1)&gt;Example');
        $request->set_param('hidden', '1');

        $response = $this->server->dispatch($request);
        $entry = Entry::find_by_id($response->get_data()['id']);

        $this->assertSame(201, $response->get_status());
        $this->assertSame('Rock & Roll 🎙️', $entry->title);
        $this->assertSame("Line 1\n\nLine 2", $entry->description);
        $this->assertSame('Example', $entry->site_name);
        $this->assertSame('https://example.test/search?q=one&lang=de#result', $entry->original_url);
        $this->assertSame('https://example.test/canonical?q=one&lang=de', $entry->url);
        $this->assertSame('1', $entry->hidden);
    }

    /**
     * @dataProvider invalidShownotesUrlRequests
     */
    public function testCreateAndUpdateRejectUnsafeUrls(string $method, string $route, string $field, string $url): void
    {
        $episode = $this->create_episode();
        $entry = $this->create_entry($episode, 'Original');
        $request = new WP_REST_Request($method, $method === 'POST' ? $route : $route.'/'.$entry->id);

        if ($method === 'POST') {
            $request->set_param('episode_id', $episode->id);
            $request->set_param('type', 'link');
            $request->set_param('original_url', 'https://example.test/new');
        }

        $request->set_param($field, $url);
        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('rest_invalid_param', $response->get_data()['code']);
        $this->assertSame('https://example.test/shownote', Entry::find_by_id($entry->id)->original_url);
    }

    public function invalidShownotesUrlRequests(): array
    {
        $cases = [];
        $urls = [
            'javascript' => 'javascript:alert(1)',
            'encoded-javascript' => 'javascript&#58;alert(1)',
            'data' => 'data:text/html,<script>alert(1)</script>',
            'protocol-relative' => '//example.test/path',
            'relative' => '/path',
            'malformed' => 'not a url',
        ];

        foreach ($this->shownotesCreateRoutes() as $version => [$route]) {
            foreach ($urls as $name => $url) {
                $cases["{$version}-create-{$name}"] = ['POST', $route, 'url', $url];
                $cases["{$version}-update-{$name}"] = ['PUT', $route, 'original_url', $url];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testSlacknotesDataIsNormalizedAndUnknownKeysAreDiscarded(string $route): void
    {
        $episode = $this->create_episode();
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('episode_id', $episode->id);
        $request->set_param('type', 'link');
        $request->set_param('original_url', 'https://example.test/slacknotes');
        $request->set_param('data', [
            'title' => '&lt;script&gt;alert(1)&lt;/script&gt;Slack title',
            'source' => '<img src=x onerror=alert(1)>Slack',
            'unix_date' => 2000,
            'orderNumber' => 3000,
            'description' => '<script>ignored</script>',
        ]);

        $response = $this->server->dispatch($request);
        $entry = Entry::find_by_id($response->get_data()['id']);

        $this->assertSame(201, $response->get_status());
        $this->assertSame('Slack title', $entry->title);
        $this->assertSame('Slack', $entry->site_name);
        $this->assertSame('2', $entry->created_at);
        $this->assertNull($entry->description);
    }

    /**
     * @dataProvider shownotesImportRoutes
     */
    public function testHtmlImportRejectsUnsafeLinks(string $route): void
    {
        $episode = $this->create_episode();
        $request = $this->import_request(
            $route,
            $episode->post_id,
            '<a href="javascript:alert(1)">&lt;script&gt;alert(1)&lt;/script&gt;</a>'
        );

        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('rest_invalid_param', $response->get_data()['code']);
        $this->assertSame(0, Entry::count());
    }

    public function shownotesImportRoutes(): array
    {
        return [
            'v1' => ['/podlove/v1/shownotes/html'],
            'v2' => ['/podlove/v2/shownotes/html'],
        ];
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testLegacyRowsAreNormalizedForRestWithoutBeingRewritten(string $route): void
    {
        global $wpdb;

        $episode = $this->create_episode();
        $entry = $this->create_entry($episode, '&lt;script&gt;alert(1)&lt;/script&gt;Legacy');
        $entry->url = 'javascript:alert(1)';
        $entry->icon = 'data:image/svg+xml,<svg onload=alert(1)>';
        $entry->unfurl_data = [
            'url' => 'javascript:alert(1)',
            'title' => '&lt;script&gt;alert(1)&lt;/script&gt;Remote',
            'site_url' => 'https://legacy.example',
            'providers' => [
                'twitter' => ['image:src' => 'data:image/png;base64,AAAA'],
            ],
        ];
        $entry->save();

        $response = $this->server->dispatch(new WP_REST_Request('GET', $route.'/'.$entry->id));
        $data = $response->get_data();
        $stored = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT title, url, icon, unfurl_data FROM '.Entry::table_name().' WHERE id = %d',
                $entry->id
            ),
            ARRAY_A
        );

        $this->assertSame(200, $response->get_status());
        $this->assertSame('Legacy', $data['title']);
        $this->assertSame('', $data['url']);
        $this->assertSame('', $data['icon']);
        $this->assertSame('', $data['unfurl_data']['url']);
        $this->assertSame('Remote', $data['unfurl_data']['title']);
        $this->assertArrayNotHasKey('image:src', $data['unfurl_data']['providers']['twitter']);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;Legacy', $stored['title']);
        $this->assertSame('javascript:alert(1)', $stored['url']);
        $this->assertSame('data:image/svg+xml,<svg onload=alert(1)>', $stored['icon']);
        $this->assertStringContainsString('javascript:alert(1)', $stored['unfurl_data']);
    }

    /**
     * @dataProvider shownotesCreateRoutes
     */
    public function testUnfurlNormalizesRemoteMetadata(string $route): void
    {
        $episode = $this->create_episode();
        $entry = $this->create_entry($episode, '');
        $body = wp_json_encode([
            'url' => 'javascript:alert(1)',
            'title' => '&lt;script&gt;alert(1)&lt;/script&gt;Remote',
            'description' => '<img src=x onerror=alert(1)>Description',
            'site_name' => '<svg onload=alert(1)>Site',
            'site_url' => 'https://remote.example',
            'icon' => ['url' => 'data:image/svg+xml,<svg onload=alert(1)>'],
            'image' => 'javascript:alert(1)',
            'screenshot_url' => 'data:image/png;base64,AAAA',
            'providers' => [
                'misc' => [
                    'icons' => [
                        ['url' => '/favicon.ico'],
                        ['url' => 'javascript:alert(1)'],
                    ],
                ],
                'open_graph' => ['image' => 'data:image/png;base64,AAAA'],
                'twitter' => ['image:src' => '/twitter.png'],
            ],
        ]);
        $mock_http = function ($preempt, $args, $url) use ($body) {
            if (!str_starts_with($url, 'https://plus.podlove.org/api/unfurl')) {
                return $preempt;
            }

            return [
                'headers' => [],
                'body' => $body,
                'response' => ['code' => 200, 'message' => 'OK'],
                'cookies' => [],
                'filename' => null,
            ];
        };
        add_filter('pre_http_request', $mock_http, 10, 3);

        $response = $this->server->dispatch(
            new WP_REST_Request('PUT', $route.'/'.$entry->id.'/unfurl')
        );

        remove_filter('pre_http_request', $mock_http, 10);

        $this->assertSame(200, $response->get_status());

        $stored = Entry::find_by_id($entry->id);
        $unfurl_data = $stored->unfurl_data_array();

        $this->assertNull($stored->url);
        $this->assertSame('Remote', $stored->title);
        $this->assertSame('Description', $stored->description);
        $this->assertSame('Site', $stored->site_name);
        $this->assertNull($stored->icon);
        $this->assertNull($stored->image);
        $this->assertSame(
            [['url' => 'https://remote.example/favicon.ico']],
            $unfurl_data['providers']['misc']['icons']
        );
        $this->assertSame('https://remote.example/twitter.png', $unfurl_data['providers']['twitter']['image:src']);
        $this->assertArrayNotHasKey('image', $unfurl_data['providers']['open_graph']);
    }

    public function shownotesCreateRoutes(): array
    {
        return [
            'v1' => ['/podlove/v1/shownotes'],
            'v2' => ['/podlove/v2/shownotes'],
        ];
    }

    public function testEntryToArrayDoesNotUnserializeUserControlledTextFields(): void
    {
        $payload = $this->serialized_string_payload();
        $entry = new Entry();
        $entry->title = $payload;
        $entry->unfurl_data = $payload;

        $data = $entry->to_array();

        $this->assertSame($payload, $data['title']);
        $this->assertNull($data['unfurl_data']);
    }

    private function create_episode(?int $author_id = null): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => 'Shownotes API Test Episode',
            'post_type' => 'podcast',
            'post_status' => 'draft',
            'post_author' => $author_id ?? get_current_user_id(),
        ]);

        return Episode::find_or_create_by_post_id($post_id);
    }

    private function create_request(string $route, Episode $episode): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('episode_id', $episode->id);
        $request->set_param('type', 'topic');
        $request->set_param('title', 'Authorized shownote');

        return $request;
    }

    private function import_request(
        string $route,
        int $post_id,
        string $html = '<h2>Imported topic</h2>'
    ): WP_REST_Request {
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('post_id', $post_id);
        $request->set_param('html', $html);

        return $request;
    }

    private function create_entry(Episode $episode, string $title): Entry
    {
        $entry = new Entry();
        $entry->episode_id = $episode->id;
        $entry->type = 'link';
        $entry->original_url = 'https://example.test/shownote';
        $entry->title = $title;
        $entry->save();

        return $entry;
    }

    private function serialized_string_payload(): string
    {
        return 's:5:"hello";';
    }
}
