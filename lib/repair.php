<?php
namespace Podlove;

use \Podlove\Modules\Social;

class Repair {

	const REPAIR_LOG_KEY = 'podlove_repair_log';

	/**
	 * Register hooks.
	 */
	public static function init()
	{
		add_action('podlove_support_repair_html', array(__CLASS__, 'page'));
		
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
		self::flush_rewrite_rules();

		// hook for modules to add their repair methods
		do_action('podlove_repair_do_repair');

		wp_redirect(admin_url('admin.php?page=' . $_REQUEST['page']));
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

	private static function clear_podlove_cache() {
		$cache = \Podlove\Cache\TemplateCache::get_instance();
		$cache->setup_purge();
		self::add_to_repair_log(__('Podlove cache cleared', 'podlove'));
	}

	private static function flush_rewrite_rules() {
		flush_rewrite_rules();
		self::add_to_repair_log(__('Rewrite rules flushed', 'podlove'));
	}

	private static function print_and_clear_repair_log() {
		$log = get_option(self::REPAIR_LOG_KEY, array());

		if (empty($log))
			return;

		?>
		<div class="updated">
			<h3>Repair Done</h3>
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
		<h3><?php echo __('Repair', 'podlove') ?></h3>

		<p>
			<?php echo __('There are a few occasional issues that are hard to avoid but easy to fix.
			To make resolving those issues easier, instead of giving you an instruction on what to do,
			pressing this button will attempt to fix it for you.
			This is what happens:', 'podlove'); ?>
			<ul class="ul-disc">
				<li>
					<strong>clears Podlove cache</strong>
					Sometimes an issue is already fixed but you still see the faulty output.
					Clearing the cache avoids this.
					However, if you use a third party caching plugin, you should clear that cache, too.
				</li>
				<li>
					<strong>flushes WordPress rewrite rules</strong>
					If you have strange behaviour in some sites or pages are not found which should exist, this might solve it.
				</li>
				<?php // hook for modules to add their repair method descriptions ?>
				<?php foreach ( apply_filters('podlove_repair_descriptions', array()) as $entry ): ?>
					<li><?php echo $entry; ?></li>
				<?php endforeach; ?>
			</ul>
			<?php echo __('Feel free to press this button as often as you like. Worst case scenario: nothing happens.', 'podlove') ?>
		</p>

		<p>
			<a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page'] . '&repair=1') ?>" class="button button-primary">
				<?php echo __( 'Attempt Repair', 'podlove' ) ?>
			</a>
		</p>
		<?php
	}

}