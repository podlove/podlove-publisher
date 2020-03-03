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

	public static $template_tags = array(
		'is_archive',
		'is_post_type_archive',
		'is_attachment',
		'is_tax',
		'is_date',
		'is_day',
		'is_feed',
		'is_comment_feed',
		'is_front_page',
		'is_home',
		'is_month',
		'is_page',
		'is_paged',
		'is_preview',
		'is_search',
		'is_single',
		'is_singular',
		'is_time',
		'is_year',
		'is_404',
		'is_main_query'
	);

	/**
	 * Apply Twig to given template
	 * 
	 * @param  string $html File path or HTML string.
	 * @param  array  $vars optional variables for Twig context
	 * @return string       rendered template string
	 */
	public static function apply_to_html($html, $vars = array()) {

		$twig = self::getTwigEnv();

		$context = ['option' => $vars];

		// add podcast to global context
		$context = array_merge(
			$context, ['podcast' => new Podcast(Model\Podcast::get())]
		);

		// Apply filters to twig templates
		$context = apply_filters( 'podlove_templates_global_context', $context );

		// add podcast to global context if we are in an episode
		if ($episode = Model\Episode::find_one_by_property('post_id', get_the_ID())) {
			$context = array_merge($context, array('episode' => new Episode($episode)));
		}

		$result = null;

		if ($twig->getLoader()->exists($html)) {
			try {
				$result = $twig->render($html, $context);
			} catch (\Twig_Error $e) {
				$message  = $e->getRawMessage();
				$line     = $e->getTemplateLine();
				$template = $e->getTemplateFile();

				\Podlove\Log::get()->addError($message, [
					'type'     => 'twig',
					'line'     => $line,
					'template' => $template
				]);
			}
		}

		if ($result === null) {
			try {
				// simple Twig Env to render plain string
				$env = new \Twig_Environment(new \Twig_Loader_Array([]), ['autoescape' => false]);

				// no clue yet how this is possible but it happens
				if (method_exists($env, 'createTemplate')) {
					$template = $env->createTemplate($html);
					$result   = $template->render($context);
				} else {
					\Podlove\Log::get()->addError("Error when rendering Twig template from string. Missing Twig_Environment::createTemplate method.", [
						'type'     => 'twig',
						'template' => $html
					]);
				}

			} catch (Exception $e) {
				\Podlove\Log::get()->addError("Error when rendering Twig template from string: " . $e->getMessage(), [
					'type'     => 'twig',
					'template' => $html
				]);
			}
		}

		return $result;
	}

	private static function getTwigLoader()
	{
		// file loader for internal use
		$file_loader = new \Twig_Loader_Filesystem();
		$file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'templates']), 'core');

		// other modules can register their own template directories/namespaces
		$file_loader = apply_filters('podlove_twig_file_loader', $file_loader);

		// database loader for user templates
		$db_loader = new TwigLoaderPodloveDatabase;

		$loaders = array($file_loader, $db_loader);
		$loaders = apply_filters('podlove_twig_loaders', $loaders);

		return new \Twig_Loader_Chain($loaders);		
	}

	private static function getTwigEnv()
	{
		$twig = new \Twig_Environment(self::getTwigLoader(), ['autoescape' => false]);
		$twig->addExtension(new \Twig_Extensions_Extension_I18n());
		$twig->addExtension(new \Twig_Extensions_Extension_Date());

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

		// add functions
		foreach (self::$template_tags as $tag) {
			$func = new \Twig_SimpleFunction($tag, function() use ($tag) { return $tag(); });
			$twig->addFunction($func);
		}

		$func = new \Twig_SimpleFunction('get_the_post_thumbnail_url', function ($post = null, $size = 'post-thumbnail') {return get_the_post_thumbnail_url($post, $size);});
		$twig->addFunction($func);

		// shortcode_exists
		$func = new \Twig_SimpleFunction('shortcode_exists', function($shortcode) { return \shortcode_exists($shortcode); });
		$twig->addFunction($func);

		return $twig;		
	}
}
