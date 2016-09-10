<?php
namespace Podlove;

use GeoIp2\Database\Reader;
use Leth\IPAddress\IP, Leth\IPAddress\IPv4, Leth\IPAddress\IPv6;

class Geo_Ip {

	const GEO_FILENAME   = 'geoip.mmdb';
	const SOURCE_URL     = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
	const SOURCE_MD5_URL = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.md5';

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

	// @todo it technically verifies that the files is valid AND up-to-date,
	// which may result in false-negatives. But that is fine with me, better 
	// than false positives.
	public static function is_db_valid($file_to_verify = null)
	{
		if ($file_to_verify === null) {
			$file_to_verify = self::get_upload_file_path();
		}

		$original_md5 = wp_remote_fopen(self::SOURCE_MD5_URL);
		$our_md5      = md5_file($file_to_verify);

		return $original_md5 === $our_md5;
	}

	public static function get_upload_file_path()
	{
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::GEO_FILENAME;
	}

	public static function get_tmp_file_path()
	{
		return self::get_upload_file_path() . '.tmp';
	}

	public static function update_database()
	{
		set_time_limit(0);

		// skip if database has not changed
		if (self::is_db_valid()) {
			return;
		}

		$tmpFilePath = self::get_tmp_file_path();

		// for download_url()
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$tmpFile = \download_url(self::SOURCE_URL);

		if (is_wp_error($tmpFile))
			die($tmpFile->get_error_message());

		$zh = gzopen($tmpFile, 'rb');
		$h  = fopen($tmpFilePath, 'wb');

		if (!$zh) {
			error_log(print_r('Downloaded file could not be opened for reading.', true));
			exit;
		}

		if (!$h) {
			error_log(print_r(sprintf('Database could not be written (%s).', $tmpFilePath), true));
			exit;
		}

		while(!gzeof($zh))
		    fwrite($h, gzread($zh, 4096));

		gzclose($zh);
		fclose($h);

		unlink($tmpFile);

		if (self::is_db_valid($tmpFilePath)) {
			@rename($tmpFilePath, self::get_upload_file_path());
			self::enable_tracking();
		} else {
			if (!self::is_db_valid()) {
				self::disable_tracking();
			}
			wp_delete_file($tmpFilePath);
			error_log(print_r(sprintf('Checksum does not match (%s).', $tmpFilePath), true));
		}
	}

}
