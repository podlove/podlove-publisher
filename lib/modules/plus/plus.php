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
        // fixme: refactor all uses of 'plus_api_token' except here
        $token = defined('PODLOVE_PLUS_TOKEN') ? PODLOVE_PLUS_TOKEN : $this->get_module_option('plus_api_token');
        $this->api = new API($this, $token);

        (new ModuleSettings($this, $this->api))->init();
        (new GlobalFeedSettings($this, $this->api))->init();
        (new FeedPusher($this, $this->api))->init();
        (new FeedProxy($this, $this->api))->init();

        // disabling unfinished feature
        // (new ImageGenerator($this, $this->api))->init();
    }

    public static function base_url()
    {
        if (defined('PODLOVE_PLUS_BASE_URL')) {
            return PODLOVE_PLUS_BASE_URL;
        }

        return apply_filters('podlove_plus_base_url', 'https://plus.podlove.org');
    }
}
