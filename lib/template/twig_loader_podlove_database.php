<?php
namespace Podlove\Template;

use Podlove\Model\Template;

class TwigLoaderPodloveDatabase implements \Twig_LoaderInterface {

	/**
	 * Gets the source code of a template, given its name.
	 *
	 * @param  string $name string The name of the template to load
	 *
	 * @return string The template source code
	 */
	function getSource($name) {
		if ($template = Template::find_one_by_title($name)) {
			return $template->content;
		} else {
			return false;
		}
	}

	/**
	 * Gets the cache key to use for the cache for a given template name.
	 *
	 * @param  string $name string The name of the template to load
	 *
	 * @return string The cache key
	 */
	function getCacheKey($name) {
		return $name;
	}

	/**
	 * Returns true if the template is still fresh.
	 *
	 * @param string    $name The template name
	 * @param timestamp $time The last modification time of the cached template
	 */
	function isFresh($name, $time) {
		return false;
	}
}