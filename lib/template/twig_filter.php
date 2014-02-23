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

	public static function apply_to_html($html, $vars = array()) {
		$loader = new \Twig_Loader_String();

		$twig   = new \Twig_Environment($loader, array('autoescape' => false));
		$twig->addFilter(self::subtemplating_filter($twig));
		$twig->addExtension(new \Twig_Extensions_Extension_I18n());

		$context = $vars;

		// add podcast to global context
		$context = array_merge($context, array('podcast' => new Podcast(Model\Podcast::get_instance())));

		// add podcast to global context if we are in an episode
		if ($episode = Model\Episode::find_one_by_property('post_id', get_the_ID())) {
			$context = array_merge($context, array('episode' => new Episode($episode)));
		}

		return $twig->render($html, $context);
	}

	/**
	 * Twig "template" Filter
	 *
	 * Enable subtemplating for all wrapper classes.
	 *
	 * Example:
	 *
	 * {{ episode|template("sub template id") }}
	 */
	protected static function subtemplating_filter($twig) {
		return new \Twig_SimpleFilter('template', function ($context, $wrapperClass, $template_id) use ($twig) {

			$reflectionClass = new \ReflectionClass($wrapperClass);
			// todo use same comment class as in bin/documentation.php
			preg_match('/@templatetag\s+(\w+)/', $reflectionClass->getDocComment(), $matches);

			$templatetag = null;
			if (isset($matches[1]))
				$templatetag = trim($matches[1]);

			if (!$templatetag)
				return sprintf( __( 'Podlove Error: No subtemplating possible for wrapper class "%s"', 'podlove' ), $reflectionClass->name );

			$context[$templatetag] = $wrapperClass;

			if (!$template = Model\Template::find_one_by_title($template_id))
				return sprintf( __( 'Podlove Error: Whoops, there is no template with id "%s"', 'podlove' ), $template_id );

		    return $twig->render($template->content, $context);
		}, array('needs_context' => true));
	}
}