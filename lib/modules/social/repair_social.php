<?php 
namespace Podlove\Modules\Social;

use Podlove\Repair;
use \Podlove\Modules\Social\Model\Service;
use \Podlove\Modules\Social\Model\ContributorService;

class RepairSocial {

	public static function init() {
		add_action('podlove_repair_do_repair', array(__CLASS__, 'fix_duplicate_services'));
		add_action('podlove_repair_do_repair', array(__CLASS__, 'fix_missing_services'));
		add_filter('podlove_repair_descriptions', array(__CLASS__, 'description'));
	}

	public static function description($descriptions) {
		return array_merge($descriptions, array("<strong>removes duplicate services</strong> if you have any"));
	}

	public static function fix_missing_services() {
		$count_before = Service::count();
		Social::build_missing_services();
		$count_after = Service::count();

		if ($count_before < $count_after) {
			Repair::add_to_repair_log(
				sprintf(
					__('Added %d missing social services', 'podlove'),
					$count_after - $count_before
				)
			);
		}
	}

	public static function fix_duplicate_services()
	{
		global $wpdb;

		$services = self::find_duplicate_services();

		if (!is_array($services) || empty($services)) {
			Repair::add_to_repair_log(__('Services did not need repair', 'podlove'));
			return;
		}

		foreach ($services as $service) {
			# update contributor services
			$sql = "UPDATE " . ContributorService::table_name() . " SET service_id = " . $service['id'] . " WHERE service_id IN (
				SELECT id FROM " . Service::table_name() . " WHERE `type` = \"" . $service['type'] . "\"
			)";
			$wpdb->query($sql);

			# update show services
			$sql = "UPDATE " . Model\ShowService::table_name() . " SET service_id = " . $service['id'] . " WHERE service_id IN (
				SELECT id FROM " . Service::table_name() . " WHERE `type` = \"" . $service['type'] . "\"
			)";
			$wpdb->query($sql);

			# delete obsolete services
			$sql = "DELETE FROM " . Service::table_name() . " WHERE id != " . $service['id'] . " AND `type` = \"" . $service['type'] . "\"";
			$wpdb->query($sql);
		}

		Repair::add_to_repair_log(
			sprintf(
				__('Consolidated duplicate services (%s)', 'podlove'),
				implode(', ', array_map(function($s){ return $s['type']; }, $services))
			)
		);
	}

	private static function find_duplicate_services() {
		global $wpdb;

		$sql = "SELECT id, `type`, COUNT(*) cnt FROM " . Service::table_name() . " GROUP BY `type` HAVING cnt > 1";
		return $wpdb->get_results($sql, ARRAY_A);
	}

}