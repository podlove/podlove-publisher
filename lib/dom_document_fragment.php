<?php
namespace Podlove;

/**
 * Simple DOMDocument wrapper for fragments without <?xml ?> head.
 *
 * Example usage:
 * 
 * 	$dom = new DomDocumentFragment;
 * 	$element = $dom->createElement('meta');
 * 	$dom->appendChild($element);
 * 	echo $dom;
 */
class DomDocumentFragment extends \DOMDocument {

	public function __construct($version = '1.0', $encoding = null) {
		return parent::__construct($version, $encoding);
	}

	public function __toString() {
		return str_replace( '<?xml version="1.0"?>', '', $this->saveXML() );
	}

}