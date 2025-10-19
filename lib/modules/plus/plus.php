<?php

namespace Podlove\Modules\Plus;

use Podlove\Model\Podcast;

class Plus extends \Podlove\Modules\Base
{
    public $settings_page;
    public $global_feed_settings;
    public $feed_pusher;
    public $feed_proxy;
    public $file_storage;
    protected $module_name = 'Publisher PLUS';
    protected $module_description = 'Publisher PLUS provides additional features and services for your podcast.';
    protected $module_group = 'external services';

    private $api;

    public function load()
    {
        // fixme: refactor all uses of 'plus_api_token' except here
        $token = defined('PODLOVE_PLUS_TOKEN') ? PODLOVE_PLUS_TOKEN : $this->get_module_option('plus_api_token');
        $this->api = new API($this, $token);

        $this->settings_page = new SettingsPage($this, $this->api);
        $this->settings_page->init();

        $this->global_feed_settings = new GlobalFeedSettings($this, $this->api);
        $this->global_feed_settings->init();

        $this->feed_pusher = new FeedPusher($this, $this->api);
        $this->feed_pusher->init();

        $this->feed_proxy = new FeedProxy($this, $this->api);
        $this->feed_proxy->init();

        $this->file_storage = new FileStorage($this, $this->api);
        $this->file_storage->init();

        add_action('rest_api_init', function () {
            $controller = new RestApi($this->api);
            $controller->register_routes();
        });

        // update podcast title in PLUS when
        // - podcast title changes locally or
        // - storage is enabled by user
        //
        // We do this to ensure that the _slug_ of the podcast is always up to
        // date in PLUS because it is used as part of the media download URL.
        add_action('update_option_podlove_podcast', function ($old_value, $new_value) {
            if ($old_value['title'] !== $new_value['title']) {
                $this->update_podcast_title_and_slug($old_value['guid'], $new_value['title']);
            }
        }, 10, 2);

        add_action('podlove_plus_enable_storage_changed', function ($new_value) {
            $podcast = Podcast::get();
            if ($new_value && $podcast->title) {
                $this->update_podcast_title_and_slug($podcast->guid ?? '', $podcast->title);
            }
        });
    }

    public function get_api()
    {
        return $this->api;
    }

    public static function base_url()
    {
        if (defined('PODLOVE_PLUS_BASE_URL')) {
            return PODLOVE_PLUS_BASE_URL;
        }

        return apply_filters('podlove_plus_base_url', 'https://plus.podlove.org');
    }

    /**
     * Updates the podcast title in PLUS and saves the returned slug.
     *
     * @param string $guid  Podcast GUID
     * @param string $title Podcast title
     */
    private function update_podcast_title_and_slug(string $guid, string $title)
    {
        $response = $this->api->upsert_podcast_title($guid, $title);
        if ($response && $response->slug) {
            $podcast = Podcast::get();
            $podcast->plus_slug = $response->slug;
            $podcast->save();
        }
    }
}
