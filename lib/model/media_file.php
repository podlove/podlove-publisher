<?php

namespace Podlove\Model;

use Podlove\Log;

class MediaFile extends Base
{
    use KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    /**
     * Fetches file size if necessary.
     *
     * @override Base::save()
     */
    public function save()
    {
        if (!$this->size) {
            $this->determine_file_size();
        }

        return parent::save();
    }

    /**
     * Find the related show model.
     *
     * @return null|\Podlove\Model\EpisodeAsset
     */
    public function episode_asset()
    {
        return $this->with_blog_scope(function () {
            return EpisodeAsset::find_by_id($this->episode_asset_id);
        });
    }

    /**
     * Find one downloadable example file.
     *
     * - JOIN episode to avoid dead media files
     * - ORDER BY e.id DESC, mf.id ASC: get a recent episode and the first asset
     */
    public static function find_example()
    {
        $episode = Episode::latest();

        if (!$episode) {
            return;
        }

        $files = $episode->media_files();

        $files = array_filter($files, function ($file) {
            $asset = $file->episode_asset();

            if (!$asset) {
                return false;
            }

            $file_type = $asset->file_type();

            if (!$file_type) {
                return false;
            }

            return $asset->downloadable && $file_type->type == 'audio';
        });

        return reset($files);
    }

    public static function find_or_create_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)
    {
        if (!$file = self::find_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)) {
            $file = new MediaFile();
            $file->episode_id = $episode_id;
            $file->episode_asset_id = $episode_asset_id;
            $file->save();
        }

        return $file;
    }

    public static function find_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)
    {
        $where = sprintf(
            'episode_id = "%s" AND episode_asset_id = "%s"',
            $episode_id,
            $episode_asset_id
        );

        return MediaFile::find_one_by_where($where);
    }

    /**
     * Is this media file valid?
     *
     * @return bool
     */
    public function is_valid()
    {
        return $this->size > 0;
    }

    /**
     * Return public file URL.
     *
     * A source must be provided, an additional context is optional.
     * Example sources: webplayer, download, feed, other
     * Example contexts: home/episode/archive for player source, feed slug for feed source
     *
     * @param string      $source  download source
     * @param null|string $context optional download context
     *
     * @return string
     */
    public function get_public_file_url($source, $context = null)
    {
        return $this->with_blog_scope(function () use ($source, $context) {
            if (empty($source) && empty($context)) {
                return $this->get_file_url();
            }

            $params = [
                'source' => $source,
                'context' => $context,
            ];

            $url = '';

            switch ((string) \Podlove\get_setting('tracking', 'mode')) {
                case 'ptm':
                    // when PTM is active, add $source and $context but
                    // keep the original file URL
                    $url = $this->add_ptm_parameters(
                        $this->get_file_url(),
                        $params
                    );

                    break;
                case 'ptm_analytics':
                    // we track, so we need to generate a shadow URL
                    if (get_option('permalink_structure')) {
                        $path = '/podlove/file/'.$this->id;
                        $path = $this->add_ptm_routing($path, $params);
                    } else {
                        $path = '?download_media_file='.$this->id;
                        $path = $this->add_ptm_parameters($path, $params);
                    }
                    $url = home_url($path);

                    break;

                default:
                    // tracking is off, return raw URL
                    $url = $this->get_file_url();

                    break;
            }

            return apply_filters('podlove_enclosure_url', $url);
        });
    }

    public function add_ptm_routing($path, $params)
    {
        if (isset($params['source'])) {
            $path .= "/s/{$params['source']}";
        }

        if (isset($params['context'])) {
            $path .= "/c/{$params['context']}";
        }

        $path .= '/'.$this->get_download_file_name();

        return $path;
    }

    public function add_ptm_parameters($path, $params)
    {
        // trim params
        $params = array_map(function ($p) {
            return trim($p);
        }, $params);

        $connector = function ($path) {
            return strpos($path, '?') === false ? '?' : '&';
        };

        // add params to path
        foreach ($params as $param_name => $value) {
            $path .= $connector($path).'ptm_'.$param_name.'='.$value;
        }

        // at last, add file param, so wget users get the right extension
        $path .= $connector($path).'ptm_file='.$this->get_download_file_name();

        return $path;
    }

    /**
     * Return real file URL.
     *
     * For public facing URLs, use ::get_public_file_url().
     *
     * @return string
     */
    public function get_file_url()
    {
        return $this->with_blog_scope(function () {
            $podcast = Podcast::get();

            $episode = $this->episode();
            $episode_asset = EpisodeAsset::find_by_id($this->episode_asset_id);
            $file_type = FileType::find_by_id($episode_asset->file_type_id);

            if (!$episode_asset || !$file_type || !$episode) {
                return '';
            }

            $template = $podcast->get_url_template();
            $template = apply_filters('podlove_file_url_template', $template);
            $template = str_replace('%media_file_base_url%', trailingslashit($podcast->get_media_file_base_uri()), $template);
            $template = str_replace('%episode_slug%', \Podlove\prepare_episode_slug_for_url($episode->slug), $template);
            $template = str_replace('%suffix%', $episode_asset->suffix, $template);
            $template = str_replace('%format_extension%', $file_type->extension, $template);

            return trim($template);
        });
    }

    public function episode()
    {
        return $this->with_blog_scope(function () {
            return Episode::find_by_id($this->episode_id);
        });
    }

    /**
     * Build file name as it appears when you download the file.
     *
     * @return string
     */
    public function get_download_file_name()
    {
        $file_name = $this->episode()->slug
                   .'.'
                   .$this->episode_asset()->file_type()->extension;

        return apply_filters('podlove_download_file_name', $file_name, $this);
    }

    /**
     * Determine file size by reading the HTTP Header of the file url.
     */
    public function determine_file_size()
    {
        $header = $this->curl_get_header();

        $http_code = (int) $header['http_code'];
        // do not change the filesize if http_code = 0
        // aka "an error occured I don't know how to deal with" (probably timeout)
        // => change to proper handling once "Conflicts" are introduced
        if (podlove_is_resolved_and_reachable_http_status($http_code) && $http_code !== 304) {
            if (isset($header['download_content_length']) && $header['download_content_length'] > 0) {
                $this->size = $header['download_content_length'];
            } else {
                // We know that the file exists but have no way of determining its size.
                // Having a proper state would be nice, but this "size = 1 byte" hack works for now.
                $this->size = 1;
            }
        }

        if ($this->size <= 0) {
            $this->etag = null;
        }

        return $header;
    }

    /**
     * Retrieve header data via curl.
     *
     * @return array
     */
    public function curl_get_header()
    {
        $response = self::curl_get_header_for_url($this->get_file_url(), $this->etag);
        $this->validate_request($response);

        return $response['header'];
    }

    /**
     * @todo  use \Podlove\Http\Curl
     *
     * @param mixed      $url
     * @param null|mixed $etag
     *
     * @return array
     */
    public static function curl_get_header_for_url($url, $etag = null)
    {
        if (!function_exists('curl_exec')) {
            return [];
        }

        $curl = curl_init();

        if (\Podlove\Http\Curl::curl_can_follow_redirects()) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // follow redirects
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);         // maximum number of redirects
        } else {
            $url = \Podlove\Http\Curl::resolve_redirects($url, 5);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // make curl_exec() return the result
        curl_setopt($curl, CURLOPT_HEADER, true);         // header only
        curl_setopt($curl, CURLOPT_NOBODY, true);         // return no body; HTTP request method: HEAD
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, (\Podlove\get_setting('website', 'ssl_verify_peer') == 'on')); // Don't check SSL certificate in order to be able to use self signed certificates
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);          // HEAD requests shouldn't take > 2 seconds

        if ($etag) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'If-None-Match: "'.$etag.'"',
            ]);
        }

        curl_setopt($curl, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());

        $response = curl_exec($curl);
        $response_header = curl_getinfo($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'header' => $response_header,
            'response' => $response,
            'error' => $error,
        ];
    }

    /**
     * Validate media file headers.
     *
     * @todo  $this->id not available for first validation before media_file has been saved
     *
     * @param array $response curl response
     */
    private function validate_request($response)
    {
        // skip unsaved media files
        if (!$this->id) {
            return;
        }

        $header = $response['header'];

        if ($response['error']) {
            Log::get()->addError(
                'Curl Error: '.$response['error'],
                ['media_file_id' => $this->id]
            );
        }

        // skip validation if ETag did not change
        if ((int) $header['http_code'] === 304) {
            return;
        }

        // look for ETag and safe for later
        if (podlove_is_resolved_and_reachable_http_status($header['http_code']) && preg_match('/ETag:\s*"([^"]+)"/i', $response['response'], $matches)) {
            $this->etag = $matches[1];
        } else {
            $this->etag = null;
        }

        do_action('podlove_media_file_content_has_changed', $this->id);

        // verify HTTP header
        if (!preg_match('/^[23]\\d\\d$/', $header['http_code'])) {
            Log::get()->addError(
                'Unexpected http response when trying to access remote media file.',
                ['media_file_id' => $this->id, 'http_code' => $header['http_code']]
            );

            return;
        }

        // check that content length exists and hasn't changed
        if (!isset($header['download_content_length']) || $header['download_content_length'] <= 0) {
            Log::get()->addWarning(
                'Unable to read "Content-Length" header. Impossible to determine file size.',
                ['media_file_id' => $this->id, 'mime_type' => $header['content_type'], 'expected_mime_type' => $mime_type]
            );
        } elseif ($header['download_content_length'] != $this->size) {
            Log::get()->addInfo(
                'Change of media file content length detected.',
                ['media_file_id' => $this->id, 'old_size' => $this->size, 'new_size' => $header['download_content_length']]
            );
        }

        // check if mime type matches asset mime type
        $mime_type = $this->episode_asset()->file_type()->mime_type;
        if ($header['content_type'] != $mime_type) {
            Log::get()->addWarning(
                'Media file mime type does not match expected asset mime type.',
                ['media_file_id' => $this->id, 'mime_type' => $header['content_type'], 'expected_mime_type' => $mime_type]
            );
        }
    }
}

MediaFile::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
MediaFile::property('episode_id', 'INT');
MediaFile::property('episode_asset_id', 'INT');
MediaFile::property('size', 'INT');
MediaFile::property('etag', 'VARCHAR(255)');
