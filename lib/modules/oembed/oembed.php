<?php 
namespace Podlove\Modules\oembed;

use \Podlove\Modules\Base;

class oembed extends \Podlove\Modules\Base {

	protected $module_name = '<a href="http://oembed.com/">oEmbed</a> Support';
	protected $module_description = 'Allows an embedded representation of a URL on third party sites.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'wp', array( $this, 'load_oembed' ) );
		add_action( 'wp_head', array( $this, 'register_oembed_discovery' ) );
	}

	public function load_oembed() {
		if( isset( $_GET['service'] ) && strtoupper( $_GET['service'] ) == "PODLOVE-OEMBED" &&
			isset( $_GET['format'] ) ) {
			if( is_single() ) {
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
		}
	}

	public function get_current_episode( $post_id ) {
		if( get_post_status( $post_id ) !== 'publish' || get_post_type( $post_id ) !== 'podcast' ) { // If post is not public, 404 will be replied
			status_header( 404 );
			return;
		}

		$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
		$podcast = \Podlove\Model\Podcast::get_instance();
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
		$post_id = get_the_ID();
		$permalink = get_permalink( $post_id );
		$title =  get_the_title( $post_id );

		if( is_single() && get_post_type( $post_id ) == 'podcast' ) {
			echo "	<link rel='alternate' type='application/json+oembed'
 						href='" . $permalink . ( strpos( $permalink, '?' ) === FALSE ? "?" : "&amp;" ) . "service=podlove-oembed&amp;format=json'
 						title='" . $title . " oEmbed Profile' />\n
					<link rel='alternate' type='text/xml+oembed'
  						href='" . $permalink . ( strpos( $permalink, '?' ) === FALSE ? "?" : "&amp;" ) . "service=podlove-oembed&amp;format=xml'
  						title='" . $title . " oEmbed Profile' />\n";
  		}
	}

}