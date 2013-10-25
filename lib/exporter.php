<?php
namespace Podlove;

use Podlove\Model;

class Exporter {

	public function __construct() {
		add_action('podlove_xml_export', array($this, 'exportEpisodes'));
		add_action('podlove_xml_export', array($this, 'exportAssets'));
		add_action('podlove_xml_export', array($this, 'exportFeeds'));
		add_action('podlove_xml_export', array($this, 'exportFileType'));
		add_action('podlove_xml_export', array($this, 'exportMediaFile'));
		add_action('podlove_xml_export', array($this, 'exportTemplates'));
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

	private function exportTable(\SimpleXMLElement $xml, $group_name, $item_name, $table_class)
	{
		$xml_group = $xml->addChild($group_name);
		foreach ($table_class::all() as $mediafile) {
			$xml_item = $xml_group->addChild($item_name);
			foreach ($table_class::property_names() as $property_name) {
				$xml_item->addChild($property_name, $mediafile->$property_name);
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
		do_action('podlove_xml_export', $xml);
		return $xml->asXML();
	}
}