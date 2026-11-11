<?php

namespace Podlove\Modules\ImportExport\Export;

class PodcastExporter
{
    public const XML_NAMESPACE = 'http://podlove.org/podlove-podcast-publisher/export';
    private $compression = false;

    public function __construct()
    {
        add_action('podlove_xml_export', [$this, 'exportEpisodes']);
        add_action('podlove_xml_export', [$this, 'exportAssets']);
        add_action('podlove_xml_export', [$this, 'exportFeeds']);
        add_action('podlove_xml_export', [$this, 'exportFileType']);
        add_action('podlove_xml_export', [$this, 'exportMediaFile']);
        add_action('podlove_xml_export', [$this, 'exportTemplates']);
        add_action('podlove_xml_export', [$this, 'exportTracking']);
        add_action('podlove_xml_export', [$this, 'exportOptions']);

        if (function_exists('gzencode') && extension_loaded('zlib')) {
            $this->enableCompression();
        }
    }

    public static function init()
    {
        if (!is_admin()) {
            return;
        }

        if (isset($_GET['podlove_export']) && $_GET['podlove_export']) {
            if (!current_user_can('administrator')) {
                wp_die(__('You are not allowed to export podcast data.', 'podlove-podcasting-plugin-for-wordpress'), '', ['response' => 403]);
            }

            if (!wp_verify_nonce($_REQUEST['_podlove_nonce'] ?? '', 'podlove_export')) {
                wp_die(__('This export link has expired. Reload the Tools page and try again.', 'podlove-podcasting-plugin-for-wordpress'), '', ['response' => 403]);
            }

            $exporter = new \Podlove\Modules\ImportExport\Export\PodcastExporter();
            $exporter->download();
            exit;
        }
    }

    public function enableCompression()
    {
        $this->compression = true;
    }

    public function isCompressionEnabled()
    {
        return (bool) $this->compression;
    }

    public function download()
    {
        try {
            $file = $this->prepareDownload();
        } catch (\Throwable $error) {
            error_log('Podlove podcast export failed: '.$error->getMessage());
            wp_die(__('The podcast export could not be prepared. Please try again. If the problem persists, check the server logs for database, memory or temporary storage errors.', 'podlove-podcasting-plugin-for-wordpress'), '', ['response' => 500]);

            return;
        }

        $this->setDownloadHeaders();
        header('Content-Length: '.fstat($file)['size']);
        fpassthru($file);
        fclose($file);
        exit;
    }

    /**
     * Build the entire download before sending attachment headers.
     *
     * The temporary file is deleted when its handle is closed, including on shutdown.
     * Keeping serialization and compression on disk avoids two additional XML-sized strings.
     *
     * @return resource
     */
    public function prepareDownload()
    {
        $file = tmpfile();
        if ($file === false) {
            throw new \RuntimeException('Unable to create a temporary podcast export file.');
        }

        try {
            $document = $this->getXmlDocument();
            $path = stream_get_meta_data($file)['uri'];
            $destination = $this->isCompressionEnabled() ? 'compress.zlib://'.$path : $path;
            if ($document->save($destination) === false) {
                throw new \RuntimeException('Unable to write the podcast export file.');
            }
            if (fstat($file)['size'] === 0 || !rewind($file)) {
                throw new \RuntimeException('The podcast export file is empty or unreadable.');
            }

            return $file;
        } catch (\Throwable $error) {
            fclose($file);

            throw $error;
        }
    }

    public function exportEpisodes(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'episodes', 'episode', '\Podlove\Model\Episode');
    }

    public function exportAssets(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'assets', 'asset', '\Podlove\Model\EpisodeAsset');
    }

    public function exportFeeds(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'feeds', 'feed', '\Podlove\Model\Feed');
    }

    public function exportFileType(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'filetypes', 'filetype', '\Podlove\Model\FileType');
    }

    public function exportMediaFile(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'mediafiles', 'mediafile', '\Podlove\Model\MediaFile');
    }

    public function exportTemplates(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'templates', 'template', '\Podlove\Model\Template');
    }

    public function exportTracking(\SimpleXMLElement $xml)
    {
        self::exportTable($xml, 'geoareas', 'geoarea', '\Podlove\Model\GeoArea');
        self::exportTable($xml, 'geoareanames', 'geoareaname', '\Podlove\Model\GeoAreaName');
        self::exportTable($xml, 'useragents', 'useragent', '\Podlove\Model\UserAgent');
    }

    public function exportOptions(\SimpleXMLElement $xml)
    {
        global $wpdb;
        $sql = 'SELECT option_name FROM '.$wpdb->options.' WHERE option_name LIKE "%podlove%" AND option_name NOT LIKE "_transient%"';
        $options = $wpdb->get_col($sql);
        if ($wpdb->last_error) {
            throw new \RuntimeException('Unable to read podcast options for export.');
        }

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
                    $xml_group->addChild("xmlns:wpe:{$option_name}", serialize($value));
                } else {
                    if (is_object($value)) {
                        $value = maybe_serialize($value);
                    }

                    $value = htmlspecialchars($value);
                    $xml_group->addChild("xmlns:wpe:{$option_name}", $value);
                }
            }
        }
    }

    public static function exportTable(\SimpleXMLElement $xml, $group_name, $item_name, $table_class)
    {
        global $wpdb;

        $xml_group = $xml->addChild("xmlns:wpe:{$group_name}");
        $lastId = 0;
        $batchSize = 500;
        do {
            $rows = $table_class::all('WHERE id > '.(int) $lastId.' ORDER BY id ASC LIMIT '.$batchSize);
            if ($wpdb->last_error) {
                throw new \RuntimeException('Unable to read podcast data for export.');
            }
            foreach ($rows as $mediafile) {
                $xml_item = $xml_group->addChild("xmlns:wpe:{$item_name}");
                foreach ($table_class::property_names() as $property_name) {
                    if (is_null($mediafile->{$property_name}) || strlen((string) $mediafile->{$property_name}) === 0) {
                        continue;
                    }

                    $value = htmlspecialchars((string) $mediafile->{$property_name}, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
                    $xml_item->addChild("xmlns:wpe:{$property_name}", $value);
                }
                $lastId = $mediafile->id;
            }
        } while (count($rows) === $batchSize);
    }

    public function getXml()
    {
        $xml = $this->getXmlDocument()->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Unable to serialize the podcast export.');
        }

        return $xml;
    }

    private function getXmlDocument()
    {
        $xml = new \SimpleXMLElement('<wpe:export/>', LIBXML_NOERROR | LIBXML_NOWARNING, false, 'wpe', true);
        // Double xmlns looks strange but is intentionally/required.
        // See http://stackoverflow.com/a/9391673/72448
        $xml->addAttribute('xmlns:xmlns:wpe', self::XML_NAMESPACE);
        $xml->addAttribute('version', '1.0');
        $xml->addAttribute('podlove-publisher-version', \Podlove\get_plugin_header('Version'));

        // add comments
        $comment = "\n\tExport Date: ".date('r');
        $comment .= "\n\t";

        $dom = dom_import_simplexml($xml);
        $commentElement = $dom->ownerDocument->createComment($comment);
        $dom->appendChild($commentElement);

        do_action('podlove_xml_export', $xml);

        // return formatted
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        return $dom;
    }

    private function getDownloadFileName()
    {
        $sitename = sanitize_key(get_bloginfo('name'));

        if (!empty($sitename)) {
            $sitename .= '.';
        }

        $filename = $sitename.'podlove.'.date('Y-m-d').'.xml';

        if ($this->isCompressionEnabled()) {
            $filename .= '.gz';
        }

        return $filename;
    }

    private function setDownloadHeaders()
    {
        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.$this->getDownloadFileName().'"');
        // The file is gzip, not the HTTP transfer: browsers must save it compressed.
        header('Content-Type: '.($this->isCompressionEnabled() ? 'application/gzip' : 'application/xml; charset=UTF-8'));
    }
}
