<?php
namespace Podlove;

use \Podlove\Modules\Social;
use \Podlove\Model;

class Repair {

	const REPAIR_LOG_KEY = 'podlove_repair_log';

	/**
	 * Register hooks.
	 */
	public static function init()
	{
		self::maybe_repair();
	}

	public static function maybe_repair()
	{
		if (isset($_GET['repair']) && $_GET['repair'])
			self::do_repair();
	}

	public static function do_repair()
	{
		self::clear_repair_log();

		self::clear_podlove_cache();
		self::clear_podlove_image_cache();
		self::flush_rewrite_rules();
		self::remove_duplicate_episodes();

		// hook for modules to add their repair methods
		do_action('podlove_repair_do_repair');

		wp_redirect(admin_url('admin.php?page=' . filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING)));
		exit;
	}

	private static function clear_repair_log() {
		update_option(self::REPAIR_LOG_KEY, array());
	}

	public static function add_to_repair_log($message) {
		$log = get_option(self::REPAIR_LOG_KEY, array());
		$log[] = $message;
		update_option(self::REPAIR_LOG_KEY, $log);
	}

	public static function clear_podlove_cache() {
		$cache = \Podlove\Cache\TemplateCache::get_instance();
		$cache->setup_purge();
		self::add_to_repair_log(__('Podlove cache cleared', 'podlove-podcasting-plugin-for-wordpress'));
	}

	public static function clear_podlove_image_cache() {
		\Podlove\Model\Image::flush_cache();
		self::add_to_repair_log(__('Podlove image cache cleared', 'podlove-podcasting-plugin-for-wordpress'));
	}

	private static function flush_rewrite_rules() {
		flush_rewrite_rules();
		self::add_to_repair_log(__('Rewrite rules flushed', 'podlove-podcasting-plugin-for-wordpress'));
	}

	// this should create a conflict with user aided resolution
	public static function remove_duplicate_episodes() {
		global $wpdb;

		// find duplicate episodes
		$sql = "SELECT post_id, COUNT(*) cnt FROM " . Model\Episode::table_name() . " GROUP BY post_id HAVING cnt > 1";
		$duplicate_post_ids = $wpdb->get_col($sql, 0);

		if ($duplicate_post_ids && count($duplicate_post_ids)) {
			foreach ($duplicate_post_ids as $post_id) {
				// only keep first created episode entry
				$sql = $wpdb->prepare(
					"DELETE FROM
						" . Model\Episode::table_name() . "
					WHERE post_id = %d AND id != (SELECT id FROM (
						SELECT
							id
						FROM
							" . Model\Episode::table_name() . "
						WHERE
							post_id = %d
						ORDER BY
							id ASC
						LIMIT 1
					) x)",
					$post_id, $post_id
				);
				$wpdb->query($sql);
			}
			self::add_to_repair_log(
				sprintf(
					__('Removed duplicate episode datasets (%s) You should verify that they are correct.', 'podlove-podcasting-plugin-for-wordpress'),
					implode(', ', array_map(function($post_id) {
						$link  = \get_edit_post_link($post_id);
						$title = \get_the_title($post_id);
						return sprintf('<a href="%s" target="_blank">%s</a>', $link, $title);
					}, $duplicate_post_ids))
				)
			);
		}
	}

	private static function print_and_clear_repair_log() {
		$log = get_option(self::REPAIR_LOG_KEY, array());

		if (empty($log))
			return;

		?>
		<div class="updated">
			<ul class="ul-disc">
				<?php foreach ( $log as $entry ): ?>
					<li>
						<?php echo $entry ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		self::clear_repair_log();
	}

	public static function page() {
		self::print_and_clear_repair_log();
		?>
		<p>
			<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&repair=1') ?>" class="button">
				<?php echo __( 'Attempt Repair', 'podlove-podcasting-plugin-for-wordpress' ) ?>
			</a>
		</p>
		<p>
			<?php echo __('There are a few occasional issues that are hard to avoid but easy to fix.
			To make resolving those issues easier, instead of giving you an instruction on what to do,
			pressing this button will attempt to fix it for you.
			This is what happens:', 'podlove-podcasting-plugin-for-wordpress'); ?>
			<ul class="ul-disc">
				<li>
					<strong><?php echo __('clears Podlove cache', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
					<?php echo __('Sometimes an issue is already fixed but you still see the faulty output. Clearing the cache avoids this. However, if you use a third party caching plugin, you should clear that cache, too.', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</li>
				<li>
					<strong><?php echo __('clears Podlove image cache', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
					<?php echo __('Podlove should notice automatically when an image changes and replace it after a while. If you want to enforce the refresh, this will do it.', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</li>
				<li>
					<strong><?php echo __('flushes WordPress rewrite rules', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
					<?php echo __('If you have strange behaviour in some sites or pages are not found which should exist, this might solve it.', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</li>
				<?php // hook for modules to add their repair method descriptions ?>
				<?php foreach ( apply_filters('podlove_repair_descriptions', array()) as $entry ): ?>
					<li><?php echo $entry; ?></li>
				<?php endforeach; ?>
			</ul>
			<?php echo __('Feel free to press this button as often as you like. Worst case scenario: nothing happens.', 'podlove-podcasting-plugin-for-wordpress') ?>
		</p>
		<?php
	}

}
