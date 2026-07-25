<?php

use Podlove\Api\EpisodeMutationAccess;
use Podlove\Model\Episode;

/**
 * @internal
 *
 * @coversNothing
 */
class EpisodeMutationAccessTest extends WP_UnitTestCase
{
    private $author_one_id;
    private $author_two_id;
    private $editor_id;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_test_reset_podcast_episodes();

        $this->author_one_id = $this->factory->user->create(['role' => 'author']);
        $this->author_two_id = $this->factory->user->create(['role' => 'author']);
        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
    }

    public function tearDown(): void
    {
        podlove_test_reset_podcast_episodes();
        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testResolvesEpisodeAndPostIdentifiers(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        $by_episode = EpisodeMutationAccess::resolve($episode->id);
        $by_post = EpisodeMutationAccess::resolve_by_post_id($episode->post_id);

        $this->assertSame((int) $episode->id, (int) $by_episode['episode']->id);
        $this->assertSame((int) $episode->post_id, $by_episode['post']->ID);
        $this->assertSame((int) $episode->id, (int) $by_post['episode']->id);
        $this->assertSame((int) $episode->post_id, $by_post['post']->ID);
    }

    public function testRejectsMissingEpisodePostAndWrongPostType(): void
    {
        $missing_post_episode = new Episode();
        $missing_post_episode->post_id = 999999;
        $missing_post_episode->save();

        $post_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_status' => 'draft',
            'post_author' => $this->author_one_id,
        ]);
        $wrong_type_episode = new Episode();
        $wrong_type_episode->post_id = $post_id;
        $wrong_type_episode->save();

        $this->assertNull(EpisodeMutationAccess::resolve(999999));
        $this->assertNull(EpisodeMutationAccess::resolve($missing_post_episode->id));
        $this->assertNull(EpisodeMutationAccess::resolve($wrong_type_episode->id));
        $this->assertNull(EpisodeMutationAccess::resolve_by_post_id($post_id));
    }

    public function testPostResolutionDoesNotCreateEpisodeRows(): void
    {
        $post_id = $this->factory->post->create([
            'post_type' => 'podcast',
            'post_status' => 'draft',
            'post_author' => $this->author_one_id,
        ]);
        $auto_created_episode = Episode::find_one_by_post_id($post_id);
        $auto_created_episode->delete();
        $episode_count = Episode::count();

        $this->assertNull(EpisodeMutationAccess::resolve_by_post_id($post_id));
        $this->assertSame($episode_count, Episode::count());
    }

    public function testEditAccessUsesPostOwnership(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        wp_set_current_user($this->author_one_id);
        $this->assertTrue(EpisodeMutationAccess::rest_check_edit($episode->id));
        $this->assertTrue(EpisodeMutationAccess::rest_check_edit_by_post_id($episode->post_id));

        wp_set_current_user($this->author_two_id);
        $error = EpisodeMutationAccess::rest_check_edit($episode->id);
        $this->assertWPError($error);
        $this->assertSame(403, $error->get_error_data()['status']);

        wp_set_current_user($this->editor_id);
        $this->assertTrue(EpisodeMutationAccess::rest_check_edit($episode->id));
    }

    public function testAnonymousMutationReturnsUnauthorized(): void
    {
        $episode = $this->createEpisode('draft', $this->author_one_id);

        wp_set_current_user(0);
        $error = EpisodeMutationAccess::rest_check_edit($episode->id);

        $this->assertWPError($error);
        $this->assertSame(401, $error->get_error_data()['status']);
    }

    public function testDeleteAccessUsesDeletePostCapability(): void
    {
        $limited_user_id = $this->factory->user->create(['role' => 'subscriber']);
        $limited_user = get_user_by('id', $limited_user_id);
        $limited_user->add_cap('edit_posts');
        $episode = $this->createEpisode('draft', $limited_user_id);

        wp_set_current_user($limited_user_id);

        $this->assertTrue(EpisodeMutationAccess::rest_check_edit($episode->id));

        $error = EpisodeMutationAccess::rest_check_delete($episode->id);
        $this->assertWPError($error);
        $this->assertSame(403, $error->get_error_data()['status']);
    }

    public function testMissingMutationTargetReturnsNotFound(): void
    {
        wp_set_current_user($this->editor_id);

        $error = EpisodeMutationAccess::rest_check_edit(999999);

        $this->assertWPError($error);
        $this->assertSame(404, $error->get_error_data()['status']);
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
}
