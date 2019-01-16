<?php
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model\Episode;
use \Podlove\Modules\PodloveWebPlayer\PlayerV3\PlayerMediaFiles;

class MediaTagRenderer
{

    public function __construct(Episode $episode)
    {
        $this->episode = $episode;
    }

    public function render($context, $attributes = [])
    {
        $player_media_files = new PlayerMediaFiles($this->episode);
        $media_files        = $player_media_files->get($context);

        if (!$media_files) {
            return "";
        }

        // build main audio/video tag
        $xml = new \SimpleXMLElement('<' . $player_media_files->media_xml_tag . '/>');
        $xml->addAttribute('controls', 'controls');
        $xml->addAttribute('preload', 'none');

        if (count($attributes) > 0) {
            foreach ($attributes as $key => $value) {
                $xml->addAttribute($key, $value);
            }
        }

        // add all sources
        $xml = $this->add_sources($xml, $media_files);

        // prettify and prepare to render
        $xml_string = $xml->asXML();
        // TODO: use DomDocumentFragment
        $xml_string = $this->format_xml($xml_string);
        $xml_string = $this->remove_xml_header($xml_string);

        return $xml_string;
    }

    public function add_sources($xml, $files)
    {

        $flash_fallback_func = function (&$xml) {};

        foreach ($files as $file) {
            $mime_type = $file['mime_type'];

            $source = $xml->addChild('source');
            $source->addAttribute('src', $file['publicUrl']);
            $source->addAttribute('type', $mime_type);
        }

        return $xml;
    }

    private function format_xml($xml)
    {

        $dom                     = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML($xml);

        return $dom->saveXML();
    }

    private function remove_xml_header($xml)
    {
        return trim(str_replace('<?xml version="1.0"?>', '', $xml));
    }
}
