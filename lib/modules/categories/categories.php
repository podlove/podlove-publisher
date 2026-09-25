<?php

namespace Podlove\Modules\Categories;

class Categories extends \Podlove\Modules\Base
{
    protected $module_group = 'metadata';

    public function get_module_name()
    {
        return __('Categories', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function get_module_description()
    {
        return __('Enable categories for episodes.', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function load()
    {
        add_filter('podlove_post_type_args', function ($args) {
            $args['taxonomies'][] = 'category';

            return $args;
        });
    }
}
