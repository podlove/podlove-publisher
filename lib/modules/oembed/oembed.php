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
		if ( isset( $_GET['service'] ) && strtoupper( $_GET['service'] ) == "PODLOVE-WEB-PLAYER" ) {
			if( is_single() ) : ?>
				<html>
				<head>
				<?php
				print_r(  wp_head() );
				?>
				</head>
				<body>
				<?php
					print_r( $this->get_webplayer( get_the_ID() ) );
				?>
				</body>
				</html>
			<?php endif;
			exit();
		}

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
						array_walk_recursive( 
										array_flip( $this->get_current_episode( get_the_ID() ) ),
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

	public function get_webplayer( $post_id ) {
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( get_the_ID() );
		$printer = new \Podlove\Modules\PodloveWebPlayer\Printer( $episode );
		return $printer->render();
	}

	public function get_current_episode( $post_id ) {
		if( get_post_status( $post_id ) !== 'publish' || get_post_type( $post_id ) !== 'podcast' ) { // If post is not public, 404 will be replied
			status_header( 404 );
			return;
		}

		$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
		$podcast = \Podlove\Model\Podcast::get_instance();

		return array(		'version'		=> '1.0',
							'type'			=> 'rich',
							'width'			=> '600px',
							'height'		=> '200px',
							'title'			=> $episode->full_title(),
							'url'			=> get_permalink( $post_id ),
							'author_name'	=> $podcast->full_title(),
							'author_url'	=> site_url(),
							'thumbnail_url'	=> $episode->get_cover_art_with_fallback(),
							'html'			=> '<iframe src="' . get_permalink( $post_id ) .'?service=podlove-web-player"></iframe>');
	}

	public function register_oembed_discovery() { // WordPress does not allow registering custom <link> elements.
		$post_id = get_the_ID();
		if( is_single() && get_post_type( $post_id ) == 'podcast' ) {
			echo "	<link rel='alternate' type='application/json+oembed'
 						href='" . get_permalink( $post_id ) . "?service=podlove-oembed&format=json'
 						title='" . get_the_title( $post_id ) . " oEmbed Profile' />\n
					<link rel='alternate' type='text/xml+oembed'
  						href='" . get_permalink( $post_id ) . "?service=podlove-oembed&format=xml'
  						title='" . get_the_title( $post_id ) . " oEmbed Profile' />\n";
  		}
	}

}