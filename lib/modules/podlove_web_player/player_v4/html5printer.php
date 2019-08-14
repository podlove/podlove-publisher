<?php
namespace Podlove\Modules\PodloveWebPlayer\PlayerV4;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Modules\PodloveWebPlayer\MediaTagRenderer;
use Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;
use Podlove\Modules\PodloveWebPlayer\PlayerV4\Module;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface
{
    // Model\Episode
    private $episode;

    private $player_id;

    private $attributes = [];

    public function __construct(Episode $episode)
    {
        $this->episode = $episode;
    }

    private function get_player_id()
    {
        if (!$this->player_id) {
            $this->player_id = 'podlovewebplayer_' . sha1(microtime() . rand());
        }

        return $this->player_id;
    }

    public function render($context = null)
    {
        if (!$this->episode) {
            return '';
        }

        $id  = $this->get_player_id();
        $url = get_permalink($this->episode->post_id);
        $url = add_query_arg('podlove_action', 'pwp4_config', $url);
        $url = add_query_arg('podlove_context', $context, $url);

        $media_xml = (new MediaTagRenderer($this->episode))->render($context);

        return '<div class="pwp4-wrapper" id="' . $id . '" data-episode="' . $url . '">' . $media_xml . '</div>';
    }

    public static function media_files($episode, $context)
    {
        $player_media_files = new PlayerMediaFiles($episode);

        if ($media_files = $player_media_files->get($context)) {
            $media_file_urls = array_map(function ($file) {
                return [
                    'url'      => $file['publicUrl'],
                    'size'     => $file['size'],
                    'title'    => $file['assetTitle'],
                    'mimeType' => $file['mime_type'],
                ];
            }, $media_files);
        } elseif (is_admin()) {
            $media_file_urls = [
                'url'      => \Podlove\PLUGIN_URL . '/bin/podlove.mp3',
                'size'     => 486839,
                'title'    => 'Podlove Example Audio',
                'mimeType' => 'audio/mp3',
            ];
        } else {
            $media_file_urls = [];
        }

        return $media_file_urls;
    }

    public static function config($episode, $context)
    {
        $podcast = Podcast::get();

        $player_settings = \Podlove\get_webplayer_settings();

        $config = [
            'show'              => [
                'title'    => $podcast->title,
                'subtitle' => $podcast->subtitle,
                'summary'  => $podcast->summary,
                'poster'   => $podcast->cover_art()->setWidth(500)->url(),
                'link'     => \Podlove\get_landing_page_url(),
            ],
            'reference'         => [],
            'theme'             => [
                'main' => self::sanitize_color($player_settings['playerv4_color_primary'], '#000'),
            ],
            'visibleComponents' => array_keys($player_settings['playerv4_visible_components'], "on"),
        ];

        if (Module::use_cdn()) {
            $base = 'https://cdn.podlove.org/web-player/';
        } else {
            $base = trailingslashit(plugins_url('dist', __FILE__));
        }

        $config['reference']['base']  = $base;
        $config['reference']['share'] = $base . 'share.html';

        if ($player_settings['playerv4_use_podcast_language']) {
            $config = array_merge($config, [
                'runtime' => [
                    'language' => explode('-', $podcast->language)[0],
                ],
            ]);
        }

        $highlight_color = self::sanitize_color($player_settings['playerv4_color_secondary'], false);
        if ($highlight_color !== false) {
            $config['theme']['highlight'] = $highlight_color;
        }

        if ($episode) {
            $post = get_post($episode->post_id);

            $player_media_files = new PlayerMediaFiles($episode);

            $episode_title = $post->post_title;

            if (!$player_media_files->get($context) && is_admin()) {
                $episode_title = __('Example Episode', 'podlove-podcasting-plugin-for-wordpress');
            }

            $media_file_urls = self::media_files($episode, $context);

            $config = array_merge($config, [
                'title'           => $episode_title,
                'subtitle'        => trim($episode->subtitle),
                'summary'         => trim($episode->summary),
                'publicationDate' => mysql2date("c", $post->post_date),
                'poster'          => $episode->cover_art_with_fallback()->setWidth(500)->url(),
                'duration'        => $episode->get_duration('full'),
                'link'            => get_permalink($episode->post_id),
                'audio'           => $media_file_urls,
                'chapters'        => array_map(function ($c) {
                    $c->title = html_entity_decode(trim($c->title));
                    return $c;
                }, (array) json_decode($episode->get_chapters('json'))),
            ]);

            $config['reference']['config'] = self::config_url($episode);

            if (\Podlove\Modules\Base::is_active('contributors')) {
                $config['contributors'] = array_filter(array_map(function ($c) {
                    $contributor = $c->getContributor();

                    if (!$contributor) {
                        return [];
                    }

                    return [
                        'id'      => $contributor->id,
                        'name'    => $contributor->getName(),
                        'avatar'  => $contributor->avatar()->setWidth(150)->setHeight(150)->url(),
                        'role'    => $c->hasRole() ? $c->getRole()->to_array() : null,
                        'group'   => $c->hasGroup() ? $c->getGroup()->to_array() : null,
                        'comment' => $c->comment,
                    ];
                }, EpisodeContribution::find_all_by_episode_id($episode->id)));
            }
        }

        $config = apply_filters('podlove_player4_config', $config, $episode);

        return $config;
    }

    public static function config_url($episode)
    {
        return esc_url(add_query_arg('podlove_player4', $episode->id, trailingslashit(get_option('siteurl'))));
    }

    public static function sanitize_color($color, $default = '#000')
    {
        static $patterns = array(
            // 'cmyk'  => '/^(?:device-)?cmyk\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*(\d+(?:\.\d+)?|\.\d+)\s*\)/',
            'rgba' => '/^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*(\d+(?:\.\d+)?|\.\d+)\s*\)/',
            'rgb'  => '/^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/',
            'hsla' => '/^hsla\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%,\s*(\d+(?:\.\d+)?|\.\d+)\s*\)/',
            'hsl'  => '/^hsl\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%\)$/',
            // 'hsva'  => '/^hsva\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%,\s*(\d+(?:\.\d+)?|\.\d+)\s*\)$/',
            // 'hsv'   => '/^hsv\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%\)$/',
            'hex6' => '/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/',
            'hex3' => '/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/',
        );

        $color = trim($color);

        # fix duplicate '#'
        $color = preg_replace('/^[#]+/', '#', $color);
        if (preg_match($patterns['hex6'], $color) || preg_match($patterns['hex3'], $color)) {

            # add missing '#'
            if ($color[0] != '#') {
                $color = '#' . $color;
            }

            return $color;
        }

        // accept any known color format
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $color)) {
                return $color;
            }
        }

        return $default;
    }
}
