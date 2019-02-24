<?php
namespace Podlove\Modules\Shownotes;

use \Podlove\Modules\Shownotes\Model\Entry;

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

        \Podlove\Template\Episode::add_accessor(
            'shownotes', ['\Podlove\Modules\Shownotes\TemplateExtensions', 'accessorEpisodeShownotes'], 4
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

    /**
     * Create entries from osf "shownotes" plugin
     *
     * @see https: //wordpress.org/plugins/shownotes/
     */
    public static function import_from_osf($post_id)
    {
        if (!function_exists('osf_parser')) {
            return false;
        }

        $shownotes = get_post_meta($post_id, "_shownotes", true);

        $tags = explode(' ', 'chapter section spoiler topic embed video audio image shopping glossary source app title quote link podcast news');
        $data = [
            'amazon'       => '',
            'thomann'      => '',
            'tradedoubler' => '',
            'fullmode'     => 'true', // sic
            'tagsmode'     => 1,
            'tags'         => $tags,
        ];
        $parsed = osf_parser($shownotes, $data);

        $links = $parsed['export'][0]['subitems'];

        if (!is_array($links)) {
            return false;
        }

        $links = array_map(function ($link) {
            if (!$link['orig'] || !$link['urls'] || !count($link['urls'])) {
                return null;
            }

            return [
                'title' => $link['orig'],
                'url'   => $link['urls'][0],
            ];
        }, $links);
        $links = array_filter($links);

        if (!$episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id)) {
            return false;
        }

        foreach ($links as $link) {
            $request = new \WP_REST_Request('POST', '/podlove/v1/shownotes');
            $request->set_query_params([
                'episode_id'   => $episode->id,
                'original_url' => $link['url'],
                'data'         => [
                    'title' => $link['title'],
                ],
            ]);
            rest_do_request($request);
        }
    }
}
