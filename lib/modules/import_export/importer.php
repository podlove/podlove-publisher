<?php
namespace Podlove\Modules\ImportExport;

use Podlove\Model;

class Importer {

	// path to import file
	private $file;

	// SimpleXML document of import file
	private $xml;

	public function __construct($file) {
		$this->file = $file;
	}

	public function import() {

		$this->xml = simplexml_load_file($this->file);
		$this->xml->registerXPathNamespace('wpe', \Podlove\Exporter::XML_NAMESPACE);

		$this->importEpisodes();
		$this->importOptions();
		$this->importFeeds();
		$this->importFileTypes();
		$this->importMediaFiles();
		$this->importTemplates();
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
				// no matching post found
			}
		}
	}

	private function importOptions()
	{
		$options = $this->xml->xpath('//wpe:options')[0]->children('wpe', true);
		foreach ($options as $option) {
			update_option($option->getName(), maybe_unserialize((string) $option));
		}
	}

	private function importFeeds()
	{
		Model\Feed::delete_all();

		$feeds = $this->xml->xpath('//wpe:feed');
		foreach ($feeds as $feed) {
			$new_feed = new Model\Feed;

			foreach ($feed->children('wpe', true) as $attribute) {
				$new_feed->{$attribute->getName()} = self::escape((string) $attribute);
			}

			$new_feed->save();
		}		
	}

	private function importFileTypes()
	{
		Model\FileType::delete_all();

		$filetypes = $this->xml->xpath('//wpe:filetype');
		foreach ($filetypes as $filetype) {
			$new_filetype = new Model\FileType;

			foreach ($filetype->children('wpe', true) as $attribute) {
				$new_filetype->{$attribute->getName()} = self::escape((string) $attribute);
			}

			$new_filetype->save();
		}		
	}

	private function importMediaFiles()
	{
		Model\MediaFile::delete_all();

		$mediafiles = $this->xml->xpath('//wpe:mediafile');
		foreach ($mediafiles as $mediafile) {
			$new_mediafile = new Model\MediaFile;

			foreach ($mediafile->children('wpe', true) as $attribute) {
				$new_mediafile->{$attribute->getName()} = self::escape((string) $attribute);
			}

			$new_mediafile->save();
		}
	}

	private function importTemplates()
	{
		Model\Template::delete_all();

		$templates = $this->xml->xpath('//wpe:template');
		foreach ($templates as $template) {
			$new_template = new Model\Template;

			foreach ($template->children('wpe', true) as $attribute) {
				$new_template->{$attribute->getName()} = self::escape((string) $attribute);
			}

			$new_template->save();
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