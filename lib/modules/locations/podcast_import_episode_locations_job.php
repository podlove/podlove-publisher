<?php

namespace Podlove\Modules\Locations;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Export\PodcastExporter;
use Podlove\Modules\Locations\Model\Location;

/**
 * Podlove Publisher import job: episode_location table.
 *
 * Pairs with {@see Locations::expandExportFile()} XML shape.
 */
class PodcastImportEpisodeLocationsJob
{
    use JobTrait;

    /**
     * @var \SimpleXMLElement
     */
    protected $xml;

    public function setup()
    {
        $this->load_import_xml();
        $this->hooks['init'] = [$this, 'init_job'];
    }

    public static function title()
    {
        return __('Podcast Import: Episode Locations', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function description()
    {
        return __('Imports episode location records (subject & creator).', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function init_job()
    {
        Location::delete_all();
        $this->job->state = 0;
    }

    public function get_total_steps()
    {
        $items = $this->xml->xpath('//wpe:episode_location');

        return is_array($items) ? count($items) : 0;
    }

    protected function do_step()
    {
        $items = $this->xml->xpath('//wpe:episode_location');
        if (!is_array($items) || !isset($items[$this->job->state])) {
            ++$this->job->state;

            return 1;
        }

        $item = $items[$this->job->state];
        $data = [];
        foreach ($item->children('wpe', true) as $attribute) {
            $data[$attribute->getName()] = (string) $attribute;
        }

        global $wpdb;
        $table = Location::table_name();

        $episode_id = isset($data['episode_id']) ? (int) $data['episode_id'] : 0;
        if ($episode_id < 1) {
            ++$this->job->state;

            return 1;
        }

        $row = [
            'episode_id' => $episode_id,
            'rel' => isset($data['rel']) ? $data['rel'] : 'subject',
            'location_name' => $data['location_name'] ?? '',
            'location_lat' => $data['location_lat'] ?? '',
            'location_lng' => $data['location_lng'] ?? '',
            'location_address' => $data['location_address'] ?? '',
            'location_country' => $data['location_country'] ?? '',
            'location_osm' => $data['location_osm'] ?? '',
        ];

        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if (!empty($data['id'])) {
            $row['id'] = (int) $data['id'];
            $formats[] = '%d';
        }

        $wpdb->insert($table, $row, $formats);

        ++$this->job->state;

        return 1;
    }

    private function load_import_xml()
    {
        $file = get_option('podlove_import_file');
        if (!$file || !is_readable($file)) {
            $this->xml = new \SimpleXMLElement('<wpe:export/>');

            return;
        }

        $gzFileHandler = gzopen($file, 'r');
        if ($gzFileHandler === false) {
            $this->xml = new \SimpleXMLElement('<wpe:export/>');

            return;
        }

        $decompressed = gzread($gzFileHandler, self::gz_file_size($file));
        gzclose($gzFileHandler);

        $loaded = simplexml_load_string($decompressed);
        $this->xml = $loaded ?: new \SimpleXMLElement('<wpe:export/>');
        $this->xml->registerXPathNamespace('wpe', PodcastExporter::XML_NAMESPACE);
    }

    private static function gz_file_size($filename)
    {
        $gzfs = 0;
        if (($zp = fopen($filename, 'r')) !== false) {
            if (fread($zp, 2) == "\x1F\x8B") {
                fseek($zp, -4, SEEK_END);
                $datum = fread($zp, 4);
                if (strlen($datum) === 4) {
                    extract(unpack('Vgzfs', $datum));
                }
            } else {
                $gzfs = filesize($filename);
            }
            fclose($zp);
        }

        return $gzfs;
    }
}
