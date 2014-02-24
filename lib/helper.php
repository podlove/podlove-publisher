<?php
namespace Podlove;

/**
 * strpos wrapper that prefers mb_strpos but falls back to strpos.
 */
function strpos($haystack, $needle, $offset = 0, $encoding = 'UTF-8') {
  if (function_exists('mb_strpos'))
    return mb_strpos($haystack, $needle, $offset, $encoding);
  else
    return strpos($haystack, $needle, $offset);
}

/**
 * strlen wrapper that prefers mb_strlen but falls back to strlen.
 */
function strlen($str, $encoding = 'UTF-8') {
  if (function_exists('mb_strlen'))
    return mb_strlen($str, $encoding);
  else
    return strlen($str);
}

/**
 * substr wrapper that prefers mb_substr but falls back to substr.
 */
function substr($str, $start, $length = NULL, $encoding = 'UTF-8') {
  if (function_exists('mb_substr'))
    return mb_substr($str, $start, $length, $encoding);
  else
    return substr($str, $start, $length);
}

function format_bytes( $size, $decimals = 2 ) {
    $units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
    for ( $i = 0; $size >= 1024 && $i < 4; $i++ ) $size /= 1024;
    return round( $size, $decimals ) . $units[$i];
}

function get_blog_prefix() {
	$blog_prefix = '';

	if ( is_multisite() && ! is_subdomain_install() && is_main_site() )
		$blog_prefix = '/blog';

	return $blog_prefix;
}

function get_setting( $namespace, $name ) {
	
	$defaults = array(
		'website' => array(
			'merge_episodes'         => 'on',
			'hide_wp_feed_discovery' => 'off',
			'use_post_permastruct' => 'on',
			'custom_episode_slug'    => '/podcast/%podcast%/',
			'episode_archive' => 'on',
			'episode_archive_slug' => '/podcast/',
			'url_template' => '%media_file_base_url%%episode_slug%%suffix%.%format_extension%',
			'force_download' => 'on'
		),
		'metadata' => array(
			'enable_episode_record_date'      => 0,
			'enable_episode_publication_date' => 0,
			'enable_episode_explicit' => 0,
			'enable_episode_license' => 0
		),
		'redirects' => array(
			'podlove_setting_redirect' => array(),
		)
	);

	$options = get_option( 'podlove_' . $namespace );
	$options = wp_parse_args( $options, $defaults[ $namespace ] );

	return $options[ $name ];
}

function get_webplayer_setting( $name ) {

	$defaults = array(
		'chaptersVisible' => 'false',
		'inject'          => 'manually'
	);
	
	$settings = get_option( 'podlove_webplayer_settings', array() );
	$settings = wp_parse_args( $settings, $defaults );

	return $settings[ $name ];
}

function slugify( $text ) {

	// replace everything but unreserved characters (RFC 3986 section 2.3) by a hyphen
	$text = preg_replace( '~[^\\pL\d_\.\~]~u', '-', $text );

	// transliterate
	$text = iconv( 'utf-8', 'us-ascii//TRANSLIT', $text );

	return empty( $text ) ? 'n-a' : $text;
}

function require_code_mirror() {
	$codemirror_path = \Podlove\PLUGIN_URL . '/js/admin/codemirror/';

	wp_register_script( 'podlove-codemirror-mode-css-js', $codemirror_path . 'modes/css/css.js', array( 'podlove-codemirror-js' ) );
	wp_register_script( 'podlove-codemirror-mode-javascript-js', $codemirror_path . 'modes/javascript/javascript.js', array( 'podlove-codemirror-js' ) );
	wp_register_script( 'podlove-codemirror-mode-xml-js', $codemirror_path . 'modes/xml/xml.js', array( 'podlove-codemirror-js' ) );
	wp_register_script( 'podlove-codemirror-mode-yaml-js', $codemirror_path . 'modes/yaml/yaml.js', array( 'podlove-codemirror-js' ) );
	wp_register_script( 'podlove-codemirror-mode-twig-js', $codemirror_path . 'modes/twig/twig.js', array( 'podlove-codemirror-js' ) );
	wp_register_script( 'podlove-codemirror-mode-htmlmixed-js', $codemirror_path . 'modes/htmlmixed/htmlmixed.js', array(
		'podlove-codemirror-mode-css-js',
		'podlove-codemirror-mode-javascript-js',
		'podlove-codemirror-mode-yaml-js',
		'podlove-codemirror-mode-xml-js'
	) );
	wp_register_script( 'podlove-codemirror-mode-twigmixed-js', $codemirror_path . 'modes/twigmixed/twigmixed.js', array(
		'podlove-codemirror-mode-htmlmixed-js',
		'podlove-codemirror-mode-twig-js'
	) );

	wp_register_script(
		'podlove-codemirror-util-hint-js',
		$codemirror_path . 'util/simple-hint.js'
	);

	wp_register_script(
		'podlove-codemirror-util-cursor-js',
		$codemirror_path . 'util/searchcursor.js'
	);

	wp_register_script(
		'podlove-codemirror-util-match-js',
		$codemirror_path . 'util/match-highlighter.js',
		array( 'podlove-codemirror-util-cursor-js' )
	);

	wp_register_script(
		'podlove-codemirror-util-close-js',
		$codemirror_path . 'util/closetag.js'
	);

	wp_register_script(
		'podlove-codemirror-js',
		$codemirror_path . 'codemirror.js'
	);

	wp_enqueue_script( 'podlove-codemirror-js' );
	wp_enqueue_script( 'podlove-codemirror-mode-htmlmixed-js' );
	wp_enqueue_script( 'podlove-codemirror-mode-twigmixed-js' );
	wp_enqueue_script( 'podlove-codemirror-util-close-js' );
	wp_enqueue_script( 'podlove-codemirror-util-match-js' );
	wp_enqueue_script( 'podlove-codemirror-util-hint-js' );

    wp_register_style(
    	'podlove-codemirror-css',
		\Podlove\PLUGIN_URL . '/css/codemirror.css'
    );

    wp_register_style(
    	'podlove-codemirror-hint-css',
		$codemirror_path . 'util/simple-hint.css'
    );

    wp_enqueue_style( 'podlove-codemirror-css' );
    wp_enqueue_style( 'podlove-codemirror-hint-css' );
}

/**
 * Load template file.
 * 
 * @param  string $path absolute or path relative to /templates
 * @return string       file contents
 */
function load_template($path) {
	if (!file_exists($path))
		$path = trailingslashit(\Podlove\PLUGIN_DIR) . 'templates/' . $path;

	if (file_exists($path)) {
		return file_get_contents($path);
	} else {
		return false;
	}
}

namespace Podlove\Form;

/**
 * Build whole form
 * @param  object   $object   object that shall be modified via the form
 * @param  array    $args     list of options, all optional
 * 		- action        form action url
 * 		- method        get, post
 * 		- hidden        dictionary with hidden values
 * 		- submit_button set to false to hide the submit button
 * 		- form          set to false to skip <form> wrapper
 * 		- attributes    optional html attributes for form tag
 * 		- is_table      is it a table form? defaults to true
 * @param  function $callback inner form
 * @return void
 * 
 * @todo  refactor into a wrapper so the <table> is optional
 * @todo  hidden fields should be added via input builders
 */
function build_for( $object, $args, $callback ) {

	// determine form action url
	if ( isset( $args['action'] ) ) {
		$url = $args['action'];
	} else {
		$url = is_admin() ? 'admin.php' : '';
		if ( isset( $_REQUEST['page'] ) ) {
			$url .= '?page=' . $_REQUEST['page'];
		}
	}

	// determine form html attributes
	$attributes_html = '';
	if ( isset( $args['attributes'] ) ) {
		$attributes = array();
		foreach ( $args['attributes'] as $attr_key => $attr_value ) {
			$attributes[] = sprintf( '%s = "%s"', $attr_key, $attr_value );
		}
		$attributes_html = implode( ' ', $attributes );
	}

	// determine method
	$method = isset( $args['method'] ) ? $args['method'] : 'post';

	// determine context
	$context = isset( $args['context'] ) ? $args['context'] : '';

	// check if <form> should be printed
	$print_form = ! isset( $args['form'] ) || $args['form'] === true;

	?>
	<?php if ( $print_form ): ?>
		<form action="<?php echo $url; ?>" method="<?php echo $method; ?>" <?php echo $attributes_html ?>>
	<?php endif ?>

	<?php if ( isset( $args['hidden'] ) && $args['hidden'] ): ?>
		<?php foreach ( $args['hidden'] as $name => $value ): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />		
		<?php endforeach ?>
	<?php endif ?>

	<?php if ( !isset($args['is_table']) || $args['is_table'] !== false ): ?>
		<table class="form-table">
	<?php endif; ?>
	<?php call_user_func( $callback, new \Podlove\Form\Input\Builder( $object, $context ) ); ?>
	<?php if ( !isset($args['is_table']) || $args['is_table'] !== false ): ?>
		</table>
	<?php endif; ?>

	<?php if ( ! isset( $args['submit_button'] ) || $args['submit_button'] === true ): ?>
		<?php submit_button(); ?>
	<?php endif ?>

	<?php if ( $print_form ): ?>
		</form>
	<?php endif ?>
	
	<?php
}

namespace Podlove\License;

function version_per_country_cc() {
	$version_per_country_cc = array(
		'international' => array("version" => "3.0", "name" => "Unported"),
		'ar' => array("version" => "2.5"),
		'au' => array("version" => "3.0"),
		'at' => array("version" => "3.0"),
		'be' => array("version" => "2.0"),
		'br' => array("version" => "3.0"),
		'bg' => array("version" => "2.5"),
		'ca' => array("version" => "2.5"),
		'cl' => array("version" => "3.0"),
		'cn' => array("version" => "3.0"),
		'co' => array("version" => "2.5"),
		'cr' => array("version" => "3.0"),
		'hr' => array("version" => "3.0"),
		'cz' => array("version" => "3.0"),
		'dk' => array("version" => "2.5"),
		'ec' => array("version" => "3.0"),
		'eg' => array("version" => "3.0"),
		'ee' => array("version" => "3.0"),
		'fi' => array("version" => "1.0"),
		'fr' => array("version" => "3.0"),
		'de' => array("version" => "3.0"),
		'gr' => array("version" => "3.0"),
		'gt' => array("version" => "3.0"),
		'hk' => array("version" => "3.0"),
		'hu' => array("version" => "2.5"),
		'igo' => array("version" => "3.0"),
		'in' => array("version" => "2.5"),
		'ie' => array("version" => "3.0"),
		'il' => array("version" => "2.5"),
		'it' => array("version" => "3.0"),
		'jp' => array("version" => "2.1"),
		'lu' => array("version" => "3.0"),
		'mk' => array("version" => "2.5"),
		'my' => array("version" => "2.5"),
		'mt' => array("version" => "2.5"),
		'mx' => array("version" => "2.5"),
		'nl' => array("version" => "3.0"),
		'nz' => array("version" => "3.0"),
		'no' => array("version" => "3.0"),
		'pe' => array("version" => "2.5"),
		'ph' => array("version" => "3.0"),
		'pl' => array("version" => "3.0"),
		'pt' => array("version" => "3.0"),
		'pr' => array("version" => "3.0"),
		'ro' => array("version" => "3.0"),
		'rs' => array("version" => "3.0"),
		'sg' => array("version" => "3.0"),
		'si' => array("version" => "2.5"),
		'za' => array("version" => "2.5"),
		'kp' => array("version" => "2.0"),
		'es' => array("version" => "3.0"),
		'se' => array("version" => "2.5"),
		'ch' => array("version" => "3.0"),
		'tw' => array("version" => "3.0"),
		'th' => array("version" => "3.0"),
		'gb' => array("version" => "2.0"),
		'gb_sc' => array("version" => "2.5"),
		'ug' => array("version" => "3.0"),
		'us' => array("version" => "3.0"),
		'vn' => array("version" => "3.0")		
	);
	asort( $version_per_country_cc );
	return $version_per_country_cc;
}

function locales_cc() {
	$locales = array(
		'international' => "International",
		'ar' => "Argentina",
		'au' => "Australia",
		'at' => "Austria",
		'be' => "Belgium",
		'br' => "Brazil",
		'bg' => "Bulgaria",
		'ca' => "Canada",
		'cl' => "Chile",
		'cn' => "China Mainland",
		'co' => "Colombia",
		'cr' => "Costa Rica",
		'hr' => "Croatia",
		'cz' => "Czech Republic",
		'dk' => "Denmark",
		'ec' => "Ecuador",
		'eg' => "Egypt",
		'ee' => "Estonia",
		'fi' => "Finland",
		'fr' => "France",
		'de' => "Germany",
		'gr' => "Greece",
		'gt' => "Guatemala",
		'hk' => "Hong Kong",
		'hu' => "Hungary",
		'igo' => "IGO",
		'in' => "India",
		'ie' => "Ireland",
		'il' => "Israel",
		'it' => "Italy",
		'jp' => "Japan",
		'lu' => "Luxembourg",
		'mk' => "Macedonia",
		'my' => "Malaysia",
		'mt' => "Malta",
		'mx' => "Mexico",
		'nl' => "Netherlands",
		'nz' => "New Zealand",
		'no' => "Norway",
		'pe' => "Peru",
		'ph' => "Philippines",
		'pl' => "Poland",
		'pt' => "Portugal",
		'pr' => "Puerto Rico",
		'ro' => "Romania",
		'rs' => "Serbia",
		'sg' => "Singapore",
		'si' => "Slovenia",
		'za' => "South Africa",
		'kp' => "South Korea",
		'es' => "Spain",
		'se' => "Sweden",
		'ch' => "Switzerland",
		'tw' => "Taiwan",
		'th' => "Thailand",
		'gb' => "UK: England & Wales",
		'gb_sc' => "UK: Scotland",
		'ug' => "Uganda",
		'us' => "United States",
		'vn' => "Vietnam"
	);
	asort( $locales );
	return $locales;
}

namespace Podlove\Locale;

function locales() {
	$locales = array(
		'af' => "Afrikaans",
		'hu-HU' => "Hungarian - Hungary",
		'af-ZA' => "Afrikaans - South Africa",
		'is' => "Icelandic",
		'sq' => "Albanian",
		'is-IS' => "Icelandic - Iceland",
		'sq-AL' => "Albanian - Albania",
		'id' => "Indonesian",
		'ar' => "Arabic",
		'id-ID' => "Indonesian - Indonesia",
		'ar-DZ' => "Arabic - Algeria",
		'it' => "Italian",
		'ar-BH' => "Arabic - Bahrain",
		'it-IT' => "Italian - Italy",
		'ar-EG' => "Arabic - Egypt",
		'it-CH' => "Italian - Switzerland",
		'ar-IQ' => "Arabic - Iraq",
		'ja' => "Japanese",
		'ar-JO' => "Arabic - Jordan",
		'ja-JP' => "Japanese - Japan",
		'ar-KW' => "Arabic - Kuwait",
		'kn' => "Kannada",
		'ar-LB' => "Arabic - Lebanon",
		'kn-IN' => "Kannada - India",
		'ar-LY' => "Arabic - Libya",
		'kk' => "Kazakh",
		'ar-MA' => "Arabic - Morocco",
		'kk-KZ' => "Kazakh - Kazakhstan",
		'ar-OM' => "Arabic - Oman",
		'kok' => "Konkani",
		'ar-QA' => "Arabic - Qatar",
		'kok-IN' => "Konkani - India",
		'ar-SA' => "Arabic - Saudi Arabia",
		'ko' => "Korean",
		'ar-SY' => "Arabic - Syria",
		'ko-KR' => "Korean - Korea",
		'ar-TN' => "Arabic - Tunisia",
		'ky' => "Kyrgyz",
		'ar-AE' => "Arabic - United Arab Emirates",
		'ky-KG' => "Kyrgyz - Kyrgyzstan",
		'ar-YE' => "Arabic - Yemen",
		'lv' => "Latvian",
		'hy' => "Armenian",
		'lv-LV' => "Latvian - Latvia",
		'hy-AM' => "Armenian - Armenia",
		'lt' => "Lithuanian",
		'az' => "Azeri",
		'lt-LT' => "Lithuanian - Lithuania",
		'az-AZ-Cyrl' => "Azeri (Cyrillic) - Azerbaijan",
		'mk' => "Macedonian",
		'az-AZ-Latn' => "Azeri (Latin) - Azerbaijan",
		'mk-MK' => "Macedonian - Former Yugoslav Republic of Macedonia",
		'eu' => "Basque",
		'ms' => "Malay",
		'eu-ES' => "Basque - Basque",
		'ms-BN' => "Malay - Brunei",
		'be' => "Belarusian",
		'ms-MY' => "Malay - Malaysia",
		'be-BY' => "Belarusian - Belarus",
		'mr' => "Marathi",
		'bg' => "Bulgarian",
		'mr-IN' => "Marathi - India",
		'bg-BG' => "Bulgarian - Bulgaria",
		'mn' => "Mongolian",
		'ca' => "Catalan",
		'mn-MN' => "Mongolian - Mongolia",
		'ca-ES' => "Catalan - Catalan",
		'no' => "Norwegian",
		'zh-HK' => "Chinese - Hong Kong SAR",
		'nb-NO' => "Norwegian (Bokm�l) - Norway",
		'zh-MO' => "Chinese - Macao SAR",
		'nn-NO' => "Norwegian (Nynorsk) - Norway",
		'zh-CN' => "Chinese - China",
		'pl' => "Polish",
		'zh-CHS' => "Chinese (Simplified)",
		'pl-PL' => "Polish - Poland",
		'zh-SG' => "Chinese - Singapore",
		'pt' => "Portuguese",
		'zh-TW' => "Chinese - Taiwan",
		'pt-BR' => "Portuguese - Brazil",
		'zh-CHT' => "Chinese (Traditional)",
		'pt-PT' => "Portuguese - Portugal",
		'hr' => "Croatian",
		'pa' => "Punjabi",
		'hr-HR' => "Croatian - Croatia",
		'pa-IN' => "Punjabi - India",
		'cs' => "Czech",
		'ro' => "Romanian",
		'cs-CZ' => "Czech - Czech Republic",
		'ro-RO' => "Romanian - Romania",
		'da' => "Danish",
		'ru' => "Russian",
		'da-DK' => "Danish - Denmark",
		'ru-RU' => "Russian - Russia",
		'div' => "Dhivehi",
		'sa' => "Sanskrit",
		'div-MV' => "Dhivehi - Maldives",
		'sa-IN' => "Sanskrit - India",
		'nl' => "Dutch",
		'sr-SP-Cyrl' => "Serbian (Cyrillic) - Serbia",
		'nl-BE' => "Dutch - Belgium",
		'sr-SP-Latn' => "Serbian (Latin) - Serbia",
		'nl-NL' => "Dutch - The Netherlands",
		'sk' => "Slovak",
		'en' => "English",
		'sk-SK' => "Slovak - Slovakia",
		'en-AU' => "English - Australia",
		'sl' => "Slovenian",
		'en-BZ' => "English - Belize",
		'sl-SI' => "Slovenian - Slovenia",
		'en-CA' => "English - Canada",
		'es' => "Spanish",
		'en-CB' => "English - Caribbean",
		'es-AR' => "Spanish - Argentina",
		'en-IE' => "English - Ireland",
		'es-BO' => "Spanish - Bolivia",
		'en-JM' => "English - Jamaica",
		'es-CL' => "Spanish - Chile",
		'en-NZ' => "English - New Zealand",
		'es-CO' => "Spanish - Colombia",
		'en-PH' => "English - Philippines",
		'es-CR' => "Spanish - Costa Rica",
		'en-ZA' => "English - South Africa",
		'es-DO' => "Spanish - Dominican Republic",
		'en-TT' => "English - Trinidad and Tobago",
		'es-EC' => "Spanish - Ecuador",
		'en-GB' => "English - United Kingdom",
		'es-SV' => "Spanish - El Salvador",
		'en-US' => "English - United States",
		'es-GT' => "Spanish - Guatemala",
		'en-ZW' => "English - Zimbabwe",
		'es-HN' => "Spanish - Honduras",
		'et' => "Estonian",
		'es-MX' => "Spanish - Mexico",
		'et-EE' => "Estonian - Estonia",
		'es-NI' => "Spanish - Nicaragua",
		'fo' => "Faroese",
		'es-PA' => "Spanish - Panama",
		'fo-FO' => "Faroese - Faroe Islands",
		'es-PY' => "Spanish - Paraguay",
		'fa' => "Farsi",
		'es-PE' => "Spanish - Peru",
		'fa-IR' => "Farsi - Iran",
		'es-PR' => "Spanish - Puerto Rico",
		'fi' => "Finnish",
		'es-ES' => "Spanish - Spain",
		'fi-FI' => "Finnish - Finland",
		'es-UY' => "Spanish - Uruguay",
		'fr' => "French",
		'es-VE' => "Spanish - Venezuela",
		'fr-BE' => "French - Belgium",
		'sw' => "Swahili",
		'fr-CA' => "French - Canada",
		'sw-KE' => "Swahili - Kenya",
		'fr-FR' => "French - France",
		'sv' => "Swedish",
		'fr-LU' => "French - Luxembourg",
		'sv-FI' => "Swedish - Finland",
		'fr-MC' => "French - Monaco",
		'sv-SE' => "Swedish - Sweden",
		'fr-CH' => "French - Switzerland",
		'syr' => "Syriac",
		'gl' => "Galician",
		'syr-SY' => "Syriac - Syria",
		'gl-ES' => "Galician - Galician",
		'ta' => "Tamil",
		'ka' => "Georgian",
		'ta-IN' => "Tamil - India",
		'ka-GE' => "Georgian - Georgia",
		'tt' => "Tatar",
		'de' => "German",
		'tt-RU' => "Tatar - Russia",
		'de-AT' => "German - Austria",
		'te' => "Telugu",
		'de-DE' => "German - Germany",
		'te-IN' => "Telugu - India",
		'de-LI' => "German - Liechtenstein",
		'th' => "Thai",
		'de-LU' => "German - Luxembourg",
		'th-TH' => "Thai - Thailand",
		'de-CH' => "German - Switzerland",
		'tr' => "Turkish",
		'el' => "Greek",
		'tr-TR' => "Turkish - Turkey",
		'el-GR' => "Greek - Greece",
		'uk' => "Ukrainian",
		'gu' => "Gujarati",
		'uk-UA' => "Ukrainian - Ukraine",
		'gu-IN' => "Gujarati - India",
		'ur' => "Urdu",
		'he' => "Hebrew",
		'ur-PK' => "Urdu - Pakistan",
		'he-IL' => "Hebrew - Israel",
		'uz' => "Uzbek",
		'hi' => "Hindi",
		'uz-UZ-Cyrl' => "Uzbek (Cyrillic) - Uzbekistan",
		'hi-IN' => "Hindi - India",
		'uz-UZ-Latn' => "Uzbek (Latin) - Uzbekistan",
		'hu' => "Hungarian",
		'vi' => "Vietnamese"
	);
	asort( $locales );
	return $locales;
}

namespace Podlove\Itunes;

/**
 * iTunes category generator.
 * 
 * Gratefully borrowed from powerpress.
 * 
 * @param bool $prefix_subcategories
 * @return array
 */
function categories( $prefix_subcategories = true ) {
	$temp = array();
	$temp['01-00'] = 'Arts';
		$temp['01-01'] = 'Design';
		$temp['01-02'] = 'Fashion & Beauty';
		$temp['01-03'] = 'Food';
		$temp['01-04'] = 'Literature';
		$temp['01-05'] = 'Performing Arts';
		$temp['01-06'] = 'Visual Arts';

	$temp['02-00'] = 'Business';
		$temp['02-01'] = 'Business News';
		$temp['02-02'] = 'Careers';
		$temp['02-03'] = 'Investing';
		$temp['02-04'] = 'Management & Marketing';
		$temp['02-05'] = 'Shopping';

	$temp['03-00'] = 'Comedy';

	$temp['04-00'] = 'Education';
		$temp['04-01'] = 'Education Technology';
		$temp['04-02'] = 'Higher Education';
		$temp['04-03'] = 'K-12';
		$temp['04-04'] = 'Language Courses';
		$temp['04-05'] = 'Training';
		 
	$temp['05-00'] = 'Games & Hobbies';
		$temp['05-01'] = 'Automotive';
		$temp['05-02'] = 'Aviation';
		$temp['05-03'] = 'Hobbies';
		$temp['05-04'] = 'Other Games';
		$temp['05-05'] = 'Video Games';

	$temp['06-00'] = 'Government & Organizations';
		$temp['06-01'] = 'Local';
		$temp['06-02'] = 'National';
		$temp['06-03'] = 'Non-Profit';
		$temp['06-04'] = 'Regional';

	$temp['07-00'] = 'Health';
		$temp['07-01'] = 'Alternative Health';
		$temp['07-02'] = 'Fitness & Nutrition';
		$temp['07-03'] = 'Self-Help';
		$temp['07-04'] = 'Sexuality';

	$temp['08-00'] = 'Kids & Family';
 
	$temp['09-00'] = 'Music';
 
	$temp['10-00'] = 'News & Politics';
 
	$temp['11-00'] = 'Religion & Spirituality';
		$temp['11-01'] = 'Buddhism';
		$temp['11-02'] = 'Christianity';
		$temp['11-03'] = 'Hinduism';
		$temp['11-04'] = 'Islam';
		$temp['11-05'] = 'Judaism';
		$temp['11-06'] = 'Other';
		$temp['11-07'] = 'Spirituality';
	 
	$temp['12-00'] = 'Science & Medicine';
		$temp['12-01'] = 'Medicine';
		$temp['12-02'] = 'Natural Sciences';
		$temp['12-03'] = 'Social Sciences';
	 
	$temp['13-00'] = 'Society & Culture';
		$temp['13-01'] = 'History';
		$temp['13-02'] = 'Personal Journals';
		$temp['13-03'] = 'Philosophy';
		$temp['13-04'] = 'Places & Travel';

	$temp['14-00'] = 'Sports & Recreation';
		$temp['14-01'] = 'Amateur';
		$temp['14-02'] = 'College & High School';
		$temp['14-03'] = 'Outdoor';
		$temp['14-04'] = 'Professional';
		 
	$temp['15-00'] = 'Technology';
		$temp['15-01'] = 'Gadgets';
		$temp['15-02'] = 'Tech News';
		$temp['15-03'] = 'Podcasting';
		$temp['15-04'] = 'Software How-To';

	$temp['16-00'] = 'TV & Film';

	if ( $prefix_subcategories ) {
		while ( list( $key, $val ) = each( $temp ) ) {
			$parts  = explode( '-', $key );
			$cat    = $parts[ 0 ];
			$subcat = $parts[ 1 ];
		 
			if( $subcat != '00' )
				$temp[ $key ] = $temp[ $cat . '-00' ] . ' > ' . $val;
		}
		reset( $temp );
	}
 
	return $temp;
}

namespace Podlove\Flattr;

function getFlattrScript() {
	return "<script type=\"text/javascript\">\n
		/* <![CDATA[ */
	    (function() {
		     var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
		     s.type = 'text/javascript';
		     s.async = true;
		    s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
		    t.parentNode.insertBefore(s, t);
			 })();
		/* ]]> */</script>\n";
}
