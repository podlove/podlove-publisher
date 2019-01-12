<?php
namespace Podlove\Settings;

class Modules
{
    use \Podlove\HasPageDocumentationTrait;

    static $pagehook;

    public function __construct($handle)
    {
        Modules::$pagehook = add_submenu_page(
            /* $parent_slug*/$handle,
            /* $page_title */__('Modules', 'podlove-podcasting-plugin-for-wordpress'),
            /* $menu_title */__('Modules', 'podlove-podcasting-plugin-for-wordpress'),
            /* $capability */'administrator',
            /* $menu_slug  */'podlove_settings_modules_handle',
            /* $function   */array($this, 'page')
        );

        $this->init_page_documentation(self::$pagehook);

        add_settings_section(
            /* $id          */'podlove_settings_modules',
            /* $title      */'',
            /* $callback */function () { /* section head html */},
            /* $page     */Modules::$pagehook
        );

        $grouped_modules = array();
        $modules         = \Podlove\Modules\Base::get_all_module_names();
        foreach ($modules as $module_name) {
            $class = \Podlove\Modules\Base::get_class_by_module_name($module_name);

            if (!class_exists($class)) {
                continue;
            }

            if ($class::is_core()) {
                continue;
            }

            $module         = $class::instance();
            $module_options = $module->get_registered_options();

            if ($group = $module->get_module_group()) {
                add_settings_section(
                    'podlove_setting_module_group_' . $group,
                    ucwords($group),
                    function () {},
                    Modules::$pagehook);
            }

            if ($module_options) {
                register_setting(Modules::$pagehook, $module->get_module_options_name());
            }

            add_settings_field(
                /* $id       */'podlove_setting_module_' . $module_name,
                /* $title    */
                '<input name="podlove_active_modules[' . $module_name . ']" id="' . $module_name . '" type="checkbox" ' . checked(\Podlove\Modules\Base::is_active($module_name), true, false) . '>' .
                sprintf(
                    '<label for="' . $module_name . '">%s</label><a name="' . $module_name . '"></a>',
                    $module->get_module_name()
                ),
                /* $callback */function () use ($module, $module_name, $module_options) {
                    ?>
					<label for="<?php echo $module_name ?>">
						<?php echo $module->get_module_description() ?>
					</label>
					<?php

                    do_action('podlove_module_before_settings_' . $module_name);

                    if ($module_options) {

                        /**
                        ?><h4><?php echo __('Settings', 'podlove-podcasting-plugin-for-wordpress') ?></h4><?php
                         **/

                        // prepare settings object because form framework expects an object
                        $settings_object = new \stdClass();
                        foreach ($module_options as $key => $value) {
                            $settings_object->$key = $module->get_module_option($key);
                        }

                        \Podlove\Form\build_for($settings_object, array('context' => $module->get_module_options_name(), 'submit_button' => false, 'form' => false), function ($form) use ($module_options) {
                            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

                            foreach ($module_options as $module_option_name => $args) {
                                call_user_func_array(
                                    array($wrapper, $args['input_type']),
                                    array(
                                        $module_option_name,
                                        $args['args'],
                                    )
                                );
                            }

                        });
                    }

                    do_action('podlove_module_after_settings_' . $module_name);
                },
                /* $page     */Modules::$pagehook,
                /* $section  */$group ? 'podlove_setting_module_group_' . $group : 'podlove_settings_modules'
            );
        }

        register_setting(Modules::$pagehook, 'podlove_active_modules');
    }

    public function page()
    {
        ?>
		<div class="wrap">
			<h2><?php echo __('Podlove Publisher Modules', 'podlove-podcasting-plugin-for-wordpress') ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields(Modules::$pagehook);?>
				<?php do_settings_sections(Modules::$pagehook);?>

				<?php submit_button(__('Save Changes', 'podlove-podcasting-plugin-for-wordpress'), 'button-primary', 'submit', true);?>
			</form>
		</div>

<style>
form > .form-table > tbody > tr {
	background: white;
	display: block;
	margin-bottom: 2em;
	padding: 1.25em;
	box-shadow: 1px 1px 2px rgba(0,0,0, 0.1), 0px 0px 4px rgba(0,0,0, 0.05);
    max-width: 600px;
}

form > .form-table > tbody > tr > th,
form > .form-table > tbody > tr > td {
	display: block;
	padding: 0;
	margin: 0;
    width: 100%;
}

form > .form-table > tbody > tr > th {
    margin-bottom: 1.25em;
}

form > .form-table > tbody > tr > th label {
    margin-left: 0.5em;
    margin-top: -4px;
}

form > .form-table th label {
	font-size: 16px;
	display: inline-block;
}

form > .form-table h4 {
	font-size: 16px;
}

.form-table .form-table th {
    width: 150px;
}
</style>
		<?php
}

}
