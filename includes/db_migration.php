<?php

/**
 * Execute migration query. Captures error if one occurs.
 *
 * @param string $sql
 */
function podlove_do_migration_query($sql)
{
    global $wpdb;

    $success = $wpdb->query($sql);

    if ($success === false) {
        update_option('podlove_db_migration_error', [
            'error' => $wpdb->last_error,
            'query' => $wpdb->last_query,
        ]);
    }

    return (bool) $success;
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

    ?>
  <div class="notice notice-error">
    <p>
      <?php echo __('An error occurred during a Podlove Podcast Publisher database migration.', 'podlove-podcasting-plugin-for-wordpress'); ?>
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
