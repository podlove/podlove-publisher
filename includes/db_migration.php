<?php

/**
 * Execute migration query. Captures error if one occurs.
 *
 * @param string $sql
 */
function podlove_do_migration_query($sql)
{
    global $wpdb;

    $result = $wpdb->query($sql);

    if ($result !== false) {
        return true;
    }

    $error = $wpdb->last_error;
    $query = $wpdb->last_query;
    $classification = podlove_classify_migration_failure($sql, $error);

    if ($classification['severity'] === 'ignored') {
        podlove_log_ignored_migration_failure($error, $query, $classification);

        return true;
    }

    update_option('podlove_db_migration_error', [
        'error' => $error,
        'query' => $query,
        'category' => $classification['category'],
    ]);

    return false;
}

function podlove_classify_migration_failure($sql, $error)
{
    if (preg_match("/Duplicate column name '([^']+)'/i", $error, $matches)) {
        $parsed = podlove_parse_add_column_query($sql);
        $column = $parsed['column'] ?? $matches[1];

        if ($parsed && podlove_migration_column_exists($parsed['table'], $column)) {
            return [
                'severity' => 'ignored',
                'category' => 'already_applied',
                'message' => 'Column already exists.',
            ];
        }
    }

    if (preg_match("/Duplicate key name '([^']+)'/i", $error, $matches)) {
        $parsed = podlove_parse_create_index_query($sql);
        $index = $parsed['index'] ?? $matches[1];

        if ($parsed && podlove_migration_index_exists($parsed['table'], $index)) {
            return [
                'severity' => 'ignored',
                'category' => 'already_applied',
                'message' => 'Index already exists.',
            ];
        }
    }

    if (preg_match("/Table '.*' already exists/i", $error)) {
        $table = podlove_parse_create_table_query($sql);

        if ($table && podlove_migration_table_exists($table)) {
            return [
                'severity' => 'ignored',
                'category' => 'already_applied',
                'message' => 'Table already exists.',
            ];
        }
    }

    if (preg_match('/(?:command denied|access denied|permission)/i', $error)) {
        return [
            'severity' => 'error',
            'category' => 'permission',
        ];
    }

    if (preg_match('/Row size too large/i', $error)) {
        return [
            'severity' => 'error',
            'category' => 'row_size',
        ];
    }

    if (preg_match("/Table '.*' doesn't exist/i", $error)) {
        return [
            'severity' => 'error',
            'category' => 'missing_table',
        ];
    }

    return [
        'severity' => 'error',
        'category' => 'unknown',
    ];
}

function podlove_log_ignored_migration_failure($error, $query, $classification)
{
    if (!class_exists('\Podlove\Log')) {
        return;
    }

    \Podlove\Log::get()->addInfo(
        'Ignored harmless database migration error.',
        [
            'error' => $error,
            'query' => $query,
            'category' => $classification['category'],
            'message' => $classification['message'] ?? '',
        ]
    );
}

function podlove_parse_add_column_query($sql)
{
    if (!preg_match('/^\s*ALTER\s+TABLE\s+(`(?:[^`]|``)+`|[^\s]+)\s+ADD\s+(?:COLUMN\s+)?(?!COLUMN\b)(`(?:[^`]|``)+`|[a-zA-Z0-9_]+\b)/i', $sql, $matches)) {
        return null;
    }

    return [
        'table' => podlove_unquote_migration_identifier($matches[1]),
        'column' => podlove_unquote_migration_identifier($matches[2]),
    ];
}

function podlove_parse_create_index_query($sql)
{
    if (!preg_match('/^\s*CREATE\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?INDEX\s+(`(?:[^`]|``)+`|[^\s]+)\s+ON\s+(`(?:[^`]|``)+`|[^\s(]+)/i', $sql, $matches)) {
        return null;
    }

    return [
        'index' => podlove_unquote_migration_identifier($matches[1]),
        'table' => podlove_unquote_migration_identifier($matches[2]),
    ];
}

function podlove_parse_create_table_query($sql)
{
    if (!preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(`(?:[^`]|``)+`|[^\s(]+)/i', $sql, $matches)) {
        return null;
    }

    return podlove_unquote_migration_identifier($matches[1]);
}

function podlove_unquote_migration_identifier($identifier)
{
    $identifier = trim($identifier);

    if (strlen($identifier) >= 2 && $identifier[0] === '`' && substr($identifier, -1) === '`') {
        return str_replace('``', '`', substr($identifier, 1, -1));
    }

    return $identifier;
}

function podlove_quote_migration_identifier($identifier)
{
    $parts = explode('.', $identifier);
    $parts = array_map(function ($part) {
        return '`'.str_replace('`', '``', $part).'`';
    }, $parts);

    return implode('.', $parts);
}

function podlove_migration_column_exists($table, $column)
{
    global $wpdb;

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            'SHOW COLUMNS FROM '.podlove_quote_migration_identifier($table).' LIKE %s',
            $column
        )
    );
}

function podlove_migration_index_exists($table, $index)
{
    global $wpdb;

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            'SHOW INDEX FROM '.podlove_quote_migration_identifier($table).' WHERE Key_name = %s',
            $index
        )
    );
}

function podlove_migration_table_exists($table)
{
    global $wpdb;

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $table
        )
    );
}

function podlove_database_migration_error_category_label($category)
{
    switch ($category) {
        case 'permission':
            return __('Database permission issue', 'podlove-podcasting-plugin-for-wordpress');
        case 'row_size':
            return __('Database row size limit', 'podlove-podcasting-plugin-for-wordpress');
        case 'missing_table':
            return __('Missing database table', 'podlove-podcasting-plugin-for-wordpress');
        case 'already_applied':
            return __('Migration already applied', 'podlove-podcasting-plugin-for-wordpress');

        default:
            return __('Unknown database issue', 'podlove-podcasting-plugin-for-wordpress');
    }
}

add_action('admin_notices', 'podlove_show_database_migration_error');

function podlove_show_database_migration_error()
{
    $data = get_option('podlove_db_migration_error');

    if (!$data || !isset($data['error']) || !isset($data['query'])) {
        return;
    }

    if (isset($_REQUEST['podlove_hide_migration_error']) && $_REQUEST['podlove_hide_migration_error']) {
        delete_option('podlove_db_migration_error');

        return;
    }

    $category = $data['category'] ?? 'unknown';

    ?>
  <div class="notice notice-error">
    <p>
      <?php echo __('An error occurred during a Podlove Podcast Publisher database migration.', 'podlove-podcasting-plugin-for-wordpress'); ?>
    </p>
    <p>
    <?php echo __('Issue type', 'podlove-podcasting-plugin-for-wordpress'); ?>: <code><?php echo esc_html(podlove_database_migration_error_category_label($category)); ?></code>
    </p>
    <p>
    <?php echo __('Error', 'podlove-podcasting-plugin-for-wordpress'); ?>: <code><?php echo esc_html($data['error']); ?></code>
    </p>
    <p>
    <?php echo __('Query', 'podlove-podcasting-plugin-for-wordpress'); ?>: <code><?php echo esc_html($data['query']); ?></code>
    </p>
    <p>
      <?php echo sprintf(
          __('The plugin might not fully work until this is resolved. If you do not know what to do, ask for help in the forums: %s', 'podlove-podcasting-plugin-for-wordpress'),
          '<a href="https://community.podlove.org/" target="_blank">community.podlove.org</a>'
      ); ?>
    </p>
    <p>
      <a href="<?php echo podlove_hide_migration_error_url(); ?>"><?php echo __('hide this message', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
      </p>
  </div>
  <?php
}

function podlove_hide_migration_error_url()
{
    if (isset($_REQUEST['page']) && $_REQUEST['page']) {
        return admin_url('admin.php?page='.$_REQUEST['page'].'&podlove_hide_migration_error=1');
    }

    return admin_url('?podlove_hide_migration_error=1');
}
