<?php

use Podlove\Custom_Guid;
use Ramsey\Uuid\Uuid;

/**
 * @internal
 *
 * @coversNothing
 */
class CustomGuidDuplicateTest extends WP_UnitTestCase
{
    public function testStoredUuidObjectIsReturnedAsStringAndDetectedAsDuplicate(): void
    {
        $episode_factory = new EpisodeFactory($this->factory);
        $first_episode = $episode_factory->create();
        $second_episode = $episode_factory->create();
        $guid = Uuid::uuid4();

        update_post_meta($first_episode->post_id, '_podlove_guid', $guid);
        update_post_meta($second_episode->post_id, '_podlove_guid', (string) $guid);

        $this->assertIsObject(get_post_meta($first_episode->post_id, '_podlove_guid', true));
        $this->assertSame((string) $guid, get_the_guid($first_episode->post_id));
        $duplicates = Custom_Guid::find_duplicate_guids();
        $this->assertCount(1, $duplicates);
        $this->assertArrayHasKey((string) $guid, $duplicates);
        $this->assertEqualsCanonicalizing(
            [$first_episode->post_id, $second_episode->post_id],
            $duplicates[(string) $guid]
        );
    }

    public function testDuplicateCheckAcceptsUuidObjectFromLaterGuidFilter(): void
    {
        $episode_factory = new EpisodeFactory($this->factory);
        $first_episode = $episode_factory->create();
        $second_episode = $episode_factory->create();
        $guid = Uuid::uuid4();

        update_post_meta($first_episode->post_id, '_podlove_guid', (string) $guid);
        update_post_meta($second_episode->post_id, '_podlove_guid', (string) $guid);

        $return_uuid_object = function ($value, $post_id) use ($first_episode, $guid) {
            return $post_id === $first_episode->post_id ? $guid : $value;
        };
        add_filter('get_the_guid', $return_uuid_object, 101, 2);

        try {
            $duplicates = Custom_Guid::find_duplicate_guids();
        } finally {
            remove_filter('get_the_guid', $return_uuid_object, 101);
        }

        $this->assertCount(1, $duplicates);
        $this->assertArrayHasKey((string) $guid, $duplicates);
        $this->assertEqualsCanonicalizing(
            [$first_episode->post_id, $second_episode->post_id],
            $duplicates[(string) $guid]
        );
    }
}
