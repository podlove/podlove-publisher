<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model;
use Podlove\Model\Episode;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\MediaFile;

/**
 * Print HTML & stuff required for Podlove Web Player
 */
class Printer {

	// unique player index
	private static $index = 0;

	// unique player id
	private $html_id;

	// Model\Episode
	private $episode;

	private $audio_formats = array( 'mp3', 'mp4', 'ogg', 'opus' );
	private $video_formats = array( 'mp4', 'ogg', 'webm' );

	// determined player type, based on $files
	private $is_video = true;

	// List of Model\MediaFile
	private $files = array();

	public function __construct( Episode $episode ) {
		$this->episode = $episode;
		$this->player_format_assignments = $this->get_player_format_assignments();
		$this->files = $this->get_files();
	}

	public function render() {

		if ( count( $this->player_format_assignments ) == 0 )
			return '';

		// build main audio/video tag
		$xml = new \SimpleXMLElement( '<' . $this->get_media_tag() . '/>' );
		$xml->addAttribute( 'id', $this->get_html_id() );
		$xml->addAttribute( 'controls', 'controls' );

		$width  = strtolower( trim( $this->get_webplayer_setting( $this->get_media_tag(), 'width' ) ) );
		$height = strtolower( trim( $this->get_webplayer_setting( $this->get_media_tag(), 'height' ) ) );

		if ( $this->is_video ) {
			$xml->addAttribute( 'poster', $this->episode->get_cover_art_with_fallback() );
			$xml->addAttribute( 'width', $width );
			$xml->addAttribute( 'height', $height );
		} else {
			$xml->addAttribute(
				'style',
				sprintf(
					'width: %s; height: %s',
					empty( $width ) ||  $width == 'auto' ? 'auto' : $width . 'px',
					empty( $height ) ? '30px' : $height
				)
			);
		}

		// add all sources
		$flash_fallback_func = function( &$xml ) {};
		foreach ( $this->files as $file ) {
			$mime_type = $file->episode_asset()->file_type()->mime_type;

			$source = $xml->addChild('source');
			$source->addAttribute( 'src', $file->get_file_url() );
			$source->addAttribute( 'type', $mime_type );

			if ( $mime_type == 'audio/mpeg' ) {
				$flash_fallback_func = function( &$xml ) use ( $file ) {
					$flash_fallback = $xml->addChild('object');
					$flash_fallback->addAttribute( 'type', 'application/x-shockwave-flash' );
					$flash_fallback->addAttribute( 'data', 'flashmediaelement.swf' );

					$params = array(
						array( 'name' => 'movie', 'value' => 'flashmediaelement.swf' ),
						array( 'name' => 'flashvars', 'value' => 'controls=true&file=' . $file->get_file_url() )
					);

					foreach ( $params as $param ) {
						$p = $flash_fallback->addChild( 'param' );
						$p->addAttribute( 'name', $param['name'] );
						$p->addAttribute( 'value', $param['value'] );
					}
					
				};
			}
		}
		// add flash fallback after all <source>s
		$flash_fallback_func( $xml );

		// prettify and prepare to render
		$xml_string = $xml->asXML();
		$xml_string = $this->format_xml( $xml_string );
		$xml_string = $this->remove_xml_header( $xml_string );

		// set JavaScript options
		$truthy = array( true, 'true', 'on', 1, "1" );
		$init_options = array(
			'pluginPath'          => plugins_url( 'player/podlove-web-player/static/', __FILE__),
			'alwaysShowHours'     => true,
			'alwaysShowControls'  => true,
			'timecontrolsVisible' => false,
			'summaryVisible'      => false,
			'hidetimebutton'      => in_array( $this->get_webplayer_setting('buttons_time'), $truthy, true ),
			'hidedownloadbutton'  => in_array( $this->get_webplayer_setting('buttons_download'), $truthy, true ),
			'hidesharebutton'     => in_array( $this->get_webplayer_setting('buttons_share'), $truthy, true ),
			'sharewholeepisode'   => in_array( $this->get_webplayer_setting('buttons_sharemode'), $truthy, true ),
			'loop'                => false,
			'chapterlinks'        => 'all',
			'permalink'           => get_permalink(),
			'title'               => get_the_title(),
			'subtitle'            => wptexturize( convert_chars( trim( $this->episode->subtitle ) ) ),
			'summary'             => nl2br( wptexturize( convert_chars( trim( $this->episode->summary ) ) ) ),
			'poster'              => $this->episode->get_cover_art_with_fallback(),
			'duration'            => $this->episode->get_duration(),
			'chaptersVisible'     => \Podlove\get_webplayer_setting( 'chaptersVisible' ),
			'features'            => array( "current", "progress", "duration", "tracks", "fullscreen", "volume" )
		);

		if ( $chapters = $this->episode->get_chapters( 'json' ) )
			$init_options['chapters'] = json_decode( $chapters );

		$xml_string .= "\n"
		             . "\n<script>\n"
		             . "jQuery('#" . $this->get_html_id() . "').podlovewebplayer(" . json_encode( $init_options ) . ");"
		             . "\n</script>\n";

		return $xml_string;
	}

	private function get_webplayer_setting( $key, $subkey = false ) {

		$options = get_option( 'podlovewebplayer_options', array() );

		if ( isset( $options[ $key ] ) ) {
			if ( $subkey ) {
				if ( isset( $options[ $key ][ $subkey ] ) )
					return $options[ $key ][ $subkey ];
			} else {
				return $options[ $key ];
			}
		}

		return NULL;
	}

	private function get_html_id() {

		if ( ! $this->html_id ) {
			self::$index++;
			$this->html_id = 'podlovewebplayer_' . self::$index;
		}

		return $this->html_id;
	}

	private function get_media_tag() {
		return $this->is_video ? 'video' : 'audio';
	}

	private function get_files() {

		$files = $this->get_playable_video_files();

		if ( count( $files ) == 0 ) {
			$files = $this->get_playable_audio_files();
			$this->is_video = false;
		}

		return $files;
	}

	private function get_playable_video_files() {
		return $this->get_playable_files( $this->video_formats, 'video' );
	}

	private function get_playable_audio_files() {
		return $this->get_playable_files( $this->audio_formats, 'audio' );
	}

	/**
	 * Get playable files for player, based on episode and player assignments.
	 * 
	 * @param  array  $formats      array of formats like mp3, mp3, ogg, opus, webm
	 * @param  string $media_type   audio or video
	 * @return array of \Podlove\Model\MediaFile
	 */
	private function get_playable_files( $formats, $media_type ) {

		$playable_files = array();

		foreach ( $formats as $format ) {

			if ( ! isset( $this->player_format_assignments[ $media_type ][ $format ] ) )
				continue;

			$episode_asset = EpisodeAsset::find_by_id( $this->player_format_assignments[ $media_type ][ $format ] );
			if ( ! $episode_asset )
				continue;

			$media_file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->episode->id, $episode_asset->id );
			if ( $media_file )
				$playable_files[] = $media_file;
		}

		return $playable_files;
	}

	private function get_player_format_assignments( $value='' ) {
		return get_option( 'podlove_webplayer_formats' );
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
