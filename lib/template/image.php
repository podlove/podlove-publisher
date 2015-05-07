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
	 * - size: Image dimensions. Either in the form of "10x20" or just "35" for square dimensions.
	 * 
	 * **Examples**
	 * 
	 * ```jinja
	 * {{ image.url }}                  {# returns the unresized image URL #}
	 * {{ image.url({size: "10x20"}) }} {# returns resized / cropped image URL #}
	 * {{ image.url({size: 10}) }}      {# returns image URL resized to 10x10 #}
	 * ```
	 * 
	 * Note: It is not _guaranteed_ to get back the resized image. If it is 
	 * not ready yet, the source URL will be returned.
	 * 
	 * @accessor
	 */
	public function url($args = []) {
		$defaults = ['size' => NULL];
		$args = wp_parse_args($args, $defaults);

		$size = self::parse_size($args['size']);

		return $this->image->url($size['width'], $size['height']);
	}

	/**
	 * Get HTML image tag for resized image.
	 * 
	 * **Parameters**
	 * 
	 * - size: Image dimensions. Either in the form of "10x20" or just "35" for square dimensions.
	 * - alt: Set image tag "alt" attribute.
	 * - title: Set image tag "title" attribute.
	 * 
	 * **Examples**
	 * 
	 * ```jinja
	 * {{ image.image }}                       {# returns the unresized image tag #}
	 * {{ image.image({size: "10x20"}) }}      {# returns resized / cropped image tag #}
	 * {{ image.image({size: 10}) }}           {# returns image tag resized to 10x10 #}
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
			'size'  => NULL,
			'alt'   => NULL,
			'title' => NULL
		];

		$args = wp_parse_args($args, $defaults);

		$size = self::parse_size($args['size']);

		return $this->image->image($size['width'], $size['height'], $args['alt'], $args['title']);
	}

	/**
	 * Parse size string.
	 * 
	 * Allowed size formats:
	 * 
	 * - "<width>x<height>", for example "30x50"
	 * - single integer, for example "10" is equivalent to "10x10"
	 * - NULL, which means no resizing
	 * 
	 * @param  mixed $size
	 * @return array ['width' => $width, 'height' => $height]
	 */
	private static function parse_size($size) {
		
		$null = ['width' => NULL, 'height' => NULL];

		if (is_null($size))
			return $null;

		// parse format "<width>x<height>", for example "30x50"
		if (preg_match('/^(\d+)x(\d+)$/', $size, $matches) === 1) {
			return [
				'width'  => (int) $matches[1], 
				'height' => (int) $matches[2]
			];
		}

		// parse singe value "<size>", interpret as square
		$size = (int) $size;

		if ($size > 0) {
			return [
				'width'  => $size, 
				'height' => $size
			];
		}

		return $null;
	}
}