<?php

namespace Podlove\Modules\Shownotes;

use \Podlove\Modules\Shownotes\Model\Entry;
use \Podlove\Modules\Affiliate\Affiliate;

class Shownotes extends \Podlove\Modules\Base
{
    protected $module_name        = 'Shownotes';
    protected $module_description = 'Generate and manage episode show notes. Helps you provide rich metadata for URLs. Full support for Publisher Templates.';
    protected $module_group       = 'web publishing';

    public function load()
    {
        add_filter('podlove_episode_form_data', [$this, 'extend_episode_form'], 10, 2);
        add_action('podlove_module_was_activated_shownotes', [$this, 'was_activated']);
        add_action('rest_api_init', [$this, 'api_init']);
        add_filter('podlove_shownotes_entry', [__CLASS__, 'apply_affiliate_to_shownotes_entry']);

        \Podlove\Template\Episode::add_accessor(
            'shownotes', ['\Podlove\Modules\Shownotes\TemplateExtensions', 'accessorEpisodeShownotes'], 5
        );

        \Podlove\Template\Episode::add_accessor(
            'hasShownotes',
            ['\Podlove\Modules\Shownotes\TemplateExtensions', 'accessorEpisodeHasShownotes'],
            4
        );
    }

    public function was_activated()
    {
        Entry::build();
    }

    public function extend_episode_form($form_data, $episode)
    {
        $form_data[] = array(
            'type'     => 'callback',
            'key'      => 'shownotes',
            'options'  => array(
                'callback' => function () use ($episode) {
                    ?>
                    <div id="podlove-shownotes-app"><shownotes episodeid="<?php echo esc_attr($episode->id); ?>"></shownotes></div>
                    <?php
},
                'label'    => __('Shownotes', 'podlove-podcasting-plugin-for-wordpress'),
            ),
            'position' => 415,
        );
        return $form_data;
    }

    public function api_init()
    {
        $api = new REST_API();
        $api->register_routes();
    }

    public static function apply_affiliate_to_shownotes_entry(Entry $entry)
    {
        $url = $entry->url;

        if (stripos($url, 'amazon.de') !== false) {
            $entry->affiliate_url = Affiliate::apply_amazon_de_affiliate($url);
        } elseif (stripos($url, 'thomann.de') !== false) {
            $entry->affiliate_url = Affiliate::apply_thomann_de_affiliate($url);
        }

        return $entry;
    }
    
}
