<?php

namespace Podlove\Modules\Contributors\Settings;

use Podlove\Settings\Expert\Tabs;

class ContributorSettings
{
    public static $pagehook;

    public function __construct($handle)
    {
        ContributorSettings::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Contributors', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Contributors', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            'podlove_contributor_settings',
            // $function
            [$this, 'page']
        );

        $is_settings_page = filter_input(INPUT_GET, 'page') == 'podlove_contributor_settings';
        $is_settings_update_request = filter_input(INPUT_POST, 'option_page') == ContributorSettings::$pagehook;

        if ($is_settings_page || $is_settings_update_request) {
            $tabs = new Tabs(__('Contributors', 'podlove-podcasting-plugin-for-wordpress'));
            $tabs->addTab(new \Podlove\Modules\Contributors\Settings\Tab\Contributors(__('Contributors', 'podlove-podcasting-plugin-for-wordpress'), true));
            $tabs->addTab(new \Podlove\Modules\Contributors\Settings\Tab\Groups(__('Groups', 'podlove-podcasting-plugin-for-wordpress')));
            $tabs->addTab(new \Podlove\Modules\Contributors\Settings\Tab\Roles(__('Roles', 'podlove-podcasting-plugin-for-wordpress')));
            $tabs->addTab(new \Podlove\Modules\Contributors\Settings\Tab\Defaults(__('Defaults', 'podlove-podcasting-plugin-for-wordpress')));

            $tabs = apply_filters('podlove_contributor_settings_tabs', $tabs);

            $this->tabs = $tabs;
            $this->tabs->initCurrentTab();

            foreach ($this->tabs->getTabs() as $tab) {
                if (method_exists($tab, 'getObject')) {
                    add_action('admin_init', [$tab->getObject(), 'process_form']);
                }
            }
        }
    }

    public function page()
    {
        ?>
		<div class="wrap">
			<?php
            echo $this->tabs->getTabsHTML();
        echo $this->tabs->getCurrentTabPage(); ?>
		</div>
		<?php
    }
}
