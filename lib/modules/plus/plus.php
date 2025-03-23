<?php

namespace Podlove\Modules\Plus;

use Podlove\Model\Podcast;

class Plus extends \Podlove\Modules\Base
{
    protected $module_name = 'Publisher PLUS';
    protected $module_description = 'A Feed Proxy service for subscriber statistics and performance.';
    protected $module_group = 'external services';

    private $api;

    public function load()
    {
        // fixme: refactor all uses of 'plus_api_token' except here
        $token = defined('PODLOVE_PLUS_TOKEN') ? PODLOVE_PLUS_TOKEN : $this->get_module_option('plus_api_token');
        $this->api = new API($this, $token);

        (new ModuleSettings($this, $this->api))->init();
        (new GlobalFeedSettings($this, $this->api))->init();
        (new FeedPusher($this, $this->api))->init();
        (new FeedProxy($this, $this->api))->init();
        (new FileStorage($this, $this->api))->init();

        // disabling unfinished feature
        // (new ImageGenerator($this, $this->api))->init();

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
                $this->api->upsert_podcast_title($old_value['guid'], $new_value['title']);
            }
        }, 10, 2);

        add_action('podlove_plus_enable_storage_changed', function ($new_value) {
            if ($new_value) {
                $podcast = Podcast::get();
                $this->api->upsert_podcast_title($podcast->guid, $podcast->title);
            }
        });
    }

    public static function base_url()
    {
        if (defined('PODLOVE_PLUS_BASE_URL')) {
            return PODLOVE_PLUS_BASE_URL;
        }

        return apply_filters('podlove_plus_base_url', 'https://plus.podlove.org');
    }
}
