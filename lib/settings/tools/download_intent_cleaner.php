<?php
namespace Podlove\Settings\Tools;

use \Podlove\Model;
use \Podlove\Cache\TemplateCache;
use \Podlove\Analytics\DownloadIntentCleanup;

class DownloadIntentCleaner {

	public function __construct() {
		add_action('wp_ajax_podlove-downloadintentcleanup', [$this, 'cleanup'] );
	}

	public function cleanup() {
		Model\DownloadIntentClean::delete_all();
		DownloadIntentCleanup::cleanup_download_intents();
		TemplateCache::get_instance()->setup_purge();
		exit;
	}

}
