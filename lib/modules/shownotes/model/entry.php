<?php
namespace Podlove\Modules\Shownotes\Model;

use \Podlove\Model\Base;

class Entry extends Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    public static function get_new_position_for_episode($episode_id)
    {
        global $wpdb;
        $sql = <<<SQL
            SELECT
                MAX(e.position)
            FROM
                wp_podlove_modules_shownotes_entry e
            WHERE
                e.episode_id = %d
            GROUP BY
                e.episode_id
SQL;

        $position = $wpdb->get_var($wpdb->prepare($sql, $episode_id));

        if (is_numeric($position)) {
            return $position + 1;
        } else {
            return 0;
        }
    }
}

Entry::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Entry::property('episode_id', 'INT');
Entry::property('state', 'VARCHAR(255)');
Entry::property('position', 'FLOAT');
Entry::property('unfurl_data', 'TEXT');
Entry::property('original_url', 'TEXT');
Entry::property('url', 'TEXT');
Entry::property('title', 'TEXT');
Entry::property('description', 'TEXT');
Entry::property('site_name', 'TEXT');
Entry::property('site_url', 'TEXT');
Entry::property('icon', 'TEXT');
