<?php

namespace Podlove\Modules\Contributors\Settings\Tab;

use Podlove\Settings\Expert\Tab;

class Defaults extends Tab
{
    public function get_slug()
    {
        return 'defaults';
    }

    public function init()
    {
        $this->page_type = 'custom';
        add_action('podlove_expert_settings_page', [$this, 'register_page']);
    }

    public function register_page()
    {
        $this->object = $this->getObject();
        $this->object->page();
    }

    public function getObject()
    {
        return new \Podlove\Modules\Contributors\Settings\ContributorDefaults('podlove_contributor_settings');
    }
}
