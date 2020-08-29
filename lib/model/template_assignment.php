<?php

namespace Podlove\Model;

/**
 * Simplified Singleton model for template assignment data.
 */
class TemplateAssignment
{
    /**
     * Singleton instance container.
     *
     * @var null|\Podlove\Model\AssetAssignment
     */
    private static $instance = null;

    /**
     * Contains property values.
     *
     * @var array
     */
    private $data = [];

    /**
     * Contains property names.
     *
     * @var array
     */
    private $properties;

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
     * Singleton.
     *
     * @return \Podlove\Model\AssetAssignment
     */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function name()
    {
        return 'template_assignment';
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
        }, $this->properties);
    }

    /**
     * Define a property with by name.
     *
     * @param string $name Name of the property / column
     */
    public function property($name)
    {
        if (!isset($this->properties)) {
            $this->properties = [];
        }

        array_push($this->properties, ['name' => $name]);
    }

    /**
     * Save current state to database.
     */
    public function save()
    {
        update_option('podlove_template_assignment', $this->data);

        do_action('podlove_model_save', $this);
        do_action('podlove_model_change', $this);
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
        if (!isset($this->properties)) {
            $this->properties = [];
        }

        return $this->properties;
    }

    /**
     * Load podcast data.
     */
    private function fetch()
    {
        $this->data = get_option('podlove_template_assignment', []);
    }
}

$template_assignment = TemplateAssignment::get_instance();
$template_assignment->property('top');
$template_assignment->property('bottom');
$template_assignment->property('head');
$template_assignment->property('header');
$template_assignment->property('footer');

$template_assignment = apply_filters('podlove_model_template_assignment_schema', $template_assignment);
