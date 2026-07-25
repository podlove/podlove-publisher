<?php

use Podlove\Model\EpisodeAsset;
use Podlove\Model\Feed;
use Podlove\Model\FileType;
use Podlove\Model\Job;
use Podlove\Modules\Onboarding\Onboarding;
use Podlove\Settings\Analytics;

/**
 * @internal
 *
 * @coversNothing
 */
class AjaxMutationAuthorizationTest extends WP_Ajax_UnitTestCase
{
    private $administrator_id;
    private $editor_id;
    private $delegated_admin_id;
    private $author_one_id;
    private $author_two_id;
    private $file_type_id;
    private $original_request_method;

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();

        Feed::delete_all();
        EpisodeAsset::delete_all();
        Job::delete_all();

        $this->administrator_id = $this->factory->user->create(['role' => 'administrator']);
        $this->editor_id = $this->factory->user->create(['role' => 'editor']);
        $this->delegated_admin_id = $this->factory->user->create(['role' => 'editor']);
        $this->author_one_id = $this->factory->user->create(['role' => 'author']);
        $this->author_two_id = $this->factory->user->create(['role' => 'author']);

        get_user_by('id', $this->delegated_admin_id)->add_cap('manage_options');
        get_user_by('id', $this->author_one_id)->add_cap('podlove_read_analytics');
        get_user_by('id', $this->author_two_id)->add_cap('podlove_read_analytics');

        $this->original_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
        Onboarding::set_banner_hide('false');
        delete_user_meta($this->author_one_id, 'podlove_onboarding_acknowledge');
        delete_user_meta($this->author_two_id, 'podlove_onboarding_acknowledge');
        delete_option('podlove_analytics_tiles');
        delete_option('podlove_analytics_compare_avg');
    }

    public function tearDown(): void
    {
        Feed::delete_all();
        EpisodeAsset::delete_all();
        Job::delete_all();

        if ($this->file_type_id && ($file_type = FileType::find_by_id($this->file_type_id))) {
            $file_type->delete();
        }

        Onboarding::set_banner_hide('false');
        delete_option('podlove_analytics_tiles');
        delete_option('podlove_analytics_compare_avg');
        delete_option('podlove_migration_validation_cache');
        delete_option('_podlove_hide_teaser');
        delete_option('podlove_tracking_delete_head_requests');

        if ($this->original_request_method === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->original_request_method;
        }

        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testFeedAndAssetReorderRequirePostNonceAndManageOptions(): void
    {
        $feed = $this->createFeed();
        $asset = $this->createEpisodeAsset();

        foreach ([
            ['action' => 'podlove-update-feed-position', 'id_key' => 'feed_id', 'model' => $feed],
            ['action' => 'podlove-update-asset-position', 'id_key' => 'asset_id', 'model' => $asset],
        ] as $case) {
            $original_position = (float) $case['model']->position;
            $parameters = [
                $case['id_key'] => $case['model']->id,
                'position' => '12.5',
            ];

            $get_response = $this->ajax($case['action'], 'GET', $parameters, $this->administrator_id);
            $this->assertAjaxError('method_not_allowed', $get_response);
            $this->assertSame($original_position, $this->freshPosition($case['model']));

            $nonce_response = $this->ajax($case['action'], 'POST', $parameters, $this->administrator_id);
            $this->assertAjaxError('invalid_nonce', $nonce_response);
            $this->assertSame($original_position, $this->freshPosition($case['model']));

            $parameters['nonce'] = $this->nonce($this->editor_id, 'podlove_ajax');
            $capability_response = $this->ajax($case['action'], 'POST', $parameters, $this->editor_id);
            $this->assertAjaxError('forbidden', $capability_response);
            $this->assertSame($original_position, $this->freshPosition($case['model']));

            $parameters['nonce'] = $this->nonce($this->delegated_admin_id, 'podlove_ajax');
            $success_response = $this->ajax($case['action'], 'POST', $parameters, $this->delegated_admin_id);
            $this->assertTrue($success_response['success']);
            $this->assertSame(12.5, $this->freshPosition($case['model']));
        }
    }

    public function testReorderRejectsMalformedAndMissingObjectsWithoutChangingState(): void
    {
        $feed = $this->createFeed();
        $nonce = $this->nonce($this->administrator_id, 'podlove_ajax');

        $invalid_position = $this->ajax('podlove-update-feed-position', 'POST', [
            'feed_id' => $feed->id,
            'position' => '1e9999',
            'nonce' => $nonce,
        ], $this->administrator_id);
        $this->assertAjaxError('invalid_parameter', $invalid_position);
        $this->assertSame((float) $feed->position, $this->freshPosition($feed));

        $invalid_id = $this->ajax('podlove-update-feed-position', 'POST', [
            'feed_id' => '-1',
            'position' => '2',
            'nonce' => $nonce,
        ], $this->administrator_id);
        $this->assertAjaxError('invalid_parameter', $invalid_id);

        $missing_feed = $this->ajax('podlove-update-feed-position', 'POST', [
            'feed_id' => '999999999',
            'position' => '2',
            'nonce' => $nonce,
        ], $this->administrator_id);
        $this->assertAjaxError('feed_not_found', $missing_feed);
    }

    public function testAnalyticsPreferencesArePostOnlyNonceProtectedAndUserScoped(): void
    {
        update_option('podlove_analytics_tiles', [
            'download_source' => true,
            'legacy_attacker_key' => true,
        ]);
        update_option('podlove_analytics_compare_avg', true);

        $this->assertSame(
            ['download_source' => true],
            Analytics::tiles_for_user($this->author_one_id)
        );
        $this->assertTrue(Analytics::compare_avg_for_user($this->author_one_id));

        $get_response = $this->ajax('podlove-analytics-settings-tiles-update', 'GET', [
            'tile_id' => 'download_source',
            'checked' => '0',
        ], $this->author_one_id);
        $this->assertAjaxError('method_not_allowed', $get_response);

        $invalid_nonce = $this->ajax('podlove-analytics-settings-tiles-update', 'POST', [
            'tile_id' => 'download_source',
            'checked' => '0',
        ], $this->author_one_id);
        $this->assertAjaxError('invalid_nonce', $invalid_nonce);

        $nonce = $this->nonce($this->author_one_id, 'podlove_analytics_preferences');
        $tile_response = $this->ajax('podlove-analytics-settings-tiles-update', 'POST', [
            'tile_id' => 'download_source',
            'checked' => '0',
            'nonce' => $nonce,
        ], $this->author_one_id);
        $this->assertTrue($tile_response['success']);
        $this->assertSame(
            ['download_source' => false],
            get_user_meta($this->author_one_id, Analytics::TILES_USER_META, true)
        );
        $this->assertSame(
            ['download_source' => true],
            Analytics::tiles_for_user($this->author_two_id)
        );

        $avg_response = $this->ajax('podlove-analytics-settings-avg-update', 'POST', [
            'checked' => '0',
            'nonce' => $nonce,
        ], $this->author_one_id);
        $this->assertTrue($avg_response['success']);
        $this->assertTrue(metadata_exists('user', $this->author_one_id, Analytics::COMPARE_AVG_USER_META));
        $this->assertFalse(Analytics::compare_avg_for_user($this->author_one_id));
        $this->assertTrue(Analytics::compare_avg_for_user($this->author_two_id));

        $invalid_tile = $this->ajax('podlove-analytics-settings-tiles-update', 'POST', [
            'tile_id' => 'attacker_selected_key',
            'checked' => '1',
            'nonce' => $nonce,
        ], $this->author_one_id);
        $this->assertAjaxError('invalid_tile', $invalid_tile);
        $this->assertArrayNotHasKey(
            'attacker_selected_key',
            get_user_meta($this->author_one_id, Analytics::TILES_USER_META, true)
        );

        $invalid_checked = $this->ajax('podlove-analytics-settings-tiles-update', 'POST', [
            'tile_id' => 'download_source',
            'checked' => 'not-a-boolean',
            'nonce' => $nonce,
        ], $this->author_one_id);
        $this->assertAjaxError('invalid_parameter', $invalid_checked);
    }

    public function testAnalyticsPreferencesRequireAnalyticsCapability(): void
    {
        $subscriber_id = $this->factory->user->create(['role' => 'subscriber']);
        $response = $this->ajax('podlove-analytics-settings-avg-update', 'POST', [
            'checked' => '0',
            'nonce' => $this->nonce($subscriber_id, 'podlove_analytics_preferences'),
        ], $subscriber_id);

        $this->assertAjaxError('forbidden', $response);
        $this->assertFalse(metadata_exists('user', $subscriber_id, Analytics::COMPARE_AVG_USER_META));
    }

    public function testJobDeleteIsPostOnlyAndRequiresManageOptions(): void
    {
        $job = $this->createJob();

        $get_response = $this->ajax('podlove-job-delete', 'GET', [
            'job_id' => $job->id,
            'nonce' => $this->nonce($this->administrator_id, 'podlove_ajax'),
        ], $this->administrator_id);
        $this->assertAjaxError('method_not_allowed', $get_response);
        $this->assertNotNull(Job::find_by_id($job->id));

        $invalid_nonce_response = $this->ajax('podlove-job-delete', 'POST', [
            'job_id' => $job->id,
        ], $this->administrator_id);
        $this->assertAjaxError('invalid_nonce', $invalid_nonce_response);
        $this->assertNotNull(Job::find_by_id($job->id));

        $administrator_nonce = $this->nonce($this->administrator_id, 'podlove_ajax');
        $invalid_id_response = $this->ajax('podlove-job-delete', 'POST', [
            'job_id' => '0',
            'nonce' => $administrator_nonce,
        ], $this->administrator_id);
        $this->assertAjaxError('invalid_parameter', $invalid_id_response);

        $missing_job_response = $this->ajax('podlove-job-delete', 'POST', [
            'job_id' => '999999999',
            'nonce' => $administrator_nonce,
        ], $this->administrator_id);
        $this->assertAjaxError('job_not_found', $missing_job_response);
        $this->assertNotNull(Job::find_by_id($job->id));

        $editor_response = $this->ajax('podlove-job-delete', 'POST', [
            'job_id' => $job->id,
            'nonce' => $this->nonce($this->editor_id, 'podlove_ajax'),
        ], $this->editor_id);
        $this->assertAjaxError('forbidden', $editor_response);
        $this->assertNotNull(Job::find_by_id($job->id));

        $delegated_response = $this->ajax('podlove-job-delete', 'POST', [
            'job_id' => $job->id,
            'nonce' => $this->nonce($this->delegated_admin_id, 'podlove_ajax'),
        ], $this->delegated_admin_id);
        $this->assertSame(['status' => 'ok'], $delegated_response);
        $this->assertNull(Job::find_by_id($job->id));
    }

    public function testJobReadsAndCreationUseManageOptionsInsteadOfRoleName(): void
    {
        $job = $this->createJob();

        $editor_get = $this->ajax('podlove-job-get', 'GET', [
            'job_id' => $job->id,
        ], $this->editor_id);
        $this->assertAjaxError('forbidden', $editor_get);

        $delegated_get = $this->ajax('podlove-job-get', 'GET', [
            'job_id' => $job->id,
        ], $this->delegated_admin_id);
        $this->assertSame($job->id, (int) $delegated_get['id']);

        $editor_list = $this->ajax('podlove-jobs-get', 'GET', [], $this->editor_id);
        $this->assertAjaxError('forbidden', $editor_list);

        $editor_create = $this->ajax('podlove-job-create', 'POST', [
            'name' => \Podlove\Jobs\DownloadIntentCleanupJob::class,
            'nonce' => $this->nonce($this->editor_id, 'podlove_ajax'),
        ], $this->editor_id);
        $this->assertAjaxError('forbidden', $editor_create);
        $this->assertSame(1, Job::count());
    }

    public function testOnboardingBannerRequiresManageOptionsAndAcknowledgementIsPerUser(): void
    {
        $editor_response = $this->ajax('podlove-banner-hide', 'POST', [
            '_podlove_nonce' => $this->nonce($this->editor_id, 'podlove_onboarding'),
        ], $this->editor_id);
        $this->assertAjaxError('forbidden', $editor_response);
        $this->assertFalse(Onboarding::is_banner_hide());

        $admin_response = $this->ajax('podlove-banner-hide', 'POST', [
            '_podlove_nonce' => $this->nonce($this->administrator_id, 'podlove_onboarding'),
        ], $this->administrator_id);
        $this->assertTrue($admin_response['success']);
        $this->assertTrue(Onboarding::is_banner_hide());

        $ack_invalid_nonce = $this->ajax('podlove-onboarding-acknowledge', 'POST', [], $this->author_one_id);
        $this->assertAjaxError('invalid_nonce', $ack_invalid_nonce);

        $ack_get = $this->ajax('podlove-onboarding-acknowledge', 'GET', [
            '_podlove_nonce' => $this->nonce($this->author_one_id, 'podlove_onboarding_acknowledge'),
        ], $this->author_one_id);
        $this->assertAjaxError('method_not_allowed', $ack_get);

        $ack_response = $this->ajax('podlove-onboarding-acknowledge', 'POST', [
            '_podlove_nonce' => $this->nonce($this->author_one_id, 'podlove_onboarding_acknowledge'),
        ], $this->author_one_id);
        $this->assertTrue($ack_response['success']);
        $this->assertTrue((bool) get_user_meta(
            $this->author_one_id,
            'podlove_onboarding_acknowledge',
            true
        ));
        $this->assertFalse((bool) get_user_meta(
            $this->author_two_id,
            'podlove_onboarding_acknowledge',
            true
        ));
    }

    public function testGuidGenerationRequiresPostNonceAndEditPostCapability(): void
    {
        $post_one_id = $this->factory->post->create([
            'post_type' => 'podcast',
            'post_status' => 'draft',
            'post_author' => $this->author_one_id,
        ]);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_author' => $this->administrator_id,
        ]);

        $get_response = $this->ajax('podlove-get-new-guid', 'GET', [
            'post_id' => $post_one_id,
        ], $this->author_one_id);
        $this->assertAjaxError('method_not_allowed', $get_response);

        $invalid_nonce_response = $this->ajax('podlove-get-new-guid', 'POST', [
            'post_id' => $post_one_id,
        ], $this->author_one_id);
        $this->assertAjaxError('invalid_nonce', $invalid_nonce_response);

        $other_author_response = $this->ajax('podlove-get-new-guid', 'POST', [
            'post_id' => $post_one_id,
            'nonce' => $this->nonce($this->author_two_id, 'podlove_ajax'),
        ], $this->author_two_id);
        $this->assertAjaxError('forbidden', $other_author_response);

        $wrong_type_response = $this->ajax('podlove-get-new-guid', 'POST', [
            'post_id' => $page_id,
            'nonce' => $this->nonce($this->administrator_id, 'podlove_ajax'),
        ], $this->administrator_id);
        $this->assertAjaxError('post_not_found', $wrong_type_response);

        $owner_response = $this->ajax('podlove-get-new-guid', 'POST', [
            'post_id' => $post_one_id,
            'nonce' => $this->nonce($this->author_one_id, 'podlove_ajax'),
        ], $this->author_one_id);
        $this->assertArrayHasKey('guid', $owner_response);
        $this->assertNotSame('', $owner_response['guid']);
    }

    private function ajax(string $action, string $method, array $parameters, int $user_id): array
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER['REQUEST_METHOD'] = $method;
        $this->_last_response = '';
        wp_set_current_user($user_id);

        if ($method === 'POST') {
            $_POST = $parameters;
        } else {
            $_GET = $parameters;
        }

        try {
            $this->_handleAjax($action);
        } catch (WPAjaxDieContinueException|WPAjaxDieStopException $exception) {
            // Expected: WordPress ends every AJAX JSON response through wp_die().
        }

        $response = json_decode($this->_last_response, true);
        $this->assertIsArray($response, 'Expected a JSON AJAX response, got: '.$this->_last_response);

        return $response;
    }

    private function assertAjaxError(string $code, array $response): void
    {
        $this->assertFalse($response['success']);
        $this->assertSame($code, $response['data']['code']);
    }

    private function nonce(int $user_id, string $action): string
    {
        wp_set_current_user($user_id);

        return wp_create_nonce($action);
    }

    private function createFeed(): Feed
    {
        $feed = new Feed();
        $feed->name = 'AJAX Security Feed';
        $feed->slug = 'ajax-security-feed';
        $feed->position = 1;
        $feed->save();

        return $feed;
    }

    private function createEpisodeAsset(): EpisodeAsset
    {
        $file_type = new FileType();
        $file_type->name = 'AJAX Security Audio';
        $file_type->type = 'audio';
        $file_type->mime_type = 'audio/mpeg';
        $file_type->extension = 'mp3';
        $file_type->save();
        $this->file_type_id = $file_type->id;

        $asset = new EpisodeAsset();
        $asset->title = 'AJAX Security Asset';
        $asset->name = 'ajax-security-asset';
        $asset->file_type_id = $file_type->id;
        $asset->position = 1;
        $asset->save();

        return $asset;
    }

    private function createJob(): Job
    {
        $now = current_time('mysql', true);
        $job = new Job();
        $job->class = str_replace('\\', '\\\\', \Podlove\Jobs\DownloadIntentCleanupJob::class);
        $job->args = serialize([]);
        $job->state = serialize([]);
        $job->steps_total = 10;
        $job->steps_progress = 1;
        $job->active_run_time = 0;
        $job->wakeups = 0;
        $job->sleeps = 0;
        $job->created_at = $now;
        $job->updated_at = $now;
        $job->save();

        return $job;
    }

    private function freshPosition($model): float
    {
        return (float) get_class($model)::find_by_id($model->id)->position;
    }
}
