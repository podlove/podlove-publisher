<?php

/**
 * @internal
 *
 * @coversNothing
 */
class AuphonicCapabilitiesTest extends WP_UnitTestCase
{
    private $original_capabilities = [];

    public function setUp(): void
    {
        parent::setUp();

        foreach (['administrator', 'editor', 'author', 'contributor', 'subscriber'] as $role_name) {
            $role = get_role($role_name);
            $this->original_capabilities[$role_name] = $role->capabilities;
            $role->remove_cap('podlove_use_auphonic');
        }
    }

    public function tearDown(): void
    {
        wp_set_current_user(0);

        foreach ($this->original_capabilities as $role_name => $capabilities) {
            $role = get_role($role_name);
            $role->remove_cap('podlove_use_auphonic');
            if (isset($capabilities['podlove_use_auphonic'])) {
                $role->add_cap('podlove_use_auphonic', $capabilities['podlove_use_auphonic']);
            }
        }

        parent::tearDown();
    }

    public function testActivationGrantsAuphonicAccessToAdministratorsAndEditors(): void
    {
        podlove_init_capabilities();
        $this->assertDefaultAccess();
    }

    public function testUpgradeGrantsAuphonicAccessOnExistingInstallations(): void
    {
        \Podlove\run_migrations_for_version(170);
        \Podlove\run_migrations_for_version(170);
        $this->assertDefaultAccess();
    }

    public function testEditorBootstrapReflectsAuphonicCapability(): void
    {
        podlove_init_capabilities();

        foreach (['administrator' => true, 'editor' => true, 'author' => false] as $role => $expected) {
            wp_set_current_user($this->factory->user->create(['role' => $role]));
            $data = podlove_js_adapter_inject_permissions([]);
            $this->assertSame($expected, $data['permissions']['canUseAuphonic']);
        }

        wp_get_current_user()->add_cap('podlove_use_auphonic');
        $data = podlove_js_adapter_inject_permissions([]);
        $this->assertTrue($data['permissions']['canUseAuphonic']);

        wp_get_current_user()->remove_cap('podlove_use_auphonic');
        $data = podlove_js_adapter_inject_permissions([]);
        $this->assertFalse($data['permissions']['canUseAuphonic']);
    }

    private function assertDefaultAccess(): void
    {
        foreach (['administrator', 'editor'] as $role_name) {
            $this->assertTrue(get_role($role_name)->has_cap('podlove_use_auphonic'));
        }
        foreach (['author', 'contributor', 'subscriber'] as $role_name) {
            $this->assertFalse(get_role($role_name)->has_cap('podlove_use_auphonic'));
        }
    }
}
