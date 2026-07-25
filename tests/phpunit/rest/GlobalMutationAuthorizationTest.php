<?php

use Podlove\Model\Podcast;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Social\Model\ContributorService;
use Podlove\Modules\Social\Model\Service;
use Podlove\Modules\Social\Model\ShowService;

/**
 * @internal
 *
 * @coversNothing
 */
class GlobalMutationAuthorizationTest extends WP_UnitTestCase
{
    private $server;
    private $editor_id;
    private $administrator_id;
    private $contributor_manager_id;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        \Podlove\Modules\Contributors\Contributors::instance()->load();
        \Podlove\Modules\Social\Social::instance()->load();
        \Podlove\Modules\Onboarding\Onboarding::instance()->load();

        Contributor::build();
        ContributorService::build();
        Service::build();
        ShowService::build();
        Contributor::delete_all();
        ContributorService::delete_all();
        ShowService::delete_all();

        global $wp_rest_server;
        $wp_rest_server = null;
        $this->server = rest_get_server();

        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
        $this->administrator_id = $this->factory->user->create(['role' => 'administrator']);
        $this->contributor_manager_id = $this->factory->user->create(['role' => 'author']);
        get_user_by('id', $this->contributor_manager_id)->add_cap('podlove_manage_contributors');
    }

    public function tearDown(): void
    {
        ContributorService::delete_all();
        ShowService::delete_all();
        Contributor::delete_all();
        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testPodcastMutationRequiresManageOptions(): void
    {
        $podcast = Podcast::get();
        $podcast->title = 'Original title';
        $podcast->save();

        wp_set_current_user($this->editor_id);
        $denied_response = $this->server->dispatch($this->podcastRequest('Denied title'));

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame('Original title', Podcast::get()->title);

        wp_set_current_user($this->administrator_id);
        $allowed_response = $this->server->dispatch($this->podcastRequest('Administrator title'));

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertSame('Administrator title', Podcast::get()->title);
    }

    /**
     * @dataProvider globalAdminRoutes
     */
    public function testGlobalAdministrationRoutesRejectEditors(string $method, string $route): void
    {
        wp_set_current_user($this->editor_id);
        $response = $this->server->dispatch(new WP_REST_Request($method, $route));

        $this->assertSame(403, $response->get_status());
    }

    public function globalAdminRoutes(): array
    {
        return [
            'clear caches' => ['DELETE', '/podlove/v2/tools/clear-caches'],
            'onboarding setup' => ['POST', '/podlove/v2/onboarding/setup'],
            'onboarding options' => ['GET', '/podlove/v2/admin/onboarding'],
            'PLUS features' => ['GET', '/podlove/v2/admin/plus/features'],
        ];
    }

    public function testManageOptionsCapabilityIsUsedInsteadOfRoleName(): void
    {
        $delegated_admin_id = $this->factory->user->create(['role' => 'editor']);
        get_user_by('id', $delegated_admin_id)->add_cap('manage_options');
        wp_set_current_user($delegated_admin_id);

        $onboarding_response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/admin/onboarding')
        );
        $plus_response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/admin/plus/features')
        );

        $this->assertSame(200, $onboarding_response->get_status());
        $this->assertSame(200, $plus_response->get_status());
    }

    public function testDedicatedContributorCapabilityCanManageDirectoryOnly(): void
    {
        wp_set_current_user($this->contributor_manager_id);
        $contributor_response = $this->server->dispatch(
            new WP_REST_Request('POST', '/podlove/v2/contributors')
        );
        $podcast_response = $this->server->dispatch($this->podcastRequest('Forbidden title'));

        $this->assertSame(201, $contributor_response->get_status());
        $this->assertSame(403, $podcast_response->get_status());
        $this->assertSame(1, Contributor::count());
    }

    public function testSocialMutationsUseContributorAndPodcastCapabilities(): void
    {
        $contributor = new Contributor();
        $contributor->visibility = 1;
        $contributor->save();

        wp_set_current_user($this->contributor_manager_id);
        $contributor_response = $this->server->dispatch(
            new WP_REST_Request('POST', '/podlove/v2/social/contributors/'.$contributor->id)
        );
        $denied_podcast_response = $this->server->dispatch(
            new WP_REST_Request('POST', '/podlove/v2/social/podcast')
        );

        $this->assertSame(201, $contributor_response->get_status());
        $this->assertSame(403, $denied_podcast_response->get_status());
        $this->assertSame(1, ContributorService::count());
        $this->assertSame(0, ShowService::count());

        wp_set_current_user($this->administrator_id);
        $allowed_podcast_response = $this->server->dispatch(
            new WP_REST_Request('POST', '/podlove/v2/social/podcast')
        );

        $this->assertSame(201, $allowed_podcast_response->get_status());
        $this->assertSame(1, ShowService::count());
    }

    private function podcastRequest(string $title): WP_REST_Request
    {
        $request = new WP_REST_Request('PUT', '/podlove/v2/podcast');
        $request->set_param('title', $title);

        return $request;
    }
}
