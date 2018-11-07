<?php 
namespace Podlove\Modules\PodloveWebPlayer\PlayerV3;

use Podlove\Model\Episode;
use Podlove\Modules\PodloveWebPlayer\MediaTagRenderer;

class Html5Printer implements \Podlove\Modules\PodloveWebPlayer\PlayerPrinterInterface {

	// Model\Episode
	private $episode;

	private $attributes = [];

	public function __construct(Episode $episode) {
		$this->episode = $episode;
	}

	public function setAttributes($attributes) {
		$this->attributes = $attributes;
	}

	public function render($context = NULL) {
		$media_xml = (new MediaTagRenderer($this->episode))->render($context, $this->attributes);
		return '<div class="podlove-player-wrapper">' . $media_xml . '</div>';
	}
}
