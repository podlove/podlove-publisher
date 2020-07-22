<?php

namespace Podlove\Model;

/**
 * Simplified Singleton model for episode assignment data.
 */
class AssetAssignment
{
    /**
     * Contains property names.
     *
     * @var array
     */
    protected static $properties = [];

    /**
     * Contains property values.
     *
     * @var array
     */
    private $data = [];

    protected function __construct()
    {
        $this->fetch();
    }

    final private function __clone()
    {
    }

    public function __set($name, $value)
    {
        if ($this->has_property($name)) {
            $this->set_property($name, $value);
        } else {
            $this->{$name} = $value;
        }
    }

    public function __get($name)
    {
        if ($this->has_property($name)) {
            return $this->get_property($name);
        }

        return $this->{$name};
    }

    /**
     * @return \Podlove\Model\AssetAssignment
     */
    public static function get_instance()
    {
        return new self();
    }

    /**
     * Does the given property exist?
     *
     * @param string $name name of the property to test
     *
     * @return bool true if the property exists, else false
     */
    public function has_property($name)
    {
        return in_array($name, $this->property_names());
    }

    /**
     * Return a list of property names.
     *
     * @return array property names
     */
    public function property_names()
    {
        return array_map(function ($p) {
            return $p['name'];
        }, self::$properties);
    }

    /**
     * Define a property with by name.
     *
     * @param string $name Name of the property / column
     */
    public static function property($name)
    {
        if (!isset(self::$properties)) {
            self::$properties = [];
        }

        array_push(self::$properties, ['name' => $name]);
    }

    /**
     * Save current state to database.
     */
    public function save()
    {
        update_option('podlove_asset_assignment', $this->data);
    }

    /**
     * Generate a human readable title.
     *
     * Return name and, if available, the subtitle. Separated by a dash.
     *
     * @return string
     */
    public function full_title()
    {
        $t = $this->title;

        if ($this->subtitle) {
            $t = $t.' - '.$this->subtitle;
        }

        return $t;
    }

    private function set_property($name, $value)
    {
        $this->data[$name] = $value;
    }

    private function get_property($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Return a list of property dictionaries.
     *
     * @return array property list
     */
    private function properties()
    {
        if (!isset(self::$properties)) {
            self::$properties = [];
        }

        return self::$properties;
    }

    /**
     * Load podcast data.
     */
    private function fetch()
    {
        $this->data = get_option('podlove_asset_assignment', []);
    }
}

AssetAssignment::property('image');
AssetAssignment::property('chapters');
AssetAssignment::property('transcript');
