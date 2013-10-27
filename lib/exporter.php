<?php
namespace Podlove;

use Podlove\Model;

class Exporter {

	const XML_NAMESPACE = 'http://podlove.org/dtd/wordpress-publisher-export/1.0';

	public function __construct() {
		add_action('podlove_xml_export', array($this, 'exportEpisodes'));
		add_action('podlove_xml_export', array($this, 'exportAssets'));
		add_action('podlove_xml_export', array($this, 'exportFeeds'));
		add_action('podlove_xml_export', array($this, 'exportFileType'));
		add_action('podlove_xml_export', array($this, 'exportMediaFile'));
		add_action('podlove_xml_export', array($this, 'exportTemplates'));
		add_action('podlove_xml_export', array($this, 'exportOptions'));
	}

	public function download() {
		$this->setDownloadHeaders();
		echo $this->getXml();
		exit;
	}

	public function exportEpisodes(\SimpleXMLElement $xml) {
		$this->exportTable($xml, 'episodes', 'episode', '\Podlove\Model\Episode');
	}

	public function exportAssets(\SimpleXMLElement $xml) {
		$this->exportTable($xml, 'assets', 'asset', '\Podlove\Model\EpisodeAsset');
	}

	public function exportFeeds(\SimpleXMLElement $xml) {
		$this->exportTable($xml, 'feeds', 'feed', '\Podlove\Model\Feed');
	}

	public function exportFileType(\SimpleXMLElement $xml) {
		$this->exportTable($xml, 'filetypes', 'filetype', '\Podlove\Model\FileType');
	}

	public function exportMediaFile(\SimpleXMLElement $xml) {
		$this->exportTable($xml, 'mediafiles', 'mediafile', '\Podlove\Model\MediaFile');
	}

	public function exportTemplates(\SimpleXMLElement $xml) {
		$this->exportTable($xml, 'templates', 'template', '\Podlove\Model\Template');
	}

	public function exportOptions(\SimpleXMLElement $xml)
	{
		$options = array(
			'podlove',
			'podlove_active_modules',
			'podlove_asset_assignment',
			'podlove_metadata',
			'podlove_podcast',
			'podlove_template_assignment',
			'podlove_webplayer_formats',
			'podlove_webplayer_settings'
		);

		$xml_group = $xml->addChild('xmlns:wpe:options');
		foreach ($options as $option_name) {
			$value = get_option($option_name);
			if ($value !== false) {
				if (is_array($value)) {
					$xml_group->addChild("xmlns:wpe:$option_name", serialize($value));
				} else {
					$xml_group->addChild("xmlns:wpe:$option_name", $value);
				}
			}
		}
	}

	private function exportTable(\SimpleXMLElement $xml, $group_name, $item_name, $table_class)
	{
		$xml_group = $xml->addChild("xmlns:wpe:$group_name");
		foreach ($table_class::all() as $mediafile) {
			$xml_item = $xml_group->addChild("xmlns:wpe:$item_name");
			foreach ($table_class::property_names() as $property_name) {
				$xml_item->addChild("xmlns:wpe:$property_name", $mediafile->$property_name);
			}
		}
	}

	private function getDownloadFileName()
	{
		$sitename = sanitize_key(get_bloginfo('name'));
		
		if (!empty($sitename))
			$sitename .= '.';

		return $sitename . 'podlove.' . date( 'Y-m-d' ) . '.xml';
	}

	private function setDownloadHeaders() {		
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $this->getDownloadFileName() );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		header( 'Cache-control: private' );
		header( 'Expires: -1' );
	}

	public function getXml() {
		$xml = new \SimpleXMLElement('<xml/>');
		// Double xmlns looks strange but is intentionally/required.
		// See http://stackoverflow.com/a/9391673/72448
		$xml->addAttribute('xmlns:xmlns:wpe', self::XML_NAMESPACE);

		do_action('podlove_xml_export', $xml);
		return $xml->asXML();
	}
}