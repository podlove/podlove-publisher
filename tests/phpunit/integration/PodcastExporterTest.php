<?php

use Podlove\Modules\ImportExport\Export\PodcastExporter;

class PodcastExporterTestModel extends \Podlove\Model\Base
{
    public static function table_name()
    {
        global $wpdb;

        return $wpdb->prefix.'podlove_export_test';
    }

    public static function property_names()
    {
        return ['id', 'title'];
    }
}

/**
 * @internal
 *
 * @coversNothing
 */
class PodcastExporterTest extends WP_UnitTestCase
{
    private $exportHook;

    public function set_up(): void
    {
        parent::set_up();
        global $wp_filter, $wpdb;
        $this->exportHook = isset($wp_filter['podlove_xml_export']) ? clone $wp_filter['podlove_xml_export'] : null;
        remove_all_actions('podlove_xml_export');
        $wpdb->query('CREATE TEMPORARY TABLE '.PodcastExporterTestModel::table_name().' (id BIGINT PRIMARY KEY, title TEXT)');
    }

    public function tear_down(): void
    {
        global $wp_filter, $wpdb;
        remove_all_actions('podlove_xml_export');
        if ($this->exportHook !== null) {
            $wp_filter['podlove_xml_export'] = $this->exportHook;
        }
        $wpdb->query('DROP TEMPORARY TABLE IF EXISTS '.PodcastExporterTestModel::table_name());
        parent::tear_down();
    }

    public function testBatchedExportIncludesEveryRowWithGapsAndEscapesText(): void
    {
        global $wpdb;
        $values = [];
        for ($i = 1; $i <= 1001; ++$i) {
            $values[] = $wpdb->prepare('(%d, %s)', $i * 2, 'Rock & Roll <'.$i.'>');
        }
        $wpdb->query('INSERT INTO '.PodcastExporterTestModel::table_name().' VALUES '.implode(',', $values));
        $xml = new SimpleXMLElement('<wpe:export/>', LIBXML_NOERROR | LIBXML_NOWARNING, false, 'wpe', true);
        $xml->addAttribute('xmlns:xmlns:wpe', PodcastExporter::XML_NAMESPACE);
        PodcastExporter::exportTable($xml, 'items', 'item', PodcastExporterTestModel::class);
        $xml = simplexml_load_string($xml->asXML())->children(PodcastExporter::XML_NAMESPACE);
        $this->assertCount(1001, $xml->items->item);
        $this->assertSame('2', (string) $xml->items->item[0]->id);
        $this->assertSame('2002', (string) $xml->items->item[1000]->id);
        $this->assertSame('Rock & Roll <1001>', (string) $xml->items->item[1000]->title);
    }

    public function testEmptyTableProducesEmptyGroup(): void
    {
        $xml = new SimpleXMLElement('<wpe:export/>', LIBXML_NOERROR | LIBXML_NOWARNING, false, 'wpe', true);
        $xml->addAttribute('xmlns:xmlns:wpe', PodcastExporter::XML_NAMESPACE);
        PodcastExporter::exportTable($xml, 'items', 'item', PodcastExporterTestModel::class);
        $xml = simplexml_load_string($xml->asXML())->children(PodcastExporter::XML_NAMESPACE);
        $this->assertCount(0, $xml->items->item);
    }

    public function testDatabaseFailureDoesNotProduceAnApparentlySuccessfulEmptyExport(): void
    {
        global $wpdb;
        $wpdb->query('DROP TEMPORARY TABLE '.PodcastExporterTestModel::table_name());
        $previous = $wpdb->suppress_errors(true);

        try {
            $this->expectException(RuntimeException::class);
            PodcastExporter::exportTable(new SimpleXMLElement('<export/>'), 'items', 'item', PodcastExporterTestModel::class);
        } finally {
            $wpdb->suppress_errors($previous);
        }
    }

    public function testPreparedDownloadsPreserveModuleHooksAndCleanUpTemporaryFiles(): void
    {
        add_action('podlove_xml_export', function ($xml) {
            $xml->addChild('module-data', 'example');
        });
        foreach ([false, true] as $compressed) {
            $exporter = new class extends PodcastExporter {
                public function __construct() {}
            };
            if ($compressed) {
                $exporter->enableCompression();
            }
            $file = $exporter->prepareDownload();
            $path = stream_get_meta_data($file)['uri'];

            try {
                $content = stream_get_contents($file);
                $this->assertNotEmpty($content);
                $xml = simplexml_load_string($compressed ? gzdecode($content) : $content);
                $this->assertNotFalse($xml);
                $this->assertSame('example', (string) $xml->{'module-data'});
                $this->assertSame('1.0', (string) $xml['version']);
                $this->assertStringContainsString('<module-data>example</module-data>', $exporter->getXml());
            } finally {
                fclose($file);
            }
            $this->assertFileDoesNotExist($path);
        }
    }

    public function testHookFailureStopsPreparation(): void
    {
        add_action('podlove_xml_export', function () {
            throw new RuntimeException('Module export failed');
        });
        $exporter = new class extends PodcastExporter {
            public function __construct() {}
        };
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module export failed');
        $exporter->prepareDownload();
    }
}
