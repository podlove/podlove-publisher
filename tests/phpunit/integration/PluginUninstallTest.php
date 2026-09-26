<?php

/**
 * @internal
 *
 * @coversNothing
 */
class PluginUninstallTest extends WP_UnitTestCase
{
    public function testUninstallVisitsEverySiteWithoutNetworkwideParameter()
    {
        global $wpdb;

        $original_get = $_GET;
        unset($_GET['networkwide']);
        $visited = [];
        $record_site = function () use (&$visited) {
            $visited[] = get_current_blog_id();
        };
        add_action('podlove_uninstall_plugin', $record_site);

        if (is_multisite()) {
            // Publisher still uses this legacy hook to initialize new sites.
            $this->setExpectedDeprecated('wpmu_new_blog');
            $this->factory()->blog->create();
            $caller = $this->factory()->blog->create();
            switch_to_blog($caller);
            $expected = array_map('intval', $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs));
        } else {
            $expected = [get_current_blog_id()];
        }

        $original_blog_id = get_current_blog_id();
        $original_stack = $GLOBALS['_wp_switched_stack'] ?? [];

        try {
            \Podlove\uninstall();

            $this->assertEqualsCanonicalizing($expected, $visited);
            $this->assertSame($original_blog_id, get_current_blog_id());
            $this->assertSame($original_stack, $GLOBALS['_wp_switched_stack'] ?? []);
        } finally {
            remove_action('podlove_uninstall_plugin', $record_site);
            $_GET = $original_get;
            if (is_multisite()) {
                restore_current_blog();
            }
        }
    }
}
