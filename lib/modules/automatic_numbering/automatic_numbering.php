<?php

namespace Podlove\Modules\AutomaticNumbering;

use Podlove\Model;

class Automatic_Numbering extends \Podlove\Modules\Base
{
    protected $module_name = 'Automatic Numbering';
    protected $module_description = 'Automatically increase the Episode number when creating episodes.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_filter('podlove_model_defaults', [$this, 'override_episode_attribute_defaults'], 10, 2);
    }

    public function override_episode_attribute_defaults(array $defaults, $model)
    {
        if ($model::name() !== Model\Episode::name()) {
            return $defaults;
        }

        return $this->append_episode_number_to_defaults($defaults, $model);
    }

    public function append_episode_number_to_defaults(array $defaults, Model\Episode $episode)
    {
        $next_number = Model\Episode::get_next_episode_number();
        $defaults['number'] = $next_number;

        return $defaults;
    }
}
