<?php

use Podlove\Modules\Locations\Locations;
use Podlove\Modules\Locations\Model\Location;
use Podlove\Modules\Locations\Settings\Podcast_Settings_Tab;

class LocationsTest extends WP_UnitTestCase
{
    /**
     * @var EpisodeFactory
     */
    private $episode_factory;

    public function setUp(): void
    {
        parent::setUp();

        podlove_test_reset_podcast_episodes();
        delete_option('podlove_episode_location_podcast');

        podlove_test_activate_module('locations', Locations::class);

        $this->episode_factory = new EpisodeFactory($this->factory);
    }

    public function tearDown(): void
    {
        podlove_test_truncate_locations_table();
        delete_option('podlove_episode_location_podcast');
        podlove_test_reset_podcast_episodes();
        parent::tearDown();
    }

    public function testTableExistsAndModuleCanListLocationsAfterActivation()
    {
        $this->assertIsArray(Location::all());
        $this->assertCount(0, Location::all());
    }

    public function testSavesSubjectAndCreatorLocationsForEpisode()
    {
        $episode = $this->episode_factory->create();

        $subject = new Location();
        $subject->episode_id = $episode->id;
        $subject->rel = 'subject';
        $subject->location_name = 'Berlin';
        $subject->location_lat = '52.52000000';
        $subject->location_lng = '13.40500000';
        $subject->save();

        $creator = new Location();
        $creator->episode_id = $episode->id;
        $creator->rel = 'creator';
        $creator->location_name = 'Home Studio';
        $creator->location_country = 'DE';
        $creator->save();

        $loadedSubject = Location::find_by_episode_id_and_rel($episode->id, 'subject');
        $this->assertNotNull($loadedSubject);
        $this->assertEquals('Berlin', $loadedSubject->location_name);
        $this->assertEquals('52.52000000', $loadedSubject->location_lat);

        $loadedCreator = Location::find_by_episode_id_and_rel($episode->id, 'creator');
        $this->assertNotNull($loadedCreator);
        $this->assertEquals('Home Studio', $loadedCreator->location_name);
        $this->assertEquals('DE', $loadedCreator->location_country);
    }

    public function testUpdatingSubjectLocationKeepsSingleRow()
    {
        global $wpdb;

        $episode = $this->episode_factory->create();

        $subject = new Location();
        $subject->episode_id = $episode->id;
        $subject->rel = 'subject';
        $subject->location_name = 'First';
        $subject->save();

        $id = $subject->id;
        $this->assertGreaterThan(0, $id);

        $loaded = Location::find_by_episode_id_and_rel($episode->id, 'subject');
        $this->assertNotNull($loaded);
        $loaded->location_name = 'Second';
        $loaded->save();

        $table = Location::table_name();
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE episode_id = %d AND rel = %s",
            $episode->id,
            'subject'
        ));
        $this->assertEquals(1, $count);

        $again = Location::find_by_episode_id_and_rel($episode->id, 'subject');
        $this->assertEquals('Second', $again->location_name);
        $this->assertEquals($id, $again->id);
    }

    public function testPodloveDeleteEpisodeHookRemovesAllLocationsForEpisode()
    {
        $episode = $this->episode_factory->create();

        $subject = new Location();
        $subject->episode_id = $episode->id;
        $subject->rel = 'subject';
        $subject->location_name = 'X';
        $subject->save();

        $creator = new Location();
        $creator->episode_id = $episode->id;
        $creator->rel = 'creator';
        $creator->location_name = 'Y';
        $creator->save();

        do_action('podlove_delete_episode', $episode);

        $this->assertNull(Location::find_by_episode_id_and_rel($episode->id, 'subject'));
        $this->assertNull(Location::find_by_episode_id_and_rel($episode->id, 'creator'));
    }

    public function testPodcastLocationOptionIsDetectedBySettingsHelpers()
    {
        update_option('podlove_episode_location_podcast', [
            'location_name' => 'Network HQ',
            'location_lat' => '51.50000000',
            'location_lng' => '-0.12000000',
            'location_address' => '',
            'location_country' => 'GB',
            'location_osm' => '',
        ]);

        $this->assertTrue(Podcast_Settings_Tab::has_podcast_location());

        $data = Podcast_Settings_Tab::get_podcast_location();
        $this->assertEquals('Network HQ', $data['location_name']);
        $this->assertEquals('GB', $data['location_country']);
    }
}
