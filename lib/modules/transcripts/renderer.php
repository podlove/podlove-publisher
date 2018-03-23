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

	public function as_json()
	{
		$transcript = Transcript::get_transcript($this->episode->id);
		$transcript = array_map(function ($t) {
			return [
				'start' => self::format_time($t->start),
				'end' => self::format_time($t->end),
				'speaker' => $t->identifier,
				'text' => $t->content,
			];
		}, $transcript);
		return json_encode($transcript);			
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

		return "WEBVTT\n\n" . implode("\n\n", $transcript);
	}

	private static function format_time($time_ms)
	{
		$ms = $time_ms % 1000;
		$seconds = floor($time_ms / 1000) % 60;
		$minutes = floor($time_ms / (1000 * 60)) % 60;
		$hours = (int) floor($time_ms / (1000 * 60 * 60));

		return sprintf("%02d:%02d:%02d.%03d", $hours, $minutes, $seconds, $ms);
	}
}
