<?php

namespace Podlove\Settings;

use Podlove\Settings\Expert\Tabs;
use Podlove\Settings\Podcast\Tab;

class Podcast
{
    use \Podlove\HasPageDocumentationTrait;

    public static $pagehook;
    private $tabs;

    public function __construct($handle)
    {
        Podcast::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Podcast Settings', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Podcast Settings', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            'podlove_settings_podcast_handle',
            // $function
            [$this, 'page']
        );

        $this->init_page_documentation(self::$pagehook);

        add_settings_section(
            // $id
            'podlove_podcast_general',
            // $title
            __('Podcast Settings', 'podlove-podcasting-plugin-for-wordpress'),
            // $callback
            function () { // section head html
            },
            // $page
            Podcast::$pagehook
        );

        register_setting(Podcast::$pagehook, 'podlove_podcast', function ($podcast) {
            if ($podcast['media_file_base_uri']) {
                $podcast['media_file_base_uri'] = trailingslashit($podcast['media_file_base_uri']);
            }

            return $podcast;
        });

        $tabs = new Tabs(__('Podcast Settings', 'podlove-podcasting-plugin-for-wordpress'));
        $tabs->addTab(new Tab\Description('description', __('Description', 'podlove-podcasting-plugin-for-wordpress'), true));
        $tabs->addTab(new Tab\Media('media', __('Media', 'podlove-podcasting-plugin-for-wordpress')));
        $tabs->addTab(new Tab\Player('player', __('Player', 'podlove-podcasting-plugin-for-wordpress')));
        $tabs->addTab(new Tab\License('license', __('License', 'podlove-podcasting-plugin-for-wordpress')));
        $tabs->addTab(new Tab\Directory('directory', __('Directory', 'podlove-podcasting-plugin-for-wordpress')));
        $this->tabs = apply_filters('podlove_podcast_settings_tabs', $tabs);
        $this->tabs->initCurrentTab();
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
