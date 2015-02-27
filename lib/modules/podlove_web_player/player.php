<?php
namespace Podlove\Modules\PodloveWebPlayer;

class Player {

	public $liburl;

	function __construct() {
		$liburl = '';
		$this->assets = array();
		$this->options = array(
				'alwaysShowHours' => true,
				'width' => 'auto',
				'summaryVisible' => false,
				'timecontrolsVisible' => false,
				'sharebuttonsVisible' => false,
				'chaptersVisible' => true,
				'loop' => false,
				'permalink' => '',
				'title' => '',
				'subtitle' => '',
				'summary' => '',
				'poster' => '',
				'width' => 'auto',
				'sharebuttonsVisible' => false,
				'chaptersVisible' => true,
				'show' => array(),
				'license' => '',
				'downloads' => '',
				'duration' => '',
				'chapterVisible' => false,
				'publicationDate' => '',
				'features' => array(
						'current', 'progress', 'duration', 'tracks', 'fullscreen', 'volume'
					),
				'chapters' => array(),
				'sources' => array(),
				'downloads' => array()
			);
	}

	public function getPlayer( $playerURL ) {
		$sources = "";
		$playerid = "podlove-web-player-" . get_the_id();
		foreach ($this->assets as $asset) {
			$this->options['sources'][] = array(
					'src' => $asset['url'],
					'type' => $asset['mimetype']
				);
			$sources .= "<source src='".$asset['url']."' type='".$asset['mimetype']."'/>\n";
		}

		return '<audio id="' . $playerid .'">
		            ' . $sources . '
		        </audio>
		        <script>pwp_metadata["' . $playerid . '"] = ' . json_encode($this->options) . ';</script>';
	}

	public function printPlayer( $playerURL ) {
		echo $this->getPlayer( $playerURL );
	}

}
