<?php

namespace Podlove;

/**
 * Provide help tabs for admin pages.
 *
 * Usage:
 *
 * ```
 * class MySettingsPage {
 *
 * 	use \Podlove\HasPageDocumentationTrait;
 *
 * 	public function __construct() {
 * 		// ...
 *		$this->init_page_documentation($pagehook);
 *	}
 *
 * }
 *
 * Then have a data file in a 'help' sudirectory:
 *
 * # ./help/my_settings_page.php
 * <?php
 * return [
 * 	'podlove_unique_tab_id' => [
 * 		'title'   => __('Tab Title', 'podlove-podcasting-plugin-for-wordpress'),
 * 		'content' => '<p>' . __('Tab Content', 'podlove-podcasting-plugin-for-wordpress') . '</p>'
 * 	]
 * ];
 * ```
 */
trait HasPageDocumentationTrait
{
    public function init_page_documentation($pagehook)
    {
        add_action('load-'.$pagehook, [$this, 'add_help_tabs']);
    }

    public function add_help_tabs()
    {
        get_current_screen()->set_help_sidebar(
            '<p><strong>'.__('For more information:').'</strong></p>'.
            '<p><a href="http://docs.podlove.org/podlove-publisher/" target="_blank">'.__('Podlove Publisher Documentation', 'podlove-podcasting-plugin-for-wordpress').'</a></p>'.
            '<p><a href="https://community.podlove.org/" target="_blank">'.__('Podlove Community', 'podlove-podcasting-plugin-for-wordpress').'</a></p>'
        );

        foreach ($this->get_help_tabs() as $id => $tab) {
            get_current_screen()->add_help_tab([
                'id' => $id,
                'title' => __($tab['title'], 'some_textdomain'),
                'callback' => function ($screen, $tab) {
                    echo $this->get_help_tabs()[$tab['id']]['content'];
                },
            ]);
        }
    }

    public static function help_file()
    {
        $inheriting_class_file = self::inheriting_class_file();

        return
            dirname($inheriting_class_file)
            .DIRECTORY_SEPARATOR
            .'help'
            .DIRECTORY_SEPARATOR
            .basename($inheriting_class_file);
    }

    public static function inheriting_class_file()
    {
        $class = new \ReflectionClass(get_called_class());

        return $class->getFileName();
    }

    private function get_help_tabs()
    {
        if (file_exists(self::help_file())) {
            return include self::help_file();
        }

        return [];
    }
}
