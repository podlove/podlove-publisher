<?php

namespace Podlove\Modules\Plus;

class Plus extends \Podlove\Modules\Base
{
    protected $module_name = 'Publisher PLUS';
    protected $module_description = 'A Feed Proxy service for subscriber statistics and performance.';
    protected $module_group = 'external services';

    private $api;

    public function load()
    {
        $this->api = new API($this, $this->get_module_option('plus_api_token'));

        (new ModuleSettings($this, $this->api))->init();
        (new GlobalFeedSettings($this, $this->api))->init();
        (new FeedPusher($this, $this->api))->init();
        (new FeedProxy($this, $this->api))->init();
        (new ImageGenerator($this, $this->api))->init();
    }

    public static function base_url()
    {
        if (defined('PODLOVE_PLUS_BASE_URL')) {
            return PODLOVE_PLUS_BASE_URL;
        }

        return apply_filters('podlove_plus_base_url', 'https://plus.podlove.org');
    }
}
