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
        $entry = Entry::create([
            'episode_id' => $episode->id,
            'type' => 'link',
            'original_url' => 'https://example.test/update-unfurl',
            'title' => 'Update Unfurl',
        ]);

        $request = new WP_REST_Request('PUT', $route.'/'.$entry->id);
        $request->set_param('title', 'Updated');
        $request->set_param('unfurl_data', ['title' => 'Client Data']);

        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('podlove_rest_invalid_unfurl_data', $response->get_data()['code']);
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

    private function create_episode(): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => 'Shownotes API Test Episode',
            'post_type' => 'podcast',
            'post_status' => 'draft',
        ]);

        return Episode::find_or_create_by_post_id($post_id);
    }

    private function serialized_string_payload(): string
    {
        return 's:5:"hello";';
    }
}
