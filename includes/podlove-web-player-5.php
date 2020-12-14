<?php

use Podlove\Model\Episode;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\FileType;
use Podlove\Model\MediaFile;
use Podlove\Model\Podcast;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;

function podlove_pwp5_init()
{
    add_filter('podlove_web_player_shortcode_episode_attributes', 'podlove_pwp5_attributes');
}

function podlove_pwp5_attributes($attributes)
{
    $post_id = (isset($attributes['post_id']) && $attributes['post_id']) ? $attributes['post_id'] : get_the_ID();
    $episode = Episode::find_one_by_post_id($post_id);

    if (!$episode) {
        return [];
    }

    $post = get_post($episode->post_id);
    $podcast = Podcast::get();

    $chapters = array_map(function ($c) {
        $c->title = html_entity_decode(trim($c->title));

        return $c;
    }, (array) json_decode($episode->get_chapters('json')));

    $config = [
        'version' => 5,
        'show' => [
            'title' => $podcast->title,
            'subtitle' => $podcast->subtitle,
            'summary' => $podcast->summary,
            'poster' => $podcast->cover_art()->setWidth(500)->url(),
            'link' => \Podlove\get_landing_page_url(),
        ],
        'title' => $post->post_title,
        'subtitle' => trim($episode->subtitle),
        'summary' => trim($episode->summary),
        'publicationDate' => mysql2date('c', $post->post_date),
        'duration' => $episode->get_duration('full'),
        'poster' => $episode->cover_art_with_fallback()->setWidth(500)->url(),
        'link' => get_permalink($episode->post_id),
        'chapters' => $chapters ? $chapters : [],
        'audio' => podlove_pwp5_audio_files($episode, null),
        'files' => podlove_pwp5_files($episode, null),
    ];

    if (\Podlove\Modules\Base::is_active('contributors')) {
        $config['contributors'] = array_filter(array_map(function ($c) {
            $contributor = $c->getContributor();

            if (!$contributor || !$contributor->visibility) {
                return [];
            }

            return [
                'id' => $contributor->id,
                'name' => $contributor->getName(),
                'avatar' => $contributor->avatar()->setWidth(150)->setHeight(150)->url(),
                'role' => $c->hasRole() ? $c->getRole()->to_array() : null,
                'group' => $c->hasGroup() ? $c->getGroup()->to_array() : null,
                'comment' => $c->comment,
            ];
        }, EpisodeContribution::find_all_by_episode_id($episode->id)));
    }

    return apply_filters('podlove_player5_config', $config, $episode);
}

function podlove_pwp5_audio_files($episode, $context)
{
    $player_media_files = new PlayerMediaFiles($episode);

    if ($media_files = $player_media_files->get($context)) {
        $media_file_urls = array_map(function ($file) {
            return [
                'url' => $file['publicUrl'],
                'size' => $file['size'],
                'title' => $file['assetTitle'],
                'mimeType' => $file['mime_type'],
            ];
        }, $media_files);
    } elseif (is_admin()) {
        $media_file_urls = [
            'src' => \Podlove\PLUGIN_URL.'/bin/podlove.mp3',
            'size' => 486839,
            'title' => 'Podlove Example Audio',
            'mimeType' => 'audio/mp3',
        ];
    } else {
        $media_file_urls = [];
    }

    return $media_file_urls;
}

function podlove_pwp5_files($episode, $context)
{
    global $wpdb;

    $sql = 'SELECT
    mf.id media_file_id, mf.size file_size, a.title asset_tile, a.downloadable, a.`position`, ft.mime_type, ft.`extension`
FROM
    '.Episode::table_name().' e
    LEFT JOIN '.MediaFile::table_name().' mf ON mf.episode_id = e.id
    LEFT JOIN '.EpisodeAsset::table_name().' a ON a.id = mf.episode_asset_id
    LEFT JOIN '.FileType::table_name().' ft ON ft.id = a.file_type_id
WHERE
    e.id = %d AND a.downloadable
ORDER BY
    position ASC
    ';

    $files = $wpdb->get_results($wpdb->prepare($sql, $episode->id), ARRAY_A);

    return array_map(function ($row) use ($context) {
        $media_file = MediaFile::find_by_id($row['media_file_id']);

        return [
            'url' => $media_file->get_public_file_url('webplayer', $context),
            'size' => $row['file_size'],
            'title' => $row['asset_tile'],
            'mimeType' => $row['mime_type'],
        ];
    }, $files);
}

podlove_pwp5_init();
