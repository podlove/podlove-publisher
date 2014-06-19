<?php
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Model;
use Podlove\Modules\ImportExport\Export\Podcast as Exporter;

class Podcast {

	// path to import file
	private $file;

	// SimpleXML document of import file
	private $xml;

	public static function init()
	{
		if (!isset($_FILES['podlove_import']))
			return;

		// allow xml uploads
		add_filter('upload_mimes', function ($mimes) {
		    return array_merge($mimes, array('xml' => 'application/xml'));
		});

		require_once ABSPATH . '/wp-admin/includes/file.php';
		 
		$file = wp_handle_upload($_FILES['podlove_import'], array('test_form' => false));
		if ($file) {
			update_option('podlove_import_file', $file['file']);
			if (!($file = get_option('podlove_import_file')))
				return;

			$importer = new \Podlove\Modules\ImportExport\Import\Podcast($file);
			$importer->import();
		} else {
			// file upload didn't work
		}
	}

	public function __construct($file) {
		$this->file = $file;
	}

	/**
	 * Import podcast metadata.
	 *
	 * A note on modules:
	 * Active modules are stored in wp_options "podlove_active_modules". When importing,
	 * we do not need special handling since module activation and deactivation is hooked
	 * to the "update_option_podlove_active_modules" filter. We only need to make sure
	 * options/modules are imported early enough.
	 */
	public function import() {

		$gzfilesize = function($filename) {
			$gzFilesize = FALSE;
			if (($zp = fopen($filename, 'r'))!==FALSE) {
				if (@fread($zp, 2) == "\x1F\x8B") { // this is a gzip'd file
					fseek($zp, -4, SEEK_END);
					if (strlen($datum = @fread($zp, 4))==4)
					  extract(unpack('Vgzfs', $datum));
				}
				else // not a gzip'd file, revert to regular filesize function
					$gzfs = filesize($filename);
				fclose($zp);
			}
			return($gzfs);
		};
		
		// It might not look like it, but it is actually compatible to 
		// uncompressed files.
		$gzFileHandler = gzopen($this->file, 'r');
		$decompressed = gzread($gzFileHandler, $gzfilesize($this->file));
		gzclose($gzFileHandler);

		$this->xml = simplexml_load_string($decompressed);

		$this->xml->registerXPathNamespace('wpe', Exporter::XML_NAMESPACE);

		$export = $this->xml->xpath('//wpe:export');
		$export = $export[0];

		if (isset($export["podlove-publisher-version"]) && (string) $export["podlove-publisher-version"] == \Podlove\get_plugin_header('Version')) {
			$status = "success";
		} else {
			$status = "version-warning";
		}

		$this->importEpisodes();
		$this->importOptions();
		$this->importFileTypes();
		$this->importAssets();
		$this->importFeeds();
		$this->importMediaFiles();
		// $this->importTracking();
		$this->importTemplates();

		do_action('podlove_xml_import', $this->xml);

		\Podlove\run_database_migrations();

		wp_redirect(admin_url('admin.php?page=podlove_imexport_migration_handle&status=' . $status));
		exit;
	}

	private function importEpisodes()
	{
		Model\Episode::delete_all();

		$episodes = $this->xml->xpath('//wpe:episode');
		foreach ($episodes as $episode) {
			$new_episode = new Model\Episode;

			foreach ($episode->children('wpe', true) as $attribute) {
				$new_episode->{$attribute->getName()} = self::escape((string) $attribute);
			}

			if ($new_post_id = $this->getNewPostId($new_episode->post_id)) {
				$new_episode->post_id = $new_post_id;
				$new_episode->save();
			} else {
				\Podlove\Log::get()->addWarning('Importer: no matching post for (old) post_id=' . $new_episode->post_id);
			}
		}
	}

	private function importOptions()
	{
		$wpe_options = $this->xml->xpath('//wpe:options');
		$options = $wpe_options[0]->children('wpe', true);
		foreach ($options as $option) {
			update_option($option->getName(), maybe_unserialize((string) $option));
		}
	}

	private function importAssets() {
		self::importTable($this->xml, 'asset', '\Podlove\Model\EpisodeAsset');
	}

	private function importFeeds() {
		self::importTable($this->xml, 'feed', '\Podlove\Model\Feed');
	}

	private function importFileTypes() {
		self::importTable($this->xml, 'filetype', '\Podlove\Model\FileType');	
	}

	private function importMediaFiles() {
		self::importTable($this->xml, 'mediafile', '\Podlove\Model\MediaFile');
	}

	private function importTracking() {
		self::importTable($this->xml, 'geoarea', '\Podlove\Model\GeoArea');
		self::importTable($this->xml, 'geoareaname', '\Podlove\Model\GeoAreaName');
		self::importTable($this->xml, 'useragent', '\Podlove\Model\UserAgent');
		self::importTable($this->xml, 'downloadintent', '\Podlove\Model\DownloadIntent');
	}

	private function importTemplates() {
		self::importTable($this->xml, 'template', '\Podlove\Model\Template');
	}

	public static function importTable($xml, $item_name, $table_class) {
		$table_class::delete_all();

		$group = $xml->xpath('//wpe:' . $item_name);

		foreach ($group as $item) {
			$new_item = new $table_class;

			foreach ($item->children('wpe', true) as $attribute) {
				$new_item->{$attribute->getName()} = self::escape((string) $attribute);
			}

			$new_item->save();
		}	
	}

	private static function escape($value) {
		global $wpdb;
		$wpdb->escape_by_ref($value);
		return $value;
	}

	/**
	 * Get mapping for post id after post import.
	 *
	 * When importing posts, their IDs might change.
	 * This function maps an existing post id to the new one.
	 * 
	 * @param  int      $old_post_id
	 * @return int|null post_id on success, otherwise null.
	 */
	private function getNewPostId($old_post_id)
	{
		$query_for_post_id = new \WP_Query(array(
			'post_type' => 'podcast',
			'meta_query' => array(
				array(
					'key' => 'import_id',
					'value' => $old_post_id,
					'compare' => '='
				)
			)
		));

		if ($query_for_post_id->have_posts()) {
			$p = $query_for_post_id->next_post();
			return $p->ID;
		} else {
			return null;
		}
	}

}