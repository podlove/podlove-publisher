<?php

class PluginActivationTest extends WP_UnitTestCase
{
    public function test_activation_enables_default_modules()
    {
        delete_option('podlove_active_modules');

        $result = activate_plugin('podlove-podcasting-plugin-for-wordpress/podlove.php');
        $this->assertNull($result, 'Plugin activation failed.');

        $expected_modules = [
            'logging',
            'podlove_web_player',
            'open_graph',
            'plus',
            'oembed',
            'import_export',
            'subscribe_button',
            'automatic_numbering',
            'onboarding',
        ];

        foreach ($expected_modules as $module) {
            $this->assertTrue(
                \Podlove\Modules\Base::is_active($module),
                sprintf('Expected module "%s" to be active after activation.', $module)
            );
        }
    }
}
