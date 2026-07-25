<?php

use Podlove\Api\EpisodeReadAccess;
use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use Podlove\Modules\Transcripts\Model\Transcript;

/**
 * @internal
 *
 * @coversNothing
 */
class EpisodeReadAccessTest extends WP_UnitTestCase
{
    private $server;
    private $author_one_id;
    private $author_two_id;
    private $editor_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->server = rest_get_server();

        podlove_setup_database_tables();
        podlove_test_reset_podcast_episodes();

        Transcript::build();
        Contributor::build();
        EpisodeContribution::build();
        EpisodeRelation::build();

        $this->author_one_id = $this->factory->user->create(['role' => 'author']);
        $this->author_two_id = $this->factory->user->create(['role' => 'author']);
        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
    }

    public function tearDown(): void
    {
        podlove_test_reset_podcast_episodes();
        parent::tearDown();
    }

    /**
     * @dataProvider nonPublicStatusProvider
     */
    public function testAnonymousCannotReadUnpublishedEpisodes(string $status): void
    {
        $episode = $this->createEpisode($status, $this->author_one_id);

        wp_set_current_user(0);

        $this->assertFalse(EpisodeReadAccess::can_read($episode));
        $this->assertSame(404, EpisodeReadAccess::rest_check($episode->id)->get_error_data()['status']);
    }

    public function nonPublicStatusProvider(): array
    {
        return [
            'draft' => ['draft'],
            'pending' => ['pending'],
            'future' => ['future'],
            'private' => ['private'],
            'trash' => ['trash'],
        ];
    }

    public function testPublicEpisodeIsReadableAnonymously(): void
    {
        $episode = $this->createEpisode('publish', $this->author_one_id);

        wp_set_current_user(0);

        $this->assertTrue(EpisodeReadAccess::can_read($episode));
        $this->assertTrue(EpisodeReadAccess::rest_check($episode->id));
    }

    public function testPasswordProtectedEpisodeRequiresEditAccess(): void
    {
        $episode = $this->createEpisode('publish', $this->author_one_id, 'secret');

        wp_set_current_user(0);
        $this->assertFalse(EpisodeReadAccess::can_read($episode));

        wp_set_current_user($this->author_two_id);
        $this->assertFalse(EpisodeReadAccess::can_read($episode));

        wp_set_current_user($this->author_one_id);
        $this->assertTrue(EpisodeReadAccess::can_read($episode));
    }

    public function testPreviewOriginMustMatchSiteOrigin(): void
    {
        $original_origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        try {
            $_SERVER['HTTP_ORIGIN'] = home_url();
            $this->assertTrue(EpisodeReadAccess::is_same_origin_request());

            $_SERVER['HTTP_ORIGIN'] = 'https://attacker.example';
            $this->assertFalse(EpisodeReadAccess::is_same_origin_request());
        } finally {
            if ($original_origin === null) {
                unset($_SERVER['HTTP_ORIGIN']);
            } else {
                $_SERVER['HTTP_ORIGIN'] = $original_origin;
            }
        }
    }

    public function testDraftUsesObjectSpecificReadCapability(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        wp_set_current_user($this->author_one_id);
        $this->assertTrue(EpisodeReadAccess::can_read($episode));

        wp_set_current_user($this->author_two_id);
        $this->assertFalse(EpisodeReadAccess::can_read($episode));
        $this->assertSame(403, EpisodeReadAccess::rest_check($episode->id)->get_error_data()['status']);

        wp_set_current_user($this->editor_id);
        $this->assertTrue(EpisodeReadAccess::can_read($episode));
    }

    public function testWrongPostTypeIsNotAnEpisodeReadTarget(): void
    {
        $post_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        $episode = new Episode();
        $episode->post_id = $post_id;
        $episode->save();

        wp_set_current_user($this->editor_id);

        $this->assertNull(EpisodeReadAccess::resolve($episode->id));
        $this->assertSame(404, EpisodeReadAccess::rest_check($episode->id)->get_error_data()['status']);
    }

    public function testV1AndV2SingleEpisodeRoutesDoNotExposeDrafts(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        wp_set_current_user(0);

        foreach (['/podlove/v1/episodes/', '/podlove/v2/episodes/'] as $route) {
            $response = $this->server->dispatch(new WP_REST_Request('GET', $route.$episode->id));
            $this->assertSame(404, $response->get_status());
        }
    }

    public function testInvalidV1EpisodeReturnsNotFound(): void
    {
        wp_set_current_user(0);

        $response = $this->server->dispatch(new WP_REST_Request('GET', '/podlove/v1/episodes/999999'));

        $this->assertSame(404, $response->get_status());
    }

    public function testEpisodeCollectionFiltersUnreadableDrafts(): void
    {
        $own_draft = $this->createEpisode('draft', $this->author_one_id);
        $other_draft = $this->createEpisode('draft', $this->author_two_id);
        $published = $this->createEpisode('publish', $this->author_two_id);

        wp_set_current_user($this->author_one_id);

        $request = new WP_REST_Request('GET', '/podlove/v2/episodes');
        $request->set_param('status', 'all');
        $response = $this->server->dispatch($request);
        $ids = array_map('intval', wp_list_pluck($response->get_data()['results'], 'id'));

        $this->assertSame(200, $response->get_status());
        $this->assertContains((int) $own_draft->id, $ids);
        $this->assertContains((int) $published->id, $ids);
        $this->assertNotContains((int) $other_draft->id, $ids);
    }

    public function testChapterRouteUsesEpisodeReadAccess(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        wp_set_current_user(0);

        $response = $this->server->dispatch(new WP_REST_Request('GET', '/podlove/v2/chapters/'.$episode->id));

        $this->assertSame(404, $response->get_status());
    }

    public function testPlayerShortcodeConfigurationDoesNotExposeDraft(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        wp_set_current_user(0);

        $this->assertSame([], podlove_pwp5_attributes(['post_id' => $episode->post_id]));
        $this->assertSame(
            '',
            \Podlove\Modules\PodloveWebPlayer\PlayerV4\Module::shortcode(['post_id' => $episode->post_id])
        );
    }

    public function testIndirectChildPermissionsResolveTheirEpisode(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        $transcript = new Transcript();
        $transcript->episode_id = $episode->id;
        $transcript->start = 0;
        $transcript->end = 1000;
        $transcript->content = 'private transcript';
        $transcript->save();

        $contribution = new EpisodeContribution();
        $contribution->episode_id = $episode->id;
        $contribution->save();

        wp_set_current_user(0);

        $transcript_request = new WP_REST_Request('GET', '/podlove/v2/transcripts/paragraphs/'.$transcript->id);
        $transcript_request->set_param('id', $transcript->id);
        $transcript_controller = new \Podlove\Modules\Transcripts\WP_REST_PodloveTranscripts_Controller();
        $this->assertSame(404, $transcript_controller->get_paragraph_permissions_check($transcript_request)->get_error_data()['status']);

        $contribution_request = new WP_REST_Request('GET', '/podlove/v2/episodes/contributions/'.$contribution->id);
        $contribution_request->set_param('id', $contribution->id);
        $contribution_controller = new \Podlove\Api\Episodes\WP_REST_PodloveEpisodeContributions_Controller();
        $this->assertSame(404, $contribution_controller->get_contribution_permissions_check($contribution_request)->get_error_data()['status']);
    }

    public function testRelationReadRequiresAccessToBothEpisodes(): void
    {
        $published = $this->createEpisode('publish', $this->author_one_id);
        $draft = $this->createEpisode('draft', $this->author_two_id);

        $relation = new EpisodeRelation();
        $relation->left_episode_id = $published->id;
        $relation->right_episode_id = $draft->id;
        $relation->save();

        $request = new WP_REST_Request('GET', '/podlove/v2/episodes/related/'.$relation->id);
        $request->set_param('id', $relation->id);
        $controller = new \Podlove\Api\Episodes\WP_REST_PodloveEpisodeRelated_Controller();

        wp_set_current_user(0);
        $this->assertSame(404, $controller->get_relation_permissions_check($request)->get_error_data()['status']);

        wp_set_current_user($this->editor_id);
        $this->assertTrue($controller->get_relation_permissions_check($request));
    }

    public function testRelatedEpisodeListOmitsUnreadableEpisodeTitle(): void
    {
        $published = $this->createEpisode('publish', $this->author_one_id);
        $draft = $this->createEpisode('draft', $this->author_two_id);

        $relation = new EpisodeRelation();
        $relation->left_episode_id = $published->id;
        $relation->right_episode_id = $draft->id;
        $relation->save();

        $request = new WP_REST_Request('GET', '/podlove/v2/episodes/'.$published->id.'/related');
        $request->set_param('id', $published->id);
        $controller = new \Podlove\Api\Episodes\WP_REST_PodloveEpisodeRelated_Controller();

        wp_set_current_user(0);

        $this->assertTrue($controller->get_items_permissions_check($request));
        $this->assertSame([], array_values($controller->get_items($request)->get_data()['relatedEpisodes']));
    }

    private function createEpisode(string $status, int $author_id, string $password = ''): Episode
    {
        $post_data = [
            'post_title' => ucfirst($status).' Episode',
            'post_content' => 'Sensitive episode content',
            'post_type' => 'podcast',
            'post_status' => $status,
            'post_author' => $author_id,
            'post_password' => $password,
        ];

        if ($status === 'future') {
            $post_data['post_date'] = gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS);
        }

        $post_id = wp_insert_post($post_data);

        return Episode::find_or_create_by_post_id($post_id);
    }
}
