<?php

use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use Podlove\Modules\Transcripts\Model\Transcript;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;

/**
 * @internal
 *
 * @coversNothing
 */
class EpisodeChildMutationAuthorizationTest extends WP_UnitTestCase
{
    private $server;
    private $author_one_id;
    private $author_two_id;
    private $editor_id;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_test_reset_podcast_episodes();

        podlove_test_activate_module('contributors', \Podlove\Modules\Contributors\Contributors::class);
        podlove_test_activate_module('related_episodes', \Podlove\Modules\RelatedEpisodes\Related_Episodes::class);
        podlove_test_activate_module('transcripts', \Podlove\Modules\Transcripts\Transcripts::class);

        Contributor::build();
        EpisodeContribution::build();
        EpisodeRelation::build();
        Transcript::build();
        VoiceAssignment::build();

        EpisodeContribution::delete_all();
        EpisodeRelation::delete_all();
        Transcript::delete_all();
        VoiceAssignment::delete_all();

        global $wp_rest_server;
        $wp_rest_server = null;
        $this->server = rest_get_server();

        $this->author_one_id = $this->factory->user->create(['role' => 'author']);
        $this->author_two_id = $this->factory->user->create(['role' => 'author']);
        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
    }

    public function tearDown(): void
    {
        EpisodeContribution::delete_all();
        EpisodeRelation::delete_all();
        Transcript::delete_all();
        VoiceAssignment::delete_all();
        podlove_test_reset_podcast_episodes();
        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testChapterMutationUsesEpisodeOwnership(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $episode->chapters = '00:00:00.000 Original';
        $episode->save();

        $request = $this->chapterRequest($episode, 'Cross-author chapter');
        wp_set_current_user($this->author_two_id);
        $denied_response = $this->server->dispatch($request);

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame('00:00:00.000 Original', Episode::find_by_id($episode->id)->chapters);

        wp_set_current_user($this->author_one_id);
        $allowed_response = $this->server->dispatch($this->chapterRequest($episode, 'Owner chapter'));

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertStringContainsString('Owner chapter', Episode::find_by_id($episode->id)->chapters);
    }

    public function testContributionCollectionMutationUsesEpisodeOwnership(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $route = '/podlove/v2/episodes/'.$episode->id.'/contributions';

        wp_set_current_user($this->author_two_id);
        $denied_response = $this->server->dispatch(new WP_REST_Request('POST', $route));

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame(0, EpisodeContribution::count());

        wp_set_current_user($this->author_one_id);
        $allowed_response = $this->server->dispatch(new WP_REST_Request('POST', $route));

        $this->assertSame(201, $allowed_response->get_status());
        $this->assertSame(1, EpisodeContribution::count());
    }

    public function testContributionChildMutationHidesForeignAndMissingRows(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $contribution = new EpisodeContribution();
        $contribution->episode_id = $episode->id;
        $contribution->comment = 'Original';
        $contribution->save();

        wp_set_current_user($this->author_two_id);
        $foreign_request = new WP_REST_Request(
            'PUT',
            '/podlove/v2/episodes/contributions/'.$contribution->id
        );
        $foreign_request->set_param('comment', 'Foreign update');
        $foreign_response = $this->server->dispatch($foreign_request);
        $missing_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/contributions/999999')
        );

        $this->assertSame(404, $foreign_response->get_status());
        $this->assertSame(404, $missing_response->get_status());
        $this->assertSame('Original', EpisodeContribution::find_by_id($contribution->id)->comment);

        wp_set_current_user($this->editor_id);
        $editor_request = new WP_REST_Request(
            'PUT',
            '/podlove/v2/episodes/contributions/'.$contribution->id
        );
        $editor_request->set_param('comment', 'Editor update');
        $editor_response = $this->server->dispatch($editor_request);

        $this->assertSame(200, $editor_response->get_status());
        $this->assertSame('Editor update', EpisodeContribution::find_by_id($contribution->id)->comment);
    }

    public function testRelationCreationRequiresEditAccessToBothEpisodes(): void
    {
        $owned_episode = $this->createEpisode('draft', $this->author_one_id);
        $foreign_episode = $this->createEpisode('draft', $this->author_two_id);

        wp_set_current_user($this->author_one_id);
        $denied_response = $this->server->dispatch(
            $this->relationCreateRequest($owned_episode, $foreign_episode)
        );

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame(0, EpisodeRelation::count());

        wp_set_current_user($this->editor_id);
        $allowed_response = $this->server->dispatch(
            $this->relationCreateRequest($owned_episode, $foreign_episode)
        );

        $this->assertSame(201, $allowed_response->get_status());
        $this->assertSame(1, EpisodeRelation::count());
    }

    public function testRelationChildMutationHidesForeignAndMissingRows(): void
    {
        $left = $this->createEpisode('draft', $this->author_one_id);
        $right = $this->createEpisode('draft', $this->author_two_id);
        $relation = $this->createRelation($left, $right);

        wp_set_current_user($this->author_one_id);
        $foreign_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/related/'.$relation->id)
        );
        $missing_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/related/999999')
        );

        $this->assertSame(404, $foreign_response->get_status());
        $this->assertSame(404, $missing_response->get_status());
        $this->assertNotNull(EpisodeRelation::find_by_id($relation->id));

        wp_set_current_user($this->editor_id);
        $editor_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/episodes/related/'.$relation->id)
        );

        $this->assertSame(200, $editor_response->get_status());
        $this->assertNull(EpisodeRelation::find_by_id($relation->id));
    }

    public function testDeniedRelationReplacementPreservesExistingRows(): void
    {
        $left = $this->createEpisode('draft', $this->author_one_id);
        $existing_right = $this->createEpisode('draft', $this->author_one_id);
        $foreign_right = $this->createEpisode('draft', $this->author_two_id);
        $existing_relation = $this->createRelation($left, $existing_right);

        wp_set_current_user($this->author_one_id);
        $request = new WP_REST_Request('PUT', '/podlove/v2/episodes/'.$left->id.'/related');
        $request->set_param('related', [$foreign_right->id]);
        $response = $this->server->dispatch($request);

        $this->assertSame(403, $response->get_status());
        $this->assertSame(1, EpisodeRelation::count());
        $this->assertNotNull(EpisodeRelation::find_by_id($existing_relation->id));
    }

    public function testV1VoiceMutationUsesPostOwnership(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $route = '/podlove/v1/transcripts/'.$episode->post_id.'/voices';

        wp_set_current_user($this->author_two_id);
        $denied_response = $this->server->dispatch($this->voiceRequest($route));

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame(0, VoiceAssignment::count());

        wp_set_current_user($this->author_one_id);
        $allowed_response = $this->server->dispatch($this->voiceRequest($route));

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertSame(1, VoiceAssignment::count());
    }

    public function testV2TranscriptMutationUsesEpisodeOwnership(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $route = '/podlove/v2/transcripts/'.$episode->id;

        wp_set_current_user($this->author_two_id);
        $denied_response = $this->server->dispatch($this->transcriptRequest($route));

        $this->assertSame(403, $denied_response->get_status());
        $this->assertSame(0, Transcript::count());

        wp_set_current_user($this->author_one_id);
        $allowed_response = $this->server->dispatch($this->transcriptRequest($route));

        $this->assertSame(200, $allowed_response->get_status());
        $this->assertSame(1, Transcript::count());
    }

    public function testTranscriptParagraphMutationHidesForeignAndMissingRows(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);
        $transcript = $this->createTranscript($episode, 'Original');

        wp_set_current_user($this->author_two_id);
        $foreign_request = new WP_REST_Request(
            'PUT',
            '/podlove/v2/transcripts/paragraphs/'.$transcript->id
        );
        $foreign_request->set_param('text', 'Foreign update');
        $foreign_response = $this->server->dispatch($foreign_request);
        $missing_response = $this->server->dispatch(
            new WP_REST_Request('DELETE', '/podlove/v2/transcripts/paragraphs/999999')
        );

        $this->assertSame(404, $foreign_response->get_status());
        $this->assertSame(404, $missing_response->get_status());
        $this->assertSame('Original', Transcript::find_by_id($transcript->id)->content);

        wp_set_current_user($this->editor_id);
        $editor_request = new WP_REST_Request(
            'PUT',
            '/podlove/v2/transcripts/paragraphs/'.$transcript->id
        );
        $editor_request->set_param('text', 'Editor update');
        $editor_response = $this->server->dispatch($editor_request);

        $this->assertSame(200, $editor_response->get_status());
        $this->assertSame('Editor update', Transcript::find_by_id($transcript->id)->content);
    }

    private function chapterRequest(Episode $episode, string $title): WP_REST_Request
    {
        $request = new WP_REST_Request('PUT', '/podlove/v2/chapters/'.$episode->id);
        $request->set_param('chapters', [
            [
                'start' => '00:00:00.000',
                'title' => $title,
            ],
        ]);

        return $request;
    }

    private function relationCreateRequest(Episode $left, Episode $right): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', '/podlove/v2/episodes/related');
        $request->set_param('episode_id', $left->id);
        $request->set_param('related_episode_id', $right->id);

        return $request;
    }

    private function voiceRequest(string $route): WP_REST_Request
    {
        $request = new WP_REST_Request('PUT', $route);
        $request->set_param('transcript_voice', ['speaker' => 0]);

        return $request;
    }

    private function transcriptRequest(string $route): WP_REST_Request
    {
        $request = new WP_REST_Request('PUT', $route);
        $request->set_param(
            'content',
            "WEBVTT\n\n00:00:00.000 --> 00:00:01.000\nHello\n"
        );

        return $request;
    }

    private function createEpisode(string $status, int $author_id): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => ucfirst($status).' Episode',
            'post_type' => 'podcast',
            'post_status' => $status,
            'post_author' => $author_id,
        ]);

        return Episode::find_or_create_by_post_id($post_id);
    }

    private function createRelation(Episode $left, Episode $right): EpisodeRelation
    {
        $relation = new EpisodeRelation();
        $relation->left_episode_id = $left->id;
        $relation->right_episode_id = $right->id;
        $relation->save();

        return $relation;
    }

    private function createTranscript(Episode $episode, string $content): Transcript
    {
        $transcript = new Transcript();
        $transcript->episode_id = $episode->id;
        $transcript->start = 0;
        $transcript->end = 1000;
        $transcript->content = $content;
        $transcript->save();

        return $transcript;
    }
}
