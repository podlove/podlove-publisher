<?php

namespace Podlove\Modules\PodloveWebPlayer\Podigee;

use Podlove\Model\Episode;
use Podlove\Model\Feed;
use Podlove\Model\MediaFile;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface
{
    // Model\Episode
    private $episode;

    private $config_var_name;

    public function __construct(Episode $episode)
    {
        $this->episode = $episode;
    }

    public function render($context = null, $style = 'configfile')
    {
        // needs to be relative URL since it needs to request the config.json via XHR,
        // which will be blocked if the protocol does not match the iframe protocol
        $src = '//cdn.podigee.com/podcast-player/javascripts/podigee-podcast-player.js';

        if ($style == 'inline') { // inline players are not embeddable
            return '
			<script>window.'.$this->config_var_name().' = '.json_encode(self::config($this->episode, $context)).'</script>
			<script class="podigee-podcast-player" src="'.$src.'" data-configuration="'.$this->config_var_name().'"></script>';
        }

        return '<script class="podigee-podcast-player" src="'.$src.'" data-configuration="'.$this->config_url().'"></script>';
    }

    public function config_url()
    {
        return esc_url(add_query_arg('podigee_player', $this->episode->id, trailingslashit(get_option('siteurl'))));
    }

    public static function config($episode, $context)
    {
        $post = get_post($episode->post_id);
        $player_media_files = new \Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles($episode);
        $media_files = $player_media_files->get($context);
        $media_files_conf = array_reduce($media_files, function ($agg, $item) {
            $extension = $item['extension'];

            if ($extension == 'oga') {
                $extension = 'ogg';
            }

            $agg[$extension] = $item['url'];

            return $agg;
        }, []);

        $config = [
            'options' => [
                'theme' => \Podlove\get_webplayer_setting('podigeetheme'),
            ],
            'extensions' => [
                'EpisodeInfo' => [
                    'showOnStart' => false,
                ],
                'ChapterMarks' => [
                    'showOnStart' => false,
                ],
                'Share' => [],
            ],
            'podcast' => [
                // don't provide the feed unless we have a CORS solution
                // 'feed' => Feed::first()->get_subscribe_url()
            ],
            'episode' => [
                'media' => $media_files_conf,
                'title' => get_the_title($post->ID),
                'subtitle' => wptexturize(convert_chars(trim($episode->subtitle))),
                'description' => nl2br(wptexturize(convert_chars(trim($episode->summary)))),
                'coverUrl' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
                'chaptermarks' => json_decode($episode->get_chapters('json')),
                'url' => get_permalink($post->ID),
            ],
        ];

        $player_assignments = get_option('podlove_webplayer_formats');
        if ($player_assignments && isset($player_assignments['transcript'], $player_assignments['transcript']['transcript'])) {
            $transcript_asset_id = (int) $player_assignments['transcript']['transcript'];
            $transcript_media_file = MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $transcript_asset_id);

            if ($transcript_media_file && $transcript_media_file->is_valid()) {
                $config['extensions']['Transcript'] = [];
                $config['episode']['Transcript'] = $transcript_media_file->get_public_file_url('webplayer');
            }
        }

        foreach ($media_files as $file) {
            switch ($file['mime_type']) {
                case 'audio/mp4':  $ext = 'm4a';

break;
                case 'audio/opus': $ext = 'opus';

break;
                case 'audio/ogg':  $ext = 'ogg';

break;
                case 'audio/mpeg': $ext = 'mp3';

break;
                default: $ext = false;

break;
            }

            if ($ext) {
                $config['episode']['media'][$ext] = $file['publicUrl'];
            }
        }

        return $config;
    }

    private function config_var_name()
    {
        if (!$this->config_var_name) {
            $uuid = str_replace('.', '', uniqid('', true));
            $this->config_var_name = 'player_'.$uuid;
        }

        return $this->config_var_name;
    }
}
