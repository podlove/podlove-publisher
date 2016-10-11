<?php
namespace Podlove\Modules\ImportExport\Export;

use Podlove\Model;

class PodcastExporter {

	const XML_NAMESPACE = 'http://podlove.org/podlove-podcast-publisher/export';
	private $compression = false;

	public static function init() {

		if (!is_admin())
			return;
		
		if (isset($_GET['podlove_export']) && $_GET['podlove_export']) {
			$exporter = new \Podlove\Modules\ImportExport\Export\PodcastExporter;
			$exporter->download();
			exit;
		}
	}

	public function __construct() {
		add_action('podlove_xml_export', array($this, 'exportEpisodes'));
		add_action('podlove_xml_export', array($this, 'exportAssets'));
		add_action('podlove_xml_export', array($this, 'exportFeeds'));
		add_action('podlove_xml_export', array($this, 'exportFileType'));
		add_action('podlove_xml_export', array($this, 'exportMediaFile'));
		add_action('podlove_xml_export', array($this, 'exportTemplates'));
		add_action('podlove_xml_export', array($this, 'exportTracking'));
		add_action('podlove_xml_export', array($this, 'exportOptions'));

		if (function_exists('gzencode') && extension_loaded('zlib'))
			$this->enableCompression();
	}

	public function enableCompression() {
		$this->compression = true;
	}

	public function isCompressionEnabled() {
		return (bool) $this->compression;
	}

	public function download() {
		$this->setDownloadHeaders();
		$xml = $this->getXml();

		if ($this->isCompressionEnabled()) {
			echo gzencode($xml);
		} else {
			echo $xml;
		}
		exit;
	}

	public function exportEpisodes(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'episodes', 'episode', '\Podlove\Model\Episode');
	}

	public function exportAssets(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'assets', 'asset', '\Podlove\Model\EpisodeAsset');
	}

	public function exportFeeds(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'feeds', 'feed', '\Podlove\Model\Feed');
	}

	public function exportFileType(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'filetypes', 'filetype', '\Podlove\Model\FileType');
	}

	public function exportMediaFile(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'mediafiles', 'mediafile', '\Podlove\Model\MediaFile');
	}

	public function exportTemplates(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'templates', 'template', '\Podlove\Model\Template');
	}

	public function exportTracking(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'geoareas', 'geoarea', '\Podlove\Model\GeoArea');
		self::exportTable($xml, 'geoareanames', 'geoareaname', '\Podlove\Model\GeoAreaName');
		self::exportTable($xml, 'useragents', 'useragent', '\Podlove\Model\UserAgent');
	}

	public function exportOptions(\SimpleXMLElement $xml)
	{
		global $wpdb;
		$sql = 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE "%podlove%" AND option_name NOT LIKE "_transient%"';
		$options = $wpdb->get_col($sql);

		$xml_group = $xml->addChild('xmlns:wpe:options');
		foreach ($options as $option_name) {
			$value = get_option($option_name);
			if ($value !== false) {
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						// `addChild` does not escape '&', so we need to escape
						// it *before* serializing, otherwise deserialization will
						// break due to string length mismatch.
						if (is_string($v)) {
							$value[$k] = htmlspecialchars($v);
						}
					}
					$xml_group->addChild("xmlns:wpe:$option_name", serialize($value));
				} else {
					$value = htmlspecialchars($value);
					$xml_group->addChild("xmlns:wpe:$option_name", $value);
				}
			}
		}
	}

	public static function exportTable(\SimpleXMLElement $xml, $group_name, $item_name, $table_class)
	{
		$xml_group = $xml->addChild("xmlns:wpe:$group_name");
		foreach ($table_class::all() as $mediafile) {
			$xml_item = $xml_group->addChild("xmlns:wpe:$item_name");
			foreach ($table_class::property_names() as $property_name) {
				
				if (strlen($mediafile->$property_name) === 0)
					continue;

				$value = htmlspecialchars($mediafile->$property_name);
				$xml_item->addChild("xmlns:wpe:$property_name", $value);
			}
		}
	}

	private function getDownloadFileName()
	{
		$sitename = sanitize_key(get_bloginfo('name'));
		
		if (!empty($sitename))
			$sitename .= '.';

		$filename = $sitename . 'podlove.' . date( 'Y-m-d' ) . '.xml';

		if ($this->isCompressionEnabled()) {
			$filename .= '.gz';
		}

		return $filename;
	}

	private function setDownloadHeaders() {		
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $this->getDownloadFileName() );
		header( 'Cache-control: private' );
		header( 'Expires: -1' );

		if ($this->isCompressionEnabled()) {
			// Do *not* send gzip headers. Why? If you set gzip headers, the data is 
			// transferred compressed but unzipped before it's saved to disk. But we
			// want it to be compressed as a file, not just for transfer.
			
			// header( 'Content-Encoding: gzip' );
			// header( 'Content-Type: application/x-gzip; charset=' . get_option( 'blog_charset' ), true );
		} else {
			header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		}
	}

	public function getXml() {
		$xml = new \SimpleXMLElement('<wpe:export/>', LIBXML_NOERROR | LIBXML_NOWARNING, false, 'wpe', true);
		// Double xmlns looks strange but is intentionally/required.
		// See http://stackoverflow.com/a/9391673/72448
		$xml->addAttribute('xmlns:xmlns:wpe', self::XML_NAMESPACE);
		$xml->addAttribute('version', '1.0');
		$xml->addAttribute('podlove-publisher-version', \Podlove\get_plugin_header( 'Version' ));

		// add comments
		$comment = "\n\tExport Date: " . date('r');
		$comment.= "\n\t";

		$dom = dom_import_simplexml($xml);
		$commentElement = $dom->ownerDocument->createComment($comment);
		$dom->appendChild($commentElement);

		do_action('podlove_xml_export', $xml);

		// return formatted
		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->formatOutput = true;
		return $dom->saveXML();
	}
}
