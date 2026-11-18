<?php

namespace Podlove\Modules\Locations\Model;

class Location
{
    public $id;
    public $episode_id;
    public $rel;
    public $location_name;
    public $location_lat;
    public $location_lng;
    public $location_address;
    public $location_country;
    public $location_osm;

    public static function table_name()
    {
        global $wpdb;

        return $wpdb->prefix.'podlove_episode_location';
    }

    public static function build()
    {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // dbDelta requires PRIMARY KEY on its own line (two spaces before "(") — inline
        // "PRIMARY KEY" on the id column causes ALTERs that error with "Multiple primary key defined"
        // when the table already exists (e.g. tests re-activate the module).
        $sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			episode_id BIGINT UNSIGNED NOT NULL,
			rel VARCHAR(20) NOT NULL DEFAULT 'subject',
			location_name VARCHAR(255) NULL,
			location_lat DECIMAL(10,8) NULL,
			location_lng DECIMAL(11,8) NULL,
			location_address TEXT NULL,
			location_country VARCHAR(2) NULL,
			location_osm VARCHAR(50) NULL,
			UNIQUE KEY episode_rel (episode_id, rel),
			PRIMARY KEY  (id)
		) {$charset_collate};";

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function find_by_episode_id_and_rel($episode_id, $rel = 'subject')
    {
        global $wpdb;

        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE episode_id = %d AND rel = %s LIMIT 1",
                $episode_id,
                $rel
            )
        );

        if (!$row) {
            return null;
        }

        return self::from_row($row);
    }

    /**
     * @return Location[]
     */
    public static function all()
    {
        global $wpdb;

        $table = self::table_name();
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC");

        if (!$rows) {
            return [];
        }

        $locations = [];
        foreach ($rows as $row) {
            $locations[] = self::from_row($row);
        }

        return $locations;
    }

    public static function delete_all()
    {
        global $wpdb;

        $wpdb->query('DELETE FROM '.self::table_name());
    }

    public function save()
    {
        global $wpdb;

        $table = self::table_name();
        $data = [
            'episode_id' => (int) $this->episode_id,
            'rel' => $this->rel ?: 'subject',
            'location_name' => $this->location_name,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'location_address' => $this->location_address,
            'location_country' => $this->location_country,
            'location_osm' => $this->location_osm,
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if ($this->id) {
            $wpdb->update($table, $data, ['id' => $this->id], $formats, ['%d']);
        } else {
            $wpdb->insert($table, $data, $formats);
            $this->id = $wpdb->insert_id;
        }
    }

    public function delete()
    {
        global $wpdb;

        if ($this->id) {
            $wpdb->delete(self::table_name(), ['id' => $this->id], ['%d']);
        }
    }

    public static function delete_for_episode($episode_id)
    {
        global $wpdb;

        return $wpdb->delete(self::table_name(), ['episode_id' => (int) $episode_id], ['%d']);
    }

    private static function from_row($row)
    {
        $model = new self();
        $model->id = (int) $row->id;
        $model->episode_id = (int) $row->episode_id;
        $model->rel = $row->rel;
        $model->location_name = $row->location_name;
        $model->location_lat = $row->location_lat;
        $model->location_lng = $row->location_lng;
        $model->location_address = $row->location_address;
        $model->location_country = $row->location_country;
        $model->location_osm = $row->location_osm;

        return $model;
    }
}
