<?php
namespace Podlove\Modules\Transcripts;

use Podlove\Model\Episode;
use Podlove\Modules\Transcripts\Model\Transcript;

/**
 * Transcript renderer.
 * 
 * Renders an episode transcript as JSON or webvtt.
 * 
 * EXAMPLE
 * 
 *     $renderer = new Renderer($episode);
 *     
 *     header("Content-Type: text/vtt");
 *     echo $renderer->as_webvtt();
 *     exit;
 */
class Renderer {

	private $episode;

	public function __construct(Episode $episode)
	{
		$this->episode = $episode;
	}

	/**
	 * Render transcript as JSON.
	 * 
	 * Supports two modes:
	 * 
	 *   - flat: same structure as webvtt, just as json
	 *   - grouped: all subsequent items with the same speaker are grouped
	 * 
	 * @param  string $mode 'flat' or 'grouped'
	 * @return string
	 */
	public function as_json($mode = 'flat')
	{
		return json_encode($this->get_data($mode));		
	}

	public function as_xml()
	{
		$xml = new \SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>' 
			. '<pst:transcripts version="1.0" xmlns:pst="http://podlove.org/simple-transcripts" />'
		);

		$data = $this->get_data('grouped');

		foreach ($data as $group) {
			$groupXML = $xml->addChild('pst:speech');
			$groupXML->addChild('pst:speaker', $group['speaker']);
			foreach ($group['items'] as $item) {
				$child = $groupXML->addChild('pst:item', $item['text']);
				$child->addAttribute('start', $item['start']);
				$child->addAttribute('end', $item['end']);
			}
		}

		$xml_string = $xml->asXML();
		$xml_string = $this->format_xml($xml_string);

		return $xml_string;		
	}

	private function format_xml( $xml ) {
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML( $xml );
		return $dom->saveXML();
	}

	private function get_data($mode = 'flat')
	{
		return Transcript::prepare_transcript(
			Transcript::get_transcript($this->episode->id), 
			$mode
		);
	}

	public function as_webvtt()
	{
		$transcript = Transcript::get_transcript($this->episode->id);
		$transcript = array_map(function ($t) {

			$voice = $t->voice ? "<v {$t->voice}>" : "";

			return sprintf(
				"%s --> %s\n%s%s",
				self::format_time($t->start),
				self::format_time($t->end),
				$voice,
				$t->content
			);
		}, $transcript);

		return "WEBVTT\n\n" . implode("\n\n", $transcript) . "\n";
	}

	public static function format_time($time_ms)
	{
		$ms = $time_ms % 1000;
		$seconds = floor($time_ms / 1000) % 60;
		$minutes = floor($time_ms / (1000 * 60)) % 60;
		$hours = (int) floor($time_ms / (1000 * 60 * 60));

		return sprintf("%02d:%02d:%02d.%03d", $hours, $minutes, $seconds, $ms);
	}
}
