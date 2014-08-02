<?php
namespace Podlove\Template;
use \Podlove\Model;

/**
 * Apply Twig functionality and podcast/episode accessors to strings/templates
 *
 * Example:
 * 	add_filter('some_filter_for_a_string', array('\Podlove\Template\TwigFilter', 'apply_to_html'));
 *
 * @param string $html HTML string
 * @param array  $vars optional map of template variables
 */
class TwigFilter {

	/**
	 * Apply Twig to given template
	 * 
	 * @param  string $html File path or HTML string.
	 * @param  array  $vars optional variables for Twig context
	 * @return string       rendered template string
	 */
	public static function apply_to_html($html, $vars = array()) {

		// file loader for internal use
		$file_loader = new \Twig_Loader_Filesystem();
		$file_loader->addPath(implode(DIRECTORY_SEPARATOR, array(\Podlove\PLUGIN_DIR, 'templates')), 'core');

		// other modules can register their own template directories/namespaces
		$file_loader = apply_filters('podlove_twig_file_loader', $file_loader);

		// database loader for user templates
		$db_loader = new TwigLoaderPodloveDatabase;

		$loaders = array($file_loader, $db_loader);
		$loaders = apply_filters('podlove_twig_loaders', $loaders);

		$loader = new \Twig_Loader_Chain($loaders);

		$twig = new \Twig_Environment($loader, array('autoescape' => false));
		$twig->addExtension(new \Twig_Extensions_Extension_I18n());

		$formatBytesFilter = new \Twig_SimpleFilter('formatBytes', function ($string) {
		    return \Podlove\format_bytes($string, 0);
		});

		$padLeftFilter = new \Twig_SimpleFilter('padLeft', function ($string, $padChar, $length) {
		    while ( strlen($string) < $length ) {
		    	$string = $padChar . $string;
		    }
		    return $string;
		});

		$twig->addFilter($formatBytesFilter);
		$twig->addFilter($padLeftFilter);

		$context = $vars;

		// add podcast to global context
		$context = array_merge($context, array('podcast' => new Podcast(Model\Podcast::get_instance())));

		// Apply filters to twig templates
		$context = apply_filters( 'podlove_templates_global_context', $context );

		// add podcast to global context if we are in an episode
		if ($episode = Model\Episode::find_one_by_property('post_id', get_the_ID())) {
			$context = array_merge($context, array('episode' => new Episode($episode)));
		}

		return $twig->render($html, $context);
	}
}