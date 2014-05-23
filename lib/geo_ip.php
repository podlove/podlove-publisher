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
	 *
	 * @todo  initial database fetch
	 * @todo  update-cron or regular notification for users (ideal: cron+conflict system to handle errors)
	 */
	public static function init()
	{
		/*
		// self::update_database();

		$ip = IP\Address::factory('182.74.40.62');
		if (method_exists($ip, 'as_IPv6_address')) {
			$ip = $ip->as_IPv6_address();
		}
		$ipv6_string = $ip->format(IP\Address::FORMAT_COMPACT);

		var_dump($ipv6_string);

		echo "<pre>";
		$reader = new Reader(self::get_upload_file_path());
		$record = $reader->city($ipv6_string);
		var_dump($record);
		// var_dump($record->mostSpecificSubdivision);
		// var_dump($record->city);
		// var_dump($record->postal);
		// var_dump($record->location);
		echo "</pre>";
		exit;

		// file_put_contents('/tmp/php.log', print_r("\n" . "Starting GeoIP db update ...\n", true), FILE_APPEND | LOCK_EX);
		*/
	}

	public static function is_db_valid()
	{
		$original_md5 = wp_remote_fopen(self::SOURCE_MD5_URL);
		$our_md5      = md5_file(self::get_upload_file_path());

		return $original_md5 == $our_md5;
	}

	public static function get_upload_file_path()
	{
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::GEO_FILENAME;
	}

	public static function update_database()
	{
		set_time_limit(0);
		$outFile = self::get_upload_file_path();

		// for download_url()
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$tmpFile = \download_url(self::SOURCE_URL);

		if (is_wp_error($tmpFile))
			die($tmpFile->get_error_message());

		$zh = gzopen($tmpFile, 'rb');
		$h  = fopen($outFile, 'wb');

		if (!$zh)
			die('Downloaded file could not be opened for reading.');

		if (!$h)
			die(sprintf('Database could not be written (%s).', $outFile));

		while(!gzeof($zh))
		    fwrite($h, gzread($zh, 4096));

		gzclose($zh);
		fclose($h);

		unlink($tmpFile);

		if (!self::is_db_valid())
			die(sprintf('Checksum does not match (%s).', $outFile));
	}

}