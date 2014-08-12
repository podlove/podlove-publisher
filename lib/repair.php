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
		self::fix_duplicate_services();
		self::fix_duplicate_contributions();

		wp_redirect(admin_url('admin.php?page=' . $_REQUEST['page']));
		exit;
	}

	private static function clear_repair_log() {
		update_option(self::REPAIR_LOG_KEY, array());
	}

	private static function add_to_repair_log($message) {
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

	private static function fix_duplicate_services()
	{
		global $wpdb;

		// @FIMXE: mhh, shouldn't this be hookable and dealt with in the module?
		$services = self::find_duplicate_services();

		if (!is_array($services) || empty($services)) {
			self::add_to_repair_log(__('Services did not need repair', 'podlove'));
			return;
		}

		foreach ($services as $service) {
			# update contributor services
			$sql = "UPDATE " . Social\Model\ContributorService::table_name() . " SET service_id = " . $service['id'] . " WHERE service_id IN (
				SELECT id FROM " . Social\Model\Service::table_name() . " WHERE `type` = \"" . $service['type'] . "\"
			)";
			$wpdb->query($sql);

			# update show services
			$sql = "UPDATE " . Social\Model\ShowService::table_name() . " SET service_id = " . $service['id'] . " WHERE service_id IN (
				SELECT id FROM " . Social\Model\Service::table_name() . " WHERE `type` = \"" . $service['type'] . "\"
			)";
			$wpdb->query($sql);

			# delete obsolete services
			$sql = "DELETE FROM " . Social\Model\Service::table_name() . " WHERE id != " . $service['id'] . " AND `type` = \"" . $service['type'] . "\"";
			$wpdb->query($sql);
		}

		self::add_to_repair_log(
			sprintf(
				__('Consolidated duplicate services (%s)', 'podlove'),
				implode(', ', array_map(function($s){ return $s['type']; }, $services))
			)
		);
	}

	private static function fix_duplicate_contributions()
	{
		global $wpdb;

		$contributions = self::find_duplicate_episode_contributions();

		if (!is_array($contributions) || empty($contributions)) {
			self::add_to_repair_log(__('Contributions did not need repair', 'podlove'));
			return;
		}

		foreach ($contributions as $contribution) {
			$sql = "
				DELETE FROM
					" . \Podlove\Modules\Contributors\Model\EpisodeContribution::table_name() . "
				WHERE
					id != " . $contribution['id'] . "
					AND `contributor_id` = \"" . $contribution['contributor_id'] . "\"
					AND `episode_id` = \"" . $contribution['episode_id'] . "\"
					AND `role_id` = \"" . $contribution['role_id'] . "\"
					AND `group_id` = \"" . $contribution['group_id'] . "\"
				";
			$wpdb->query($sql);

			$ec = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_by_id($contribution['id']);
			$ec->save(); // recalculates contribution count
		}

		self::add_to_repair_log(
			sprintf(
				_n( 'Deleted 1 duplicate contribution', 'Deleted %s duplicate contributions', count($contributions), 'podlove' ),
				count($contributions)
			)
		);
	}

	private static function find_duplicate_episode_contributions() {
		global $wpdb;

		$sql = "
			SELECT
				id, contributor_id, episode_id, role_id, group_id, COUNT(*) cnt
			FROM
				" . \Podlove\Modules\Contributors\Model\EpisodeContribution::table_name() . "
			GROUP BY
				contributor_id, episode_id, role_id, group_id
			HAVING
				cnt > 1
			ORDER BY
				cnt DESC
		";
		return $wpdb->get_results($sql, ARRAY_A);
	}

	private static function find_duplicate_services() {
		global $wpdb;

		$sql = "SELECT id, `type`, COUNT(*) cnt FROM " . \Podlove\Modules\Social\Model\Service::table_name() . " GROUP BY `type` HAVING cnt > 1";
		return $wpdb->get_results($sql, ARRAY_A);
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
				<li>
					<strong>fix data inconsistencies</strong>
					If you have duplicate data (for example a service or contribution appearing twice), this might fix it.
				</li>
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