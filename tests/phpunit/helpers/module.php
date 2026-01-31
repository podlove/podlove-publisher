<?php

function podlove_test_activate_module(string $module_name, ?string $module_class = null): void
{
    $modules = get_option('podlove_active_modules');
    if (!is_array($modules)) {
        add_option('podlove_active_modules', []);
    }

    if ($module_class && class_exists($module_class)) {
        $module_class::instance()->load();
    }

    \Podlove\Modules\Base::deactivate($module_name);
    \Podlove\Modules\Base::activate($module_name);
}
