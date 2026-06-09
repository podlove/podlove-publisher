<?php

/**
 * @internal
 *
 * @coversNothing
 */
class DatabaseMigrationQueryTest extends WP_UnitTestCase
{
    private $table;
    private $suppress_errors;

    public function setUp(): void
    {
        parent::setUp();

        global $wpdb;

        $this->table = $wpdb->prefix.'podlove_migration_helper_test';
        $this->suppress_errors = $wpdb->suppress_errors(true);

        delete_option('podlove_db_migration_error');
        $wpdb->query('DROP TABLE IF EXISTS '.podlove_quote_migration_identifier($this->table));
    }

    public function tearDown(): void
    {
        global $wpdb;

        $wpdb->query('DROP TABLE IF EXISTS '.podlove_quote_migration_identifier($this->table));
        delete_option('podlove_db_migration_error');
        $wpdb->suppress_errors($this->suppress_errors);

        parent::tearDown();
    }

    public function testQueryWithNoAffectedRowsIsSuccessful()
    {
        global $wpdb;

        $wpdb->query('CREATE TABLE '.podlove_quote_migration_identifier($this->table).' (`id` BIGINT, `name` VARCHAR(255))');

        $result = podlove_do_migration_query(
            'UPDATE '.podlove_quote_migration_identifier($this->table)." SET `name` = 'test' WHERE `id` = -1"
        );

        $this->assertTrue($result);
        $this->assertFalse(get_option('podlove_db_migration_error'));
    }

    public function testDuplicateColumnIsIgnoredWhenColumnExists()
    {
        global $wpdb;

        $wpdb->query('CREATE TABLE '.podlove_quote_migration_identifier($this->table).' (`existing_column` TINYINT)');

        $result = podlove_do_migration_query(
            'ALTER TABLE '.podlove_quote_migration_identifier($this->table).' ADD COLUMN `existing_column` TINYINT'
        );

        $this->assertTrue($result);
        $this->assertFalse(get_option('podlove_db_migration_error'));
    }

    public function testDuplicateIndexIsIgnoredWhenIndexExists()
    {
        global $wpdb;

        $wpdb->query('CREATE TABLE '.podlove_quote_migration_identifier($this->table).' (`id` BIGINT)');
        $wpdb->query('CREATE INDEX `existing_index` ON '.podlove_quote_migration_identifier($this->table).' (`id`)');

        $result = podlove_do_migration_query(
            'CREATE INDEX `existing_index` ON '.podlove_quote_migration_identifier($this->table).' (`id`)'
        );

        $this->assertTrue($result);
        $this->assertFalse(get_option('podlove_db_migration_error'));
    }

    public function testRealMigrationErrorIsStoredWithCategory()
    {
        $result = podlove_do_migration_query(
            'ALTER TABLE '.podlove_quote_migration_identifier($this->table).' ADD COLUMN `missing` TINYINT'
        );

        $this->assertFalse($result);

        $error = get_option('podlove_db_migration_error');

        $this->assertSame('missing_table', $error['category']);
        $this->assertStringContainsString('ALTER TABLE', $error['query']);
    }
}
