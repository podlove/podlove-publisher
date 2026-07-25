<?php

use Podlove\Model\Episode;

/**
 * @internal
 *
 * @coversNothing
 */
class EpisodeMutationAuthorizationTest extends WP_UnitTestCase
{
    private $server;
    private $author_one_id;
    private $author_two_id;
    private $contributor_id;
    private $editor_id;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        \Podlove\Modules\Base::deactivate('seasons');
        podlove_test_reset_podcast_episodes();

        global $wp_rest_server;
        $wp_rest_server = null;
        $this->server = rest_get_server();

        $this->author_one_id = $this->factory->user->create(['role' => 'author']);
        $this->author_two_id = $this->factory->user->create(['role' => 'author']);
        $this->contributor_id = $this->factory->user->create(['role' => 'contributor']);
        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
    }

    public function tearDown(): void
    {
        podlove_test_reset_podcast_episodes();
        wp_set_current_user(0);

        parent::tearDown();
    }

    /**
     * @dataProvider updateRouteProvider
     */
    public function testOwnerCanUpdateDraftAndPublishedEpisode(string $route, string $parameter, string $property): void
    {
        foreach (['draft', 'publish'] as $status) {
            $episode = $this->createEpisode($status, $this->author_one_id);
            $value = 'Owner update '.$status;

            wp_set_current_user($this->author_one_id);
            $response = $this->dispatchUpdate($route, $episode, $parameter, $value);

            $this->assertSame(200, $response->get_status());
            $this->assertSame($value, Episode::find_by_id($episode->id)->{$property});
        }
    }

    /**
     * @dataProvider updateRouteProvider
     */
    public function testDifferentAuthorCannotUpdateEpisode(string $route, string $parameter, string $property): void
    {
        $episode = $this->createEpisode('publish', $this->author_one_id);
        $original_value = $episode->{$property};

        wp_set_current_user($this->author_two_id);
        $response = $this->dispatchUpdate($route, $episode, $parameter, 'Cross-author update');

        $this->assertSame(403, $response->get_status());
        $this->assertSame($original_value, Episode::find_by_id($episode->id)->{$property});
    }

    /**
     * @dataProvider updateRouteProvider
     */
    public function testContributorCannotUpdateAnotherUsersEpisode(string $route, string $parameter, string $property): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $original_value = $episode->{$property};

        wp_set_current_user($this->contributor_id);
        $response = $this->dispatchUpdate($route, $episode, $parameter, 'Contributor update');

        $this->assertSame(403, $response->get_status());
        $this->assertSame($original_value, Episode::find_by_id($episode->id)->{$property});
    }

    /**
     * @dataProvider updateRouteProvider
     */
    public function testEditorCanUpdateAnotherUsersEpisode(string $route, string $parameter, string $property): void
    {
        $episode = $this->createEpisode('publish', $this->author_one_id);

        wp_set_current_user($this->editor_id);
        $response = $this->dispatchUpdate($route, $episode, $parameter, 'Editor update');

        $this->assertSame(200, $response->get_status());
        $this->assertSame('Editor update', Episode::find_by_id($episode->id)->{$property});
    }

    public function updateRouteProvider(): array
    {
        return [
            'v1' => ['/podlove/v1/episodes/', 'soundbite_title', 'soundbite_title'],
            'v2' => ['/podlove/v2/episodes/', 'subtitle', 'subtitle'],
        ];
    }

    public function testAnonymousUpdateReturnsUnauthorized(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        wp_set_current_user(0);

        foreach (['/podlove/v1/episodes/', '/podlove/v2/episodes/'] as $route) {
            $request = new WP_REST_Request('PUT', $route.$episode->id);
            $request->set_param('soundbite_title', 'Anonymous update');

            $response = $this->server->dispatch($request);

            $this->assertSame(401, $response->get_status());
        }
    }

    public function testDifferentAuthorCannotTrashEpisode(): void
    {
        $episode = $this->createEpisode('publish', $this->author_one_id);

        wp_set_current_user($this->author_two_id);
        $response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$episode->id)
        );

        $this->assertSame(403, $response->get_status());
        $this->assertSame('publish', get_post_status($episode->post_id));
    }

    public function testOwnerAndEditorCanTrashEpisodes(): void
    {
        $owned_episode = $this->createEpisode('publish', $this->author_one_id);
        $foreign_episode = $this->createEpisode('publish', $this->author_two_id);

        wp_set_current_user($this->author_one_id);
        $owner_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$owned_episode->id)
        );

        wp_set_current_user($this->editor_id);
        $editor_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$foreign_episode->id)
        );

        $this->assertSame(200, $owner_response->get_status());
        $this->assertSame('trash', get_post_status($owned_episode->post_id));
        $this->assertSame(200, $editor_response->get_status());
        $this->assertSame('trash', get_post_status($foreign_episode->post_id));
    }

    public function testTagDeletionRequiresEditButNotDeletePost(): void
    {
        $limited_user_id = $this->factory->user->create(['role' => 'subscriber']);
        $limited_user = get_user_by('id', $limited_user_id);
        $limited_user->add_cap('edit_posts');
        $episode = $this->createEpisode('draft', $limited_user_id);
        $term = wp_insert_term('Authorization Test', 'post_tag');
        wp_set_object_terms($episode->post_id, [(int) $term['term_id']], 'post_tag');

        wp_set_current_user($limited_user_id);

        $tag_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$episode->id.'/tags')
        );
        $episode_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/'.$episode->id)
        );

        $this->assertSame(200, $tag_response->get_status());
        $this->assertSame([], wp_get_object_terms($episode->post_id, 'post_tag', ['fields' => 'ids']));
        $this->assertSame(403, $episode_response->get_status());
        $this->assertSame('draft', get_post_status($episode->post_id));
    }

    public function testBuildSlugRequiresEpisodeEditAccess(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        wp_set_current_user($this->author_two_id);
        $response = $this->server->dispatch(
            new WP_REST_Request('GET', '/podlove/v2/episodes/'.$episode->id.'/build_slug')
        );

        $this->assertSame(403, $response->get_status());
    }

    public function testMissingMutationTargetsReturnNotFound(): void
    {
        wp_set_current_user($this->editor_id);

        $update_response = $this->server->dispatch(
            new WP_REST_Request('PUT', '/podlove/v2/episodes/999999')
        );
        $delete_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/999999')
        );

        $this->assertSame(404, $update_response->get_status());
        $this->assertSame(404, $delete_response->get_status());
    }

    private function dispatchUpdate(
        string $route,
        Episode $episode,
        string $parameter,
        string $value
    ): WP_REST_Response {
        $request = new WP_REST_Request('PUT', $route.$episode->id);
        $request->set_param($parameter, $value);

        return $this->server->dispatch($request);
    }

    private function createEpisode(string $status, int $author_id): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => ucfirst($status).' Episode',
            'post_type' => 'podcast',
            'post_status' => $status,
            'post_author' => $author_id,
        ]);
        $episode = Episode::find_or_create_by_post_id($post_id);
        $episode->subtitle = 'Original subtitle';
        $episode->soundbite_title = 'Original soundbite';
        $episode->save();

        return $episode;
    }
}
