<?php
require_once 'vendor/autoload.php';

use DeviceDetector\DeviceDetector;

$userAgent = $argv[1];
$dd = new DeviceDetector($userAgent);
$dd->parse();

if ($dd->isBot()) {
	var_dump($botInfo = $dd->getBot());
} else {
	$clientInfo = $dd->getClient(); // holds information about browser, feed reader, media player, ...
	$osInfo = $dd->getOs();
	$device = $dd->getDevice();
	$brand = $dd->getBrand();
	$model = $dd->getModel();
	var_dump($clientInfo, $osInfo, $device, $brand, $model);
}