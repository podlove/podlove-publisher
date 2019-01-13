<?php
namespace Podlove\Modules\SlackShownotes;

class Message
{
    public static function extract_links($message)
    {
        preg_match_all("/<(http[^>]*)>/", $message["text"], $links);

        return array_reduce($links[1], function ($agg, $url) use ($message) {
            $url_segments  = explode("|", $url);
            $canonical_url = $url_segments[0];

            $agg[] = [
                "link"   => $canonical_url,
                "title"  => self::get_url_title_via_attachment($canonical_url, $message),
                "source" => self::get_source_via_attachment($canonical_url, $message),
            ];
            return $agg;
        }, []);
    }

    public static function get_url_title_via_attachment($url, $message)
    {
        if (!isset($message["attachments"]) || !count($message["attachments"])) {
            return null;
        }

        $matching_attachments = array_filter($message["attachments"], function ($attachment) use ($url) {
            return $url == $attachment["original_url"];
        });

        if (!count($matching_attachments)) {
            return null;
        }

        return array_values($matching_attachments)[0]["title"];
    }

    public static function get_source_via_attachment($url, $message)
    {
        $fallback = self::get_short_domain_from_url($url);

        if (!isset($message["attachments"]) || !count($message["attachments"])) {
            return $fallback;
        }

        $matching_attachments = array_filter($message["attachments"], function ($attachment) use ($url) {
            return $url == $attachment["original_url"];
        });

        if (!count($matching_attachments)) {
            return $fallback;
        }

        return array_values($matching_attachments)[0]["service_name"];
    }

    public static function get_short_domain_from_url($url)
    {
        $host = parse_url($url)['host'];
        return preg_replace("/^www\./", "", $host);
    }
}
