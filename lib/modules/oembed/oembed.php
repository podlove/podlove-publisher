<?php 
namespace Podlove\Modules\oembed;

use \Podlove\Modules\Base;
use Podlove\DomDocumentFragment;

class oembed extends \Podlove\Modules\Base {

	protected $module_name = 'oEmbed Support';
	protected $module_description = 'Allows an embedded representation of a URL on third party sites.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'wp', array( $this, 'load_oembed' ) );
		add_action( 'wp_head', array( $this, 'register_oembed_discovery' ) );
	}

	public function load_oembed() {

		if (!is_single())
			return;

		if (!isset($_GET['service']) || strtoupper($_GET['service']) != "PODLOVE-OEMBED" || !isset($_GET['format']))
			return;

		switch ( strtoupper( $_GET['format'] ) ) {
			case 'JSON' :
				header('Content-type: application/json; charset=utf-8');
				print_r( json_encode( $this->get_current_episode( get_the_ID() ) ) );
			break;
			case 'XML' :
				header('Content-Type: application/xml; charset=utf-8');
				$xml_source = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ' . 'standalone="yes"?><oembed/>');
				$episode = array_flip( $this->get_current_episode( get_the_ID() ) );
				array_walk_recursive( 
								$episode,
								array( $xml_source, 'addChild' ) );
				print $xml_source->asXML();
			break;
			default :
				status_header( 404 );
			break;
		}
		
		exit();
	}

	public function get_current_episode( $post_id ) {
		if( get_post_status( $post_id ) !== 'publish' || get_post_type( $post_id ) !== 'podcast' ) { // If post is not public, 404 will be replied
			status_header( 404 );
			return;
		}

		$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
		$podcast = \Podlove\Model\Podcast::get();
		$permalink = get_permalink( $post_id );

		$player_width = "560px";
		$player_height =  "140px";

		return array(		'version'		=> '1.0',
							'type'			=> 'rich',
							'width'			=> $player_width,
							'height'		=> $player_height,
							'title'			=> $episode->full_title(),
							'url'			=> get_permalink( $post_id ),
							'author_name'	=> $podcast->full_title(),
							'author_url'	=> site_url(),
							'thumbnail_url'	=> $episode->get_cover_art_with_fallback(),
							'html'			=> '<iframe width="' . $player_width .'" height="' . $player_height . '" src="' . $permalink . ( strpos( $permalink, '?' ) === FALSE ? "?" : "&amp;" ) .'standalonePlayer"></iframe>');
	}

	public function register_oembed_discovery() { // WordPress does not allow registering custom <link> elements.

		if (!is_single())
			return;

		$post_id = get_the_ID();

		if (get_post_type( $post_id ) !== 'podcast')
			return;

		$permalink = get_permalink( $post_id );
		$permalink_template = $permalink . ( strpos( $permalink, '?' ) === FALSE ? "?" : "&" );
		$title =  get_the_title( $post_id );

		$embed_elements = array(
			array(
					'rel'	=> 'alternate',
					'type'	=> 'application/json+oembed',
					'href'	=> $permalink_template . "service=podlove-oembed&format=json",
					'title'	=> $title . " oEmbed Profile"
			),
			array(
					'rel'	=> 'alternate',
					'type'	=> 'application/xml+oembed',
					'href'	=> $permalink_template . "service=podlove-oembed&format=xml",
					'title'	=> $title . " oEmbed Profile"
			),
		);

		$dom = new DomDocumentFragment;

		foreach ($embed_elements as $link_element) {
			$element = $dom->createElement('link');
			foreach ($link_element as $attribute => $value) {
				$element->setAttribute($attribute,$value);
			}
			$dom->appendChild($element);
		}

		echo $dom;
	}

}