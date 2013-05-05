<?php 
namespace Podlove\Chapters\Printer;

class PSC implements Printer {

	public function do_print( \Podlove\Chapters\Chapters $chapters ) {
		$xml = new \SimpleXMLElement( '<psc:chapters version="1.2" xmlns:psc="http://podlove.org/simple-chapters" />' );
		$xml = array_reduce( $chapters->toArray(), function ( $xml, $chapter ) {
			$child = $xml->addChild( 'psc:chapter' );
			$child->addAttribute( 'start', $chapter->get_time() );
			$child->addAttribute( 'title', $chapter->get_title() );

			if ( $chapter->get_link() )
				$child->addAttribute( 'href', $chapter->get_link() );

			if ( $chapter->get_image() )
				$child->addAttribute( 'image', $chapter->get_image() );

			return $xml;
		}, $xml );

		$xml_string = $xml->asXML();
		$xml_string = $this->format_xml( $xml_string );
		$xml_string = $this->remove_xml_header( $xml_string );

		return $xml_string;
	}

	private function format_xml( $xml ) {

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML( $xml );

		return $dom->saveXML();
	}

	public function remove_xml_header( $xml ) {
		return trim( str_replace( '<?xml version="1.0"?>', '', $xml ) );
	}
}