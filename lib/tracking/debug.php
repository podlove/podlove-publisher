<?php

namespace Podlove\Tracking;

class Debug
{
    public static function rewrites_exist()
    {
        global $wp_rewrite;

        $top_rewrite_patterns = array_keys($wp_rewrite->extra_rules_top);
        $podlove_rewrites = array_filter($top_rewrite_patterns, function ($pattern) {
            return stristr($pattern, '^podlove/file/') !== false;
        });

        return count($podlove_rewrites) > 0;
    }

    public static function is_consistent_https_chain($public_url, $actual_url)
    {
        // if the site doesn't run SSL it doesn't matter what the actual_url structure is
        if (!self::startswith($public_url, 'https')) {
            return true;
        }

        // if the site runs SSL, the files *must* be served with SSL, too
        return self::startswith($actual_url, 'https');
    }

    public static function url_resolves_correctly($start_url, $target_url)
    {
        $result = \wp_remote_head($start_url, [
          'user-agent' => \Podlove\Http\Curl::user_agent()
        ]);
        $final_url = $result['headers']['location'];

        return stristr($final_url, $target_url) !== false;
    }

    private static function startswith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
