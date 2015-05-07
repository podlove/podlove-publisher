<?php
namespace Podlove\Template;

/**
 * Episode Template Wrapper
 *
 * @templatetag image
 */
class Image extends Wrapper {

	/**
	 * @var Podlove\Model\Image
	 */
	private $image;

	public function __construct(\Podlove\Model\Image $image) {
		$this->image = $image;
	}

	protected function getExtraFilterArgs() {
		return [$this->image];
	}

	// /////////
	// Accessors
	// /////////

	public function __toString() {
		return $this->image->url();
	}

	/**
	 * Get URL for resized image.
	 * 
	 * **Parameters**
	 * 
	 * - width: Image width. Set width and leave height blank to keep the orinal aspect ratio.
	 * - height: Image height. Set height and leave width blank to keep the orinal aspect ratio.
	 * 
	 * **Examples**
	 * 
	 * ```jinja
	 * {{ image.url }}               {# returns the unresized image URL #}
	 * {{ image.url({width: 100}) }} {# returns resized image URL #}
	 * ```
	 * 
	 * Note: It is not _guaranteed_ to get back the resized image. If it is 
	 * not ready yet, the source URL will be returned.
	 * 
	 * @accessor
	 */
	public function url($args = []) {

		$defaults = [
			'width'  => NULL,
			'height' => NULL
		];
		$args = wp_parse_args($args, $defaults);

		return $this->image->url($args['width'], $args['height']);
	}

	/**
	 * Get HTML image tag for resized image.
	 * 
	 * **Parameters**
	 * 
	 * - width: Image width. Set width and leave height blank to keep the orinal aspect ratio.
	 * - height: Image height. Set height and leave width blank to keep the orinal aspect ratio.
	 * - alt: Set image tag "alt" attribute.
	 * - title: Set image tag "title" attribute.
	 * 
	 * **Examples**
	 * 
	 * ```jinja
	 * {{ image.image }}                       {# returns the unresized image tag #}
	 * {{ image.image({width: 100}) }}         {# returns resized image tag #}
	 * {{ image.image({title: "The Spark"}) }} {# returns image tag with custom title #}
	 * ```
	 * 
	 * Note: It is not _guaranteed_ to get back the resized image. If it is 
	 * not ready yet, the source URL will be returned.
	 * 
	 * @accessor
	 */
	public function image($args = []) {
		
		$defaults = [
			'width'  => NULL,
			'height' => NULL,
			'alt'    => NULL,
			'title'  => NULL
		];
		$args = wp_parse_args($args, $defaults);

		return $this->image->image($args['width'], $args['height'], $args['alt'], $args['title']);
	}
}