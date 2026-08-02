<?php

use function Podlove\database_migration_request_is_authorized;
use function Podlove\database_migration_url;
use function Podlove\run_database_migrations;

use const Podlove\DATABASE_MIGRATION_NONCE_ACTION;
use const Podlove\DATABASE_VERSION;

/**
 * @internal
 *
 * @coversNothing
 */
class DatabaseMigrationAuthorizationTest extends WP_UnitTestCase
{
    private $original_database_version;
    private $original_request;

    public function setUp(): void
    {
        parent::setUp();

        $this->original_database_version = get_option('podlove_database_version');
        $this->original_request = $_REQUEST;
    }

    public function tearDown(): void
    {
        if ($this->original_database_version === false) {
            delete_option('podlove_database_version');
        } else {
            update_option('podlove_database_version', $this->original_database_version);
        }

        $_REQUEST = $this->original_request;
        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testMigrationUrlContainsValidNonce(): void
    {
        $url = database_migration_url('/wp-admin/edit.php');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('podlove_upgrade', $query['podlove_page']);
        $this->assertSame('/wp-admin/edit.php', $query['_wp_http_referer']);
        $this->assertNotFalse(wp_verify_nonce($query['_wpnonce'], DATABASE_MIGRATION_NONCE_ACTION));
    }

    public function testMigrationRequestRequiresUpgradeCapabilityAndNonce(): void
    {
        $administrator_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($administrator_id);
        $_REQUEST['_wpnonce'] = wp_create_nonce(DATABASE_MIGRATION_NONCE_ACTION);

        $this->assertTrue(database_migration_request_is_authorized());

        $subscriber_id = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);

        $this->assertFalse(database_migration_request_is_authorized());
    }

    public function testUnauthorizedRequestCannotRunPendingMigration(): void
    {
        update_option('podlove_database_version', DATABASE_VERSION - 1);
        $subscriber_id = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);
        $_REQUEST = [
            'podlove_page' => 'podlove_upgrade',
            '_wpnonce' => wp_create_nonce(DATABASE_MIGRATION_NONCE_ACTION),
        ];

        run_database_migrations();

        $this->assertSame(DATABASE_VERSION - 1, (int) get_option('podlove_database_version'));
    }
}
