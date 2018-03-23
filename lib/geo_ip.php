<?php
namespace Podlove;

use GeoIp2\Database\Reader;
use Leth\IPAddress\IP, Leth\IPAddress\IPv4, Leth\IPAddress\IPv6;

class Geo_Ip {

	const GEO_FILENAME   = 'GeoLite2-City_20180206/GeoLite2-City.mmdb';
	const SOURCE_URL     = 'http://cdn.podlove.org/publisher/GeoLite2-City_20180206.tar.gz';

	/**
	 * Register hooks.
	 */
	public static function init()
	{
		add_filter( 'cron_schedules', array(__CLASS__, 'cron_add_monthly') );
		add_action( 'podlove_geoip_db_update', array(__CLASS__, 'update_database') );

		register_deactivation_hook( \Podlove\PLUGIN_FILE, array(__CLASS__, 'stop_updater_cron') );
		register_activation_hook( \Podlove\PLUGIN_FILE, array(__CLASS__, 'register_updater_cron') );
	}

	public static function register_updater_cron()
	{
		if (!wp_next_scheduled('podlove_geoip_db_update'))
			wp_schedule_event(time(), 'monthly', 'podlove_geoip_db_update');
	}

	public static function stop_updater_cron()
	{
		wp_clear_scheduled_hook('podlove_geoip_db_update');
	}

	public static function cron_add_monthly($schedules)
	{
		if (!isset($schedules['monthly'])) {
			$schedules['monthly'] = array(
				'interval' => 2635200,
				'display'  => __('Once a month', 'podlove-podcasting-plugin-for-wordpress')
			);
		}

		return $schedules;
	}

	/**
	 * Is tracking enabled?
	 * 
	 * @hook podlove_geo_tracking_is_enabled 
	 * 
	 * @return boolean
	 */
	public static function is_enabled()
	{
		$enabled = get_option('podlove_geo_tracking', 'on') === 'on';

		return apply_filters('podlove_geo_tracking_is_enabled', $enabled);
	}

	public static function disable_tracking()
	{
		update_option('podlove_geo_tracking', 'off');
	}

	public static function enable_tracking()
	{
		update_option('podlove_geo_tracking', 'on');
	}

	public static function is_db_valid()
	{
		try {
			$reader = new \GeoIp2\Database\Reader(self::get_upload_file_path());
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function get_upload_file_path()
	{
		return self::get_upload_file_dir()  . DIRECTORY_SEPARATOR . self::GEO_FILENAME;
	}

	public static function get_upload_file_dir()
	{
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'];
	}

	public static function update_database()
	{
		set_time_limit(0);

		// for download_url()
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$tmpFile = \download_url(self::SOURCE_URL);

		if (is_wp_error($tmpFile))
			die($tmpFile->get_error_message());

		// decompress from gz
		$p = new \PharData($tmpFile);
		$file2 = $p->decompress(); // creates files.tar
		 
		// unarchive from the tar
		$phar = new \PharData($file2->getPath());
		$phar->extractTo(self::get_upload_file_dir()); 

		self::enable_tracking();
	}
}
