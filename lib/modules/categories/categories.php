<?php

namespace Podlove\Modules\Categories;

class Categories extends \Podlove\Modules\Base
{
    protected $module_name = 'Categories';
    protected $module_description = 'Enable categories for episodes.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_filter('podlove_post_type_args', function ($args) {
            $args['taxonomies'][] = 'category';

            return $args;
        });
    }
}
