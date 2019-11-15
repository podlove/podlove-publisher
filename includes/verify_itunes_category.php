<?php
add_action('admin_notices', 'podlove_verify_itunes_category');

function podlove_verify_itunes_category()
{
    $podcast  = \Podlove\Model\Podcast::get();
    $category = $podcast->category_1;

    if (!$category) {
        return;
    }

    if (array_key_exists($category, \Podlove\Itunes\categories(false))) {
        return;
    }

    ?>
		<div class="notice notice-error">
			<p>
				<strong><?php echo __('Podlove Publisher Warning', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
				<br>
        <?php echo __('Apple iTunes has updated the list of existing podcast categories. Your previously selected category does not exist any more. Please choose a new one:', 'podlove-podcasting-plugin-for-wordpress'); ?>
        <br>
        <a href="<?php echo admin_url('admin.php?page=podlove_settings_podcast_handle&podlove_tab=directory') ?>">
          <?php echo __('Podcast Directory Settings', 'podlove-podcasting-plugin-for-wordpress') ?>
        </a>
			</p>
		</div>
    <?php
}
