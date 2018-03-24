<?php 
namespace Podlove\Modules\Transcripts;

use \Podlove\Modules\Transcripts\Model;
use \Podlove\Modules\Transcripts\Template;

class TemplateExtensions {
	/**
	 * List of all Podcast shows
	 *
	 * **Examples**
	 * 
	 * ```
	 * <style>
	 * .ts-line { margin-bottom: 5px; }
	 * .ts-line .time { font-family: monospace; }
	 * </style>
	 * 
	 * {% for line in episode.transcript %}
	 *  <div class="ts-line">
	 *      <small>
	 *      <span class="time">{{ line.start }}&ndash;{{ line.end }}</span>
	 *      {% if line.contributor %}
	 *        <strong>{{ line.contributor.name }}</strong>
	 *      {% endif %}
	 *      </small>
	 *      <div>{{ line.content }}</div>
	 *  </div>
	 * {% endfor %}
	 * ```
	 *
	 * @accessor
	 * @dynamicAccessor episode.transcript
	 */
	public static function accessorEpisodeTranscript($return, $method_name, \Podlove\Model\Episode $episode) {
		return $episode->with_blog_scope(function() use ($return, $method_name, $episode) {
			return array_map(function($line) {
				return new Template\Line($line);
			}, Model\Transcript::get_transcript($episode->id));
		});
	}
}
