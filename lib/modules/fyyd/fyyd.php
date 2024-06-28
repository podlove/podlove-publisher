<?php

namespace Podlove\Modules\fyyd;

class fyyd extends \Podlove\Modules\Base
{
    protected $module_name = 'fyyd';
    protected $module_description = 'Inserts a verification code into your feeds for the fyyd search engine.';
    protected $module_group = 'Podcast Directories';

    public function load()
    {
        add_action('init', [$this, 'register_hooks']);
        $this->register_option('fyyd_verifycode', 'string', [
            'label' => __('fyyd verifycode', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Code to verify your ownership at fyyd', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => [
                'class' => 'regular-text podlove-check-input',
                'data-podlove-input-type' => 'text',
                'placeholder' => 'yourverifycodehere',
            ],
        ]);
    }

    public function register_hooks()
    {
        $code = $this->get_module_option('fyyd_verifycode');

        if (!$code) {
            return;
        }

        add_filter('podlove_rss_channel', function ($channel) use ($code) {
            $channel[\Podlove\RSS\Generator::NS_FYYD.'verify'] = $code;

            return $channel;
        });
    }
}
