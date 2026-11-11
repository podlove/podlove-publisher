<?php

use Podlove\Model\AssetAssignment;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\FileType;
use Podlove\Model\MediaFile;
use Podlove\Model\Podcast;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Transcripts\Model\Transcript;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;
use Podlove\Modules\Transcripts\REST_API;
use Podlove\Modules\Transcripts\TranscriptMutation;
use Podlove\Modules\Transcripts\Transcripts;
use Podlove\Modules\Transcripts\WP_REST_PodloveTranscripts_Controller;

/**
 * @internal
 *
 * @coversNothing
 */
class TranscriptImportTest extends WP_UnitTestCase
{
    private $original_prefix;
    private $episode;
    private $contributor;
    private $asset;
    private $media_file;
    private $file;
    private $content = "WEBVTT\n\n00:00:00.000 --> 00:00:01.000\n<v speaker>Hello\n\n"
        ."00:00:01.000 --> 00:00:02.000\n<v speaker>World\n";

    public function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $this->original_prefix = $wpdb->prefix;
        // Shared plugin tables let independent PHP workers exercise actual contention.
        // Keep the core table properties (posts/options/postmeta) on the WP test tables.
        $wpdb->prefix = 'ti_';
        remove_filter('query', [$this, '_create_temporary_tables']);
        remove_filter('query', [$this, '_drop_temporary_tables']);
        podlove_setup_database_tables();
        podlove_setup_file_types();
        \Podlove\Modules\Contributors\Contributors::instance()->was_activated('contributors');
        Transcript::build();
        VoiceAssignment::build();
        Transcript::delete_all();
        VoiceAssignment::delete_all();
        podlove_test_reset_podcast_episodes();

        $this->episode = (new EpisodeFactory($this->factory))->create();
        $this->contributor = new Contributor();
        $this->contributor->identifier = 'speaker';
        $this->contributor->publicname = 'Speaker';
        $this->contributor->save();

        $this->asset = new EpisodeAsset();
        $this->asset->title = 'Transcript';
        $this->asset->file_type_id = FileType::find_one_by_property('extension', 'vtt')->id;
        $this->asset->suffix = '';
        $this->asset->save();
        $assignment = AssetAssignment::get_instance();
        $assignment->transcript = $this->asset->id;
        $assignment->save();

        $uploads = wp_upload_dir();
        $podcast = Podcast::get();
        $podcast->media_file_base_uri = trailingslashit($uploads['baseurl']);
        $podcast->save();
        $this->file = trailingslashit($uploads['basedir']).$this->episode->slug.'.vtt';
        file_put_contents($this->file, $this->content);
        $this->media_file = new MediaFile();
        $this->media_file->episode_id = $this->episode->id;
        $this->media_file->episode_asset_id = $this->asset->id;
        $this->media_file->active = true;
        $this->media_file->save(false);
    }

    public function tearDown(): void
    {
        wp_delete_file($this->file);
        Transcript::delete_all();
        VoiceAssignment::delete_all();
        $this->media_file->delete();
        $this->asset->delete();
        $this->contributor->delete();
        delete_option('podlove_asset_assignment');
        delete_option('podlove_podcast');
        podlove_test_reset_podcast_episodes();
        global $wpdb;
        $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix).'%'));
        foreach ($tables as $table) {
            $wpdb->query('DROP TABLE `'.$table.'`');
        }
        $wpdb->prefix = $this->original_prefix;
        parent::tearDown();
    }

    public function testRepeatedImportsReplaceCuesAndPreserveVoiceAssignments(): void
    {
        $this->assertTrue(Transcripts::parse_and_import_webvtt($this->episode, $this->content));
        $assignment = VoiceAssignment::first();
        $assignment->contributor_id = 0;
        $assignment->save();
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
        $this->assertSame(2, Transcript::count());
        $this->assertSame(1, VoiceAssignment::count());
        $this->assertSame(0, (int) VoiceAssignment::first()->contributor_id);
        $this->assertSame(['Hello', 'World'], array_column(Transcript::get_transcript($this->episode->id), 'content'));
    }

    public function testAutomaticVerificationSkipsUnchangedContentButImportsChangedContent(): void
    {
        $module = Transcripts::instance();
        $module->handle_changed_media_file($this->media_file->id);
        $ids = $this->cue_ids();
        wp_cache_set($this->episode->post_id, ['_podlove_transcript_asset_hash' => ['stale hash']], 'post_meta');
        $module->handle_changed_media_file($this->media_file->id);
        $this->assertSame($ids, $this->cue_ids());
        file_put_contents($this->file, str_replace('Hello', 'Changed', $this->content));
        $module->handle_changed_media_file($this->media_file->id);
        $this->assertNotSame($ids, $this->cue_ids());
        $this->assertSame('Changed', Transcript::first()->content);
        $this->assertSame(1, VoiceAssignment::count());
    }

    public function testExplicitAssetImportReplacesUnchangedContent(): void
    {
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
        $ids = $this->cue_ids();
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
        $this->assertNotSame($ids, $this->cue_ids());
        $this->assertSame(2, Transcript::count());
    }

    public function testVerificationOfUnassignedVttDoesNotImportAssignedAsset(): void
    {
        $other = new EpisodeAsset();
        $other->title = 'Other VTT';
        $other->file_type_id = $this->asset->file_type_id;
        $other->save();
        $file = new MediaFile();
        $file->episode_id = $this->episode->id;
        $file->episode_asset_id = $other->id;
        $file->save();

        try {
            Transcripts::instance()->handle_changed_media_file($file->id);
            $this->assertSame(0, Transcript::count());
        } finally {
            $file->delete();
            $other->delete();
        }
    }

    public function testUploadResetsAssignmentsAndInvalidatesAssetHash(): void
    {
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
        $assignment = VoiceAssignment::first();
        $assignment->contributor_id = 0;
        $assignment->save();
        $response = $this->upload(str_replace('Hello', 'Uploaded', $this->content));
        $this->assertNotWPError($response);
        $this->assertSame('Uploaded', Transcript::first()->content);
        $this->assertSame((int) $this->contributor->id, (int) VoiceAssignment::first()->contributor_id);
        $this->assertSame('', get_post_meta($this->episode->post_id, '_podlove_transcript_asset_hash', true));
        Transcripts::instance()->handle_changed_media_file($this->media_file->id);
        $this->assertSame('Hello', Transcript::first()->content);
    }

    public function testInvalidAssetPreservesExistingTranscriptAndHash(): void
    {
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
        $ids = $this->cue_ids();
        $hash = get_post_meta($this->episode->post_id, '_podlove_transcript_asset_hash', true);
        file_put_contents($this->file, 'not a webvtt file');
        $result = Transcripts::transcript_import_from_asset($this->episode);
        $this->assertSame('podlove_transcript_parse_failed', $result['code']);
        $this->assertSame($ids, $this->cue_ids());
        $this->assertSame($hash, get_post_meta($this->episode->post_id, '_podlove_transcript_asset_hash', true));
    }

    public function testFailedCueWriteRollsBackCuesAndVoicesAndAllowsRetry(): void
    {
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
        $ids = $this->cue_ids();
        $voice_ids = array_column(VoiceAssignment::all(), 'id');
        $hash = get_post_meta($this->episode->post_id, '_podlove_transcript_asset_hash', true);
        $inserts = 0;
        $fail_second_insert = function ($query) use (&$inserts) {
            if (strpos($query, 'INSERT INTO '.Transcript::table_name()) === 0 && ++$inserts === 2) {
                return 'INSERT INTO podlove_missing_transcript_test_table VALUES (1)';
            }

            return $query;
        };
        global $wpdb;
        $previous = $wpdb->suppress_errors(true);
        add_filter('query', $fail_second_insert);

        try {
            $result = $this->upload(str_replace('Hello', 'Changed', $this->content));
        } finally {
            remove_filter('query', $fail_second_insert);
            $wpdb->suppress_errors($previous);
        }
        $this->assertWPError($result);
        $this->assertSame($ids, $this->cue_ids());
        $this->assertSame($voice_ids, array_column(VoiceAssignment::all(), 'id'));
        $this->assertSame($hash, get_post_meta($this->episode->post_id, '_podlove_transcript_asset_hash', true));
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
    }

    public function testFailedVoiceWriteRollsBackCuesAndHash(): void
    {
        $fail_voice = function ($query) {
            return strpos($query, 'INSERT INTO '.VoiceAssignment::table_name()) === 0
                ? 'INSERT INTO podlove_missing_transcript_test_table VALUES (1)' : $query;
        };
        global $wpdb;
        $previous = $wpdb->suppress_errors(true);
        add_filter('query', $fail_voice);

        try {
            $result = Transcripts::transcript_import_from_asset($this->episode);
        } finally {
            remove_filter('query', $fail_voice);
            $wpdb->suppress_errors($previous);
        }
        $this->assertSame('podlove_transcript_import_failed', $result['code']);
        $this->assertSame(0, Transcript::count());
        $this->assertSame(0, VoiceAssignment::count());
        $this->assertSame('', get_post_meta($this->episode->post_id, '_podlove_transcript_asset_hash', true));
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
    }

    public function testDuplicateAssignmentsDoNotMultiplyReadsOrPagination(): void
    {
        $this->assertTrue(Transcripts::parse_and_import_webvtt($this->episode, $this->content));
        foreach ([0, $this->contributor->id] as $cid) {
            $assignment = new VoiceAssignment();
            $assignment->episode_id = $this->episode->id;
            $assignment->voice = 'speaker';
            $assignment->contributor_id = $cid;
            $assignment->save();
        }
        $this->assertCount(2, Transcript::get_transcript($this->episode->id));
        $this->assertSame(2, (int) Transcript::get_transcript_count($this->episode->id));
        $page = Transcript::get_transcript_offset_limit($this->episode->id, 1, 1);
        $this->assertCount(1, $page);
        $this->assertSame('World', $page[0]->content);
        $voices = Transcript::get_voices_for_episode_id($this->episode->id);
        $this->assertCount(1, $voices);
        $this->assertSame((int) $this->contributor->id, (int) $voices[0]->contributor_id);
        $groups = Transcript::prepare_transcript(Transcript::get_transcript($this->episode->id), 'grouped');
        $this->assertCount(1, $groups);
        $this->assertCount(2, $groups[0]['items']);
        $this->assertSame(3, VoiceAssignment::count());
    }

    public function testDuplicateStartTimesArePreservedAsSeparateCues(): void
    {
        $content = str_replace('00:00:01.000 --> 00:00:02.000', '00:00:00.000 --> 00:00:02.000', $this->content);
        $this->assertTrue(Transcripts::parse_and_import_webvtt($this->episode, $content));
        $this->assertCount(2, Transcript::get_transcript($this->episode->id));
    }

    public function testVoiceUpdatesUseSameEpisodeLockAsImports(): void
    {
        $this->assertTrue(Transcripts::parse_and_import_webvtt($this->episode, $this->content));
        global $wpdb;
        $other = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $lock_name = null;
        $capture_lock = function ($query) use (&$lock_name) {
            if (preg_match("/GET_LOCK\\('([^']+)', 10\\)/", $query, $match)) {
                $lock_name = $match[1];
            }

            return $query;
        };
        add_filter('query', $capture_lock);
        TranscriptMutation::run($this->episode->id, function () { return true; });
        remove_filter('query', $capture_lock);
        $this->assertNotNull($lock_name);
        $this->assertSame('1', (string) $other->get_var($other->prepare('SELECT GET_LOCK(%s, 0)', $lock_name)));
        $no_wait = function ($query) { return str_replace(', 10)', ', 0)', $query); };
        add_filter('query', $no_wait);

        try {
            $request = new WP_REST_Request('PUT');
            $request->set_param('id', $this->episode->id);
            $request->set_param('voice', 'speaker');
            $request->set_param('contributor_id', 0);
            $v2 = (new WP_REST_PodloveTranscripts_Controller())->update_item_voices($request);
            $this->assertWPError($v2);
            $request->set_param('id', $this->episode->post_id);
            $request->set_param('transcript_voice', ['speaker' => 0]);
            $v1 = (new REST_API())->update_voices($request);
            $this->assertWPError($v1);
            $this->assertWPError($this->upload($this->content));
            $asset_result = Transcripts::transcript_import_from_asset($this->episode);
            $this->assertSame('podlove_transcript_busy', $asset_result['code']);
            $this->assertSame(1, VoiceAssignment::count());
            $this->assertSame((int) $this->contributor->id, (int) VoiceAssignment::first()->contributor_id);
        } finally {
            remove_filter('query', $no_wait);
            $other->get_var($other->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
            $other->close();
        }
        $this->assertTrue(Transcripts::transcript_import_from_asset($this->episode));
    }

    public function testConcurrentLegacyUploadAndAssetImportsAreSerialized(): void
    {
        if (!function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is required for concurrent import workers.');
        }
        global $wpdb;
        $previous_content = "WEBVTT\n\n00:00:00.000 --> 00:00:01.000\n<v speaker>Before\n";
        $this->assertTrue(Transcripts::parse_and_import_webvtt($this->episode, $previous_content));
        // Independent connections must see the fixture before starting imports.
        $wpdb->query('COMMIT');
        $signal = tempnam(sys_get_temp_dir(), 'transcript-signal-');
        unlink($signal);
        $release = $signal.'-release';
        $workers = [];

        try {
            $workers[] = $this->start_worker('legacy', $signal, $release);
            $this->wait_until(function () use ($signal, $workers) {
                if (!proc_get_status($workers[0][0])['running']) {
                    $this->fail(stream_get_contents($workers[0][1][1]).stream_get_contents($workers[0][1][2]));
                }

                return file_exists($signal);
            });
            $workers[] = $this->start_worker('upload', $signal, $release);
            $workers[] = $this->start_worker('asset', $signal, $release);
            $this->wait_until(function () use ($wpdb, $workers) {
                $waiters = array_filter($wpdb->get_results('SHOW PROCESSLIST'), function ($process) {
                    return strpos($process->Info ?? '', 'SELECT GET_LOCK(') === 0;
                });

                return count($waiters) >= 2
                    || (!proc_get_status($workers[1][0])['running'] && !proc_get_status($workers[2][0])['running']);
            });
            // Readers must continue seeing the previous committed state, not partial cues.
            $visible_content = array_column(Transcript::get_transcript($this->episode->id), 'content');
            touch($release);
            foreach ($workers as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $output);
            }
            $workers = [];
            // WP tests disable autocommit; discard the reader's pre-commit snapshot.
            $wpdb->query('COMMIT');
            $this->assertSame(2, Transcript::count());
            $this->assertSame(['Before'], $visible_content);
            $this->assertSame(1, VoiceAssignment::count());
            $this->assertSame(
                ['Hello', 'World'],
                array_column(Transcript::get_transcript($this->episode->id), 'content')
            );
        } finally {
            touch($release);
            foreach ($workers as [$process, $pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($process);
                }
            }
            wp_delete_file($signal);
            wp_delete_file($release);
            wp_cache_flush();
        }
    }

    private function cue_ids(): array
    {
        return array_column(Transcript::get_transcript_offset_limit($this->episode->id, 0, 100), 'id');
    }

    private function upload($content)
    {
        $request = new WP_REST_Request('PUT');
        $request->set_param('id', $this->episode->id);
        $request->set_param('content', $content);

        return (new WP_REST_PodloveTranscripts_Controller())->update_item($request);
    }

    private function start_worker($mode, $signal, $release): array
    {
        global $wpdb;
        $process = proc_open([
            PHP_BINARY, __DIR__.'/../fixtures/transcript_import_worker.php', ABSPATH, DB_NAME,
            $wpdb->prefix, (string) $this->episode->id, $this->file, $mode, $signal, $release
        ], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        fclose($pipes[0]);

        return [$process, $pipes];
    }

    private function wait_until(callable $condition): void
    {
        $deadline = microtime(true) + 5;
        do {
            if ($condition()) {
                return;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        $this->fail('Timed out waiting for concurrent import worker.');
    }
}
