<?php

use Podlove\Modules\Base;
use Podlove\Modules\TitleMigration\Notices;
use Podlove\Modules\TitleMigration\State;
use Podlove\Modules\TitleMigration\Title_Migration;

/**
 * @internal
 *
 * @coversNothing
 */
class TitleMigrationAuthorizationTest extends WP_UnitTestCase
{
    private $original_active_modules;
    private $original_request;
    private $original_state;

    public function setUp(): void
    {
        parent::setUp();

        $this->original_active_modules = get_option('podlove_active_modules');
        $this->original_state = get_option(State::OPTION);
        $this->original_request = $_REQUEST;
        Base::activate('title_migration');
        update_option(State::OPTION, State::FINISHED);
    }

    public function tearDown(): void
    {
        if ($this->original_active_modules === false) {
            delete_option('podlove_active_modules');
        } else {
            update_option('podlove_active_modules', $this->original_active_modules);
        }

        if ($this->original_state === false) {
            delete_option(State::OPTION);
        } else {
            update_option(State::OPTION, $this->original_state);
        }

        $_REQUEST = $this->original_request;
        wp_set_current_user(0);

        parent::tearDown();
    }

    public function testSubscriberCannotDeactivateModuleWithValidNonce(): void
    {
        $subscriber_id = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);
        $_REQUEST = [
            'podlove_disable_title_migration_module' => 1,
            '_wpnonce' => wp_create_nonce(Title_Migration::DEACTIVATE_NONCE_ACTION),
        ];

        Title_Migration::instance()->load();

        $this->assertTrue(Base::is_active('title_migration'));
        $this->assertSame(State::FINISHED, get_option(State::OPTION));
    }

    public function testAdministratorCanDeactivateModuleWithValidNonce(): void
    {
        $administrator_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($administrator_id);
        $_REQUEST = [
            'podlove_disable_title_migration_module' => 1,
            '_wpnonce' => wp_create_nonce(Title_Migration::DEACTIVATE_NONCE_ACTION),
        ];

        Title_Migration::instance()->load();

        $this->assertFalse(Base::is_active('title_migration'));
        $this->assertSame(State::FINISHED_HIDDEN, get_option(State::OPTION));
    }

    public function testGeneratedActionUrlsContainValidNonces(): void
    {
        $administrator_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($administrator_id);
        $_REQUEST['page'] = '"><script>alert(1)</script>';

        $disable_url = html_entity_decode(Notices::disable_module_url());
        $hide_url = html_entity_decode(Notices::hide_message_url(State::FINISHED_HIDDEN));
        parse_str((string) parse_url($disable_url, PHP_URL_QUERY), $disable_query);
        parse_str((string) parse_url($hide_url, PHP_URL_QUERY), $hide_query);

        $this->assertNotFalse(wp_verify_nonce($disable_query['_wpnonce'], Title_Migration::DEACTIVATE_NONCE_ACTION));
        $this->assertNotFalse(wp_verify_nonce($hide_query['_wpnonce'], Title_Migration::STATE_NONCE_ACTION));
        $this->assertSame('scriptalert1script', $hide_query['page']);
    }
}
