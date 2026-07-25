<?php

use Podlove\Model\Episode;
use Podlove\Modules\Auphonic\REST_API as AuphonicRestApi;
use Podlove\Modules\Plus\API as PlusApi;
use Podlove\Modules\Plus\RestApi as PlusRestApi;

/**
 * @internal
 *
 * @coversNothing
 */
class ExternalMutationAuthorizationTest extends WP_UnitTestCase
{
    private $server;
    private $register_routes;
    private $author_one_id;
    private $author_two_id;
    private $editor_id;
    private $administrator_id;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_test_reset_podcast_episodes();

        $auphonic_module = new class {
            public function get_module_option($name)
            {
                return $name === 'auphonic_api_key' ? 'secret-token' : null;
            }
        };
        $plus_api = new PlusApi(null, 'plus-token');
        $this->register_routes = function () use ($auphonic_module, $plus_api) {
            (new AuphonicRestApi($auphonic_module))->register_routes();
            (new PlusRestApi($plus_api))->register_routes();
        };
        add_action('rest_api_init', $this->register_routes);

        global $wp_rest_server;
        $wp_rest_server = null;
        $this->server = rest_get_server();

        $this->author_one_id = $this->factory->user->create(['role' => 'author']);
        $this->author_two_id = $this->factory->user->create(['role' => 'author']);
        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
        $this->administrator_id = $this->factory->user->create(['role' => 'administrator']);
    }

    public function tearDown(): void
    {
        remove_action('rest_api_init', $this->register_routes);
        podlove_test_reset_podcast_episodes();
        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testAuphonicTokenRequiresManageOptions(): void
    {
        wp_set_current_user($this->editor_id);
        $denied_response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/auphonic/token')
        );

        $this->assertSame(403, $denied_response->get_status());

        wp_set_current_user($this->administrator_id);
        $allowed_response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/auphonic/token')
        );

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertSame('secret-token', $allowed_response->get_data());
    }

    public function testAuphonicEpisodeOperationDenialOccursBeforeHttpRequest(): void
    {
        $episode = $this->createEpisode($this->author_one_id);
        $http_requests = 0;
        $block_http = function () use (&$http_requests) {
            ++$http_requests;

            return new WP_Error('unexpected_http_request');
        };
        add_filter('pre_http_request', $block_http);

        wp_set_current_user($this->author_two_id);
        $response = $this->server->dispatch(new WP_REST_Request(
            'POST',
            '/podlove/v2/auphonic/init-plus-file-transfer/production-uuid/'.$episode->post_id
        ));

        remove_filter('pre_http_request', $block_http);

        $this->assertSame(403, $response->get_status());
        $this->assertSame(0, $http_requests);
    }

    /**
     * @dataProvider plusUploadRoutes
     */
    public function testPlusUploadOperationsRequireEpisodeOwnership(string $route): void
    {
        $episode = $this->createEpisode($this->author_one_id);
        $http_requests = 0;
        $block_http = function () use (&$http_requests) {
            ++$http_requests;

            return new WP_Error('unexpected_http_request');
        };
        add_filter('pre_http_request', $block_http);

        wp_set_current_user($this->author_two_id);
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('filename', 'episode.mp3');
        $request->set_param('episode_id', $episode->id);
        $response = $this->server->dispatch($request);

        remove_filter('pre_http_request', $block_http);

        $this->assertSame(403, $response->get_status());
        $this->assertSame(0, $http_requests);
    }

    /**
     * @dataProvider plusUploadRoutes
     */
    public function testPlusUploadOperationsRequireEpisodeId(string $route): void
    {
        wp_set_current_user($this->author_one_id);
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('filename', 'episode.mp3');
        $response = $this->server->dispatch($request);

        $this->assertSame(400, $response->get_status());
    }

    public function plusUploadRoutes(): array
    {
        return [
            'create upload' => ['/podlove/v2/plus/create_file_upload'],
            'check file' => ['/podlove/v2/plus/check_file_exists'],
            'complete upload' => ['/podlove/v2/plus/complete_file_upload'],
        ];
    }

    public function testPlusFilenameGenerationUsesEpisodeOwnership(): void
    {
        $episode = $this->createEpisode($this->author_one_id);
        $route = '/podlove/v2/plus/generate_filename';

        wp_set_current_user($this->author_two_id);
        $denied_response = $this->server->dispatch(
            $this->filenameRequest($route, $episode)
        );

        $this->assertSame(403, $denied_response->get_status());

        wp_set_current_user($this->author_one_id);
        $allowed_response = $this->server->dispatch(
            $this->filenameRequest($route, $episode)
        );

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertSame((int) $episode->id, $allowed_response->get_data()['episode_id']);
    }

    public function testPlusMigrationRequiresManageOptions(): void
    {
        wp_set_current_user($this->editor_id);
        $denied_response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/plus/get_migration_status')
        );

        $this->assertSame(403, $denied_response->get_status());

        wp_set_current_user($this->administrator_id);
        $allowed_response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/plus/get_migration_status')
        );

        $this->assertSame(200, $allowed_response->get_status());
    }

    private function createEpisode(int $author_id): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => 'External Mutation Authorization',
            'post_type' => 'podcast',
            'post_status' => 'draft',
            'post_author' => $author_id,
        ]);

        return Episode::find_or_create_by_post_id($post_id);
    }

    private function filenameRequest(string $route, Episode $episode): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', $route);
        $request->set_param('original_filename', 'episode.mp3');
        $request->set_param('episode_id', $episode->id);

        return $request;
    }
}
