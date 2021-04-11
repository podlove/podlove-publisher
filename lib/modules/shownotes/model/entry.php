<?php

namespace Podlove\Modules\Shownotes\Model;

use Podlove\Model\Base;

class Entry extends Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    /**
     * Prepare Icon.
     *
     * If possible, serve icon locally.
     */
    public function prepare_icon()
    {
        $services = \Podlove\Modules\Social\Social::services_config();
        $host = parse_url($this->site_url, PHP_URL_HOST);
        $icons = array_filter($services, function ($service) use ($host) {
            return stristr($service['url_scheme'], $host) !== false;
        });

        if (!$icons) {
            return;
        }

        $icon = reset($icons);
        $service = \Podlove\Modules\Social\Model\Service::from_data($icon);
        $url = $service->image()->url();

        if ($url) {
            $this->icon = $url;
        }
    }

    public static function get_new_position_for_episode($episode_id)
    {
        global $wpdb;
        $table_name = static::table_name();

        $sql = <<<SQL
            SELECT
                MAX(e.position)
            FROM
                {$table_name} e
            WHERE
                e.episode_id = %d
            GROUP BY
                e.episode_id
SQL;

        $position = $wpdb->get_var($wpdb->prepare($sql, $episode_id));

        if (is_numeric($position)) {
            return $position + 1;
        }

        return 0;
    }

    public static function has_shownotes($episode_id)
    {
        global $wpdb;
        $table_name = static::table_name();

        $sql = <<<SQL
            SELECT
                COUNT(e.id)
            FROM
                {$table_name} e
            WHERE
                e.episode_id = %d
SQL;

        $count = $wpdb->get_var($wpdb->prepare($sql, $episode_id));

        return $count > 0;
    }
}

Entry::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Entry::property('episode_id', 'INT');
Entry::property('type', 'VARCHAR(255)');
Entry::property('state', 'VARCHAR(255)');
Entry::property('position', 'FLOAT');
Entry::property('unfurl_data', 'TEXT');
Entry::property('original_url', 'TEXT');
Entry::property('affiliate_url', 'TEXT'); // virtual?
Entry::property('url', 'TEXT');
Entry::property('title', 'TEXT');
Entry::property('description', 'TEXT');
Entry::property('site_name', 'TEXT');
Entry::property('site_url', 'TEXT');
Entry::property('icon', 'TEXT');
Entry::property('image', 'TEXT');
Entry::property('created_at', 'INT');
Entry::property('hidden', 'INT');
