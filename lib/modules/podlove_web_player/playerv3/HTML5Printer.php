<?php 
namespace Podlove\Modules\PodloveWebPlayer\Playerv3;

use Podlove\Model\Episode;

class HTML5Printer {

	// Model\Episode
	private $episode;

	// determined player type, based on $files
	private $is_video = true;

	public function __construct(Episode $episode) {
		$this->episode = $episode;
	}

	public function render($context = NULL, $attributes = []) {

		$player_media_files = new PlayerMediaFiles($this->episode);
		$media_files = $player_media_files->get($context);

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

	public function add_sources($xml, $files) {

		$flash_fallback_func = function(&$xml) {};

		foreach ($files as $file) {
			$mime_type = $file['mime_type'];

			$source = $xml->addChild('source');
			$source->addAttribute('src', $file['publicUrl']);
			$source->addAttribute('type', $mime_type);

			if ($mime_type == 'audio/mpeg') {
				$flash_fallback_func = function(&$xml) use ($file) {
					$flash_fallback = $xml->addChild('object');
					$flash_fallback->addAttribute('type', 'application/x-shockwave-flash');
					$flash_fallback->addAttribute('data', plugins_url('bin/', __FILE__) . 'flashmediaelement.swf');

					$params = [
						[
							'name' => 'movie',
							'value' => plugins_url('player/podlove-web-player/static/', __FILE__) . 'flashmediaelement.swf'
						],
						[
							'name' => 'flashvars',
							'value' => 'controls=true&file=' . $file['url']
						]
					];

					foreach ($params as $param) {
						$p = $flash_fallback->addChild('param');
						$p->addAttribute('name', $param['name']);
						$p->addAttribute('value', $param['value']);
					}
					
				};
			}
		}
		// add flash fallback after all <source>s
		$flash_fallback_func($xml);

		return $xml;
	}

	private function format_xml( $xml ) {

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML( $xml );

		return $dom->saveXML();
	}

	private function remove_xml_header( $xml ) {
		return trim( str_replace( '<?xml version="1.0"?>', '', $xml ) );
	}
}
