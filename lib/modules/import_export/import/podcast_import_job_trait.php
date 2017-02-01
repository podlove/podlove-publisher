<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Modules\ImportExport\Export\PodcastExporter;

trait PodcastImportJobTrait {

	protected $xml;

	private function setupXml()
	{
		$file = get_option('podlove_import_file');

		$gzFileHandler = gzopen($file, 'r');
		$decompressed = gzread($gzFileHandler, self::gzfilesize($file));
		gzclose($gzFileHandler);

		$this->xml = simplexml_load_string($decompressed);

		$this->xml->registerXPathNamespace('wpe', PodcastExporter::XML_NAMESPACE);
	}

	private static function gzfilesize($filename)
	{
		if (($zp = fopen($filename, 'r'))!==FALSE) {
			if (@fread($zp, 2) == "\x1F\x8B") { // this is a gzip'd file
				fseek($zp, -4, SEEK_END);
				if (strlen($datum = @fread($zp, 4))==4)
				  extract(unpack('Vgzfs', $datum));
			} else { // not a gzip'd file, revert to regular filesize function
				$gzfs = filesize($filename);
			}
			fclose($zp);
		}
		return($gzfs);
	}

	private static function escape($value)
	{
		global $wpdb;
		$wpdb->escape_by_ref($value);
		return $value;
	}

}
