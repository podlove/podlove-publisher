<?php

namespace Podlove\Modules\ExternalAnalytics;

class External_Analytics extends \Podlove\Modules\Base
{
    protected $module_name = 'External Analytics';
    protected $module_description = 'Add an external analytics service, e.g. OP3, Podtrac, Blubrry, etc.';
    protected $module_group = 'external services';

    public function load()
    {
        add_action('init', [$this, 'register_hooks']);
        add_action('init', [$this, 'register_module_option']);
    }

    public function register_hooks()
    {
        $analytics_prefix = $this->get_module_option('analytics_prefix');
        if (!$analytics_prefix) {
            return;
        }

        add_filter('podlove_enclosure_url', function ($original_url) use ($analytics_prefix) {
            $schemeless_url = preg_replace('/^https?:\/\//', '', $original_url);

            return trailingslashit($analytics_prefix).$schemeless_url;
        });
    }

    public function register_module_option()
    {
        $this->register_option('analytics_prefix', 'string', [
            'label' => __('Analytics Prefix', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => '
    <p><b>'.__('Examples:', 'podlove-podcasting-plugin-for-wordpress').'</b></p>
    '.'<ul>
    '.'<li><a href="https://op3.dev/" target="_blank">Open Podcast Prefix Project (OP3)</a>: https://op3.dev/e/</li>
    '.'<li><a href="https://publisher.podtrac.com" target="_blank">Podtrac</a>: https://dts.podtrac.com/redirect.mp3/</li>
    '.'<li><a href="https://stats.blubrry.com" target="_blank">Blubrry</a>: http://media.blubrry.com/{blubrry_id}/</li>
    <li>'.__('etc.', 'podlove-podcasting-plugin-for-wordpress').'</li>
    '.'</ul>
            ',
            'html' => [
                'class' => 'regular-text podlove-check-input',
                'data-podlove-input-type' => 'text',
                'placeholder' => 'https://op3.dev/e/'
            ]
        ]);
    }
}
