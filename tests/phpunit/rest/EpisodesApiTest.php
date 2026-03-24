<?php

use Podlove\Model\Episode;

class EpisodesApiTest extends WP_UnitTestCase
{
    private $server;
    private $admin_user_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->server = rest_get_server();

        podlove_setup_database_tables();
        podlove_test_reset_podcast_episodes();

        $this->admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_user_id);
    }

    public function tearDown(): void
    {
        podlove_test_reset_podcast_episodes();
        parent::tearDown();
    }

    public function test_create_episode()
    {
        $create_request = new WP_REST_Request('POST', '/podlove/v2/episodes');
        $create_response = $this->server->dispatch($create_request);

        $this->assertEquals(201, $create_response->get_status());
        $create_data = $create_response->get_data();
        $this->assertArrayHasKey('id', $create_data);

        $episode_id = (int) $create_data['id'];
        $episode = Episode::find_by_id($episode_id);
        $this->assertNotNull($episode);
    }

    public function test_update_episode()
    {
        $episode = $this->create_episode();

        $update_request = new WP_REST_Request('PUT', '/podlove/v2/episodes/'.$episode->id);
        $update_request->set_param('title', 'Updated Episode Title');
        $update_request->set_param('subtitle', 'Updated Episode Subtitle');
        $update_request->set_param('summary', 'Updated Episode Summary');
        $update_request->set_param('number', 42);
        $update_request->set_param('explicit', true);
        $update_request->set_param('soundbite_title', 'Intro');
        $update_request->set_param('soundbite_start', '00:00:10');
        $update_request->set_param('soundbite_duration', '00:00:30');

        $update_response = $this->server->dispatch($update_request);
        $this->assertEquals(200, $update_response->get_status());

        $episode = Episode::find_by_id($episode->id);
        $this->assertEquals('Updated Episode Title', $episode->title);
        $this->assertEquals('Updated Episode Subtitle', $episode->subtitle);
        $this->assertEquals('Updated Episode Summary', $episode->summary);
        $this->assertEquals(42, $episode->number);
        $this->assertEquals(1, $episode->explicit);
        $this->assertEquals('Intro', $episode->soundbite_title);
        $this->assertEquals('00:00:10', $episode->soundbite_start);
        $this->assertEquals('00:00:30', $episode->soundbite_duration);

        $post = get_post($episode->post_id);
        $this->assertEquals('Updated Episode Title', $post->post_title);
    }

    public function test_delete_episode()
    {
        $episode = $this->create_episode();

        $delete_request = new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$episode->id);
        $delete_response = $this->server->dispatch($delete_request);
        $this->assertEquals(200, $delete_response->get_status());

        $post = get_post($episode->post_id);
        $this->assertEquals('trash', $post->post_status);
    }

    public function test_create_episode_requires_permissions()
    {
        wp_set_current_user(0);

        $request = new WP_REST_Request('POST', '/podlove/v2/episodes');
        $response = $this->server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
    }

    public function test_update_episode_requires_permissions()
    {
        $episode = $this->create_episode();
        wp_set_current_user(0);

        $request = new WP_REST_Request('PUT', '/podlove/v2/episodes/'.$episode->id);
        $request->set_param('title', 'Unauthorized Update');
        $response = $this->server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
    }

    public function test_delete_episode_requires_permissions()
    {
        $episode = $this->create_episode();
        wp_set_current_user(0);

        $request = new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$episode->id);
        $response = $this->server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
    }

    public function test_update_episode_not_found()
    {
        $request = new WP_REST_Request('PUT', '/podlove/v2/episodes/999999');
        $request->set_param('title', 'Missing Episode');
        $response = $this->server->dispatch($request);

        $this->assertEquals(404, $response->get_status());
    }

    public function test_delete_episode_not_found()
    {
        $request = new WP_REST_Request('DELETE', '/podlove/v2/episodes/999999');
        $response = $this->server->dispatch($request);

        $this->assertEquals(404, $response->get_status());
    }

    public function test_update_episode_rejects_invalid_duration()
    {
        $episode = $this->create_episode();

        $request = new WP_REST_Request('PUT', '/podlove/v2/episodes/'.$episode->id);
        $request->set_param('duration', 'invalid');
        $response = $this->server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
    }

    private function create_episode(): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => 'API Test Episode',
            'post_type' => 'podcast',
            'post_status' => 'draft',
        ]);

        return Episode::find_or_create_by_post_id($post_id);
    }
}
