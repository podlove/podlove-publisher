<?php
namespace Podlove;

function load_template($path, $vars = []) {
	$template = null;

	$paths = [
		\Podlove\PLUGIN_DIR . 'views/' . $path . '.php',
		\Podlove\PLUGIN_DIR . $path . '.php'
	];

	foreach ($paths as $path) {
		if (file_exists($path)) {
			$template = $path;
			break;
		}
	}

	extract($vars);
	require $template;
}

function maybe_encode_emoji($string)
{
	if (function_exists('wp_encode_emoji'))
		return \wp_encode_emoji($string);

	return $string;
}

/**
 * Duplicate of $wpdb::esc_like
 * 
 * Can be replaced once we bump WordPress version dependency to 4.0+
 */
function esc_like( $text ) {
	return addcslashes( $text, '_%\\' );
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

function get_help_link($tab_id, $title = '<sup>?</sup>') {
	return sprintf('<a href="#" data-podlove-help="%s">%s</a>', $tab_id, $title);
}

/**
 * Checks if given file is an image based on mime type
 * 
 * @param  string  $file
 * @return boolean
 */
function is_image($file) {
	$type = get_image_type($file);
	$mime = get_image_mime_type($type);

	return substr($mime, 0, 5) == 'image';
}

function get_image_type($file) {
	if (function_exists('exif_imagetype')) {
		return exif_imagetype($file);
	} else {
		$image = getimagesize($file);
		return $image[2];
	}
}

function get_image_mime_type($image_type) {
	return image_type_to_mime_type($image_type);
}

function get_setting( $namespace, $name ) {
	
	$defaults = [
		'website' => [
			'merge_episodes'         => 'on',
			'hide_wp_feed_discovery' => 'off',
			'use_post_permastruct'   => 'on',
			'custom_episode_slug'    => '/podcast/%podcast%/',
			'episode_archive'        => 'on',
			'episode_archive_slug'   => '/podcast/',
			'url_template'           => '%media_file_base_url%%episode_slug%%suffix%.%format_extension%',
			'ssl_verify_peer'        => 'on',
			'landing_page'           => 'homepage',
			'feeds_skip_redirect'    => 'off',
			'feeds_force_protocol'   => 'default',
			'enable_generated_blog_post_title' => false,
			'blog_title_template' => '%mnemonic%%episode_number% %episode_title%',
			'episode_number_padding' => 3
		],
		'metadata' => [
			'enable_episode_recording_date' => 0,
			'enable_episode_explicit'       => 0,
			'enable_episode_license'        => 0
		],
		'redirects' => [
			'podlove_setting_redirect' => [],
		],
		'tracking' => [
			'mode' => 'ptm_analytics',
			'window' => 'hourly'
		],
		'notifications' => [
			'delay' => 1,
			'subject' => "\"{{ podcast.title }}\" Episode Published: {{ episode.title }}",
			'body' => 'Hi {{ contributor.name }},

You get this email because you were part of "{{ podcast.title }}".
Episode "{{ episode.title }}" was just released.

URL: {{ episode.url }}

{% if podcast.ownerName %}
Regards,
{{ podcast.ownerName }}
{% endif %}',
			'send_as' => null,
			'group' => null,
			'role'  => null
		],
		'notifications_test' => [
			'receiver' => '',
			'episode' => 0
		]
	];

	$options = get_option( 'podlove_' . $namespace );
	$options = wp_parse_args( $options, $defaults[ $namespace ] );

	if (isset($options[ $name ])) {
		return $options[ $name ];
	} else {
		return null;
	}
}

function save_setting( $namespace, $name, $values ) {
	update_option( 'podlove_' . $namespace, array( $name => $values ) );
}

/**
 * Are we on the WordPress Settings API save page?
 * 
 * DO NOT USE filter_input here. There seems to be a PHP bug that on some
 * systems prevents filter_input to work for INPUT_SERVER and INPUT_ENV.
 * @see  http://stackoverflow.com/questions/25232975/php-filter-inputinput-server-request-method-returns-null
 * 
 * @return boolean
 */
function is_options_save_page() {
    $self    = $_SERVER['PHP_SELF'];
    $request = $_SERVER['REQUEST_URI'];

	return stripos($self, 'options.php') !== FALSE || stripos($request, 'options.php') !== FALSE;
}

/**
 * Are we on a Podlove Settings screen?
 * 
 * @return boolean
 */
function is_podlove_settings_screen() {
	$screen = get_current_screen();
	return stripos($screen->id, 'podlove') !== false;
}

/**
 * Are we on an edit screen for episodes?
 * 
 * @return boolean
 */
function is_episode_edit_screen() {
	$screen = get_current_screen();
	return in_array($screen->base, ['edit', 'post']) && $screen->post_type == 'podcast';
}

/**
 * Podcast Landing Page URL
 * 
 * @todo  move to Model\Podcast->get_landing_page_url()
 * 
 * @return string
 */
function get_landing_page_url() {
	$landing_page = \Podlove\get_setting('website', 'landing_page');

	switch ($landing_page) {
		case 'homepage':
			return home_url();
			break;
		case 'archive':
			if ( 'on' == \Podlove\get_setting( 'website', 'episode_archive' ) ) {
				$archive_slug = trim( \Podlove\get_setting( 'website', 'episode_archive_slug' ), '/' );

				$blog_prefix = \Podlove\get_blog_prefix();
				$blog_prefix = $blog_prefix ? trim( $blog_prefix, '/' ) . '/' : '';

				return trailingslashit(get_option('home') . $blog_prefix) . $archive_slug;
			}
			break;
		default:
			if (is_numeric($landing_page)) {
				if ($link = get_permalink($landing_page)) {
					return $link;
				}
			}
			break;
	}

	// always default to home page
	return home_url();
}

function get_webplayer_defaults() {
	return [
		'chaptersVisible' => 'false',
		'inject'          => 'manually',
		'version'         => 'player_v4',
		'playerv3theme'   => 'pwp-dark-green.min.css',
		'podigeetheme'    => 'default',
		'playerv4_color_primary'   => get_background_color(),
		'playerv4_color_secondary' => get_header_textcolor(),
		'playerv4_visible_components' => [
			'controlChapters' => "on",
			'controlSteppers' => "on",
			'episodeTitle' => "on",
			'poster' => "on",
			'progressbar' => "on",
			'showTitle' => "on",
			'subtitle' => "on",
			'tabAudio' => "on",
			'tabChapters' => "on",
			'tabFiles' => "on",
			'tabShare' => "on",
			'tabInfo' => "on",
			'tabTranscripts' => "on"
		],
		'playerv4_use_podcast_language' => false
	];
}

function get_webplayer_settings() {
	$settings = get_option('podlove_webplayer_settings', []);
	$settings = array_filter($settings);
	$settings = wp_parse_args($settings, get_webplayer_defaults());
	return $settings;
}

function get_webplayer_setting($name) {
	return get_webplayer_settings()[$name];
}

// create slugs for text/titles
function slugify($slug) {
	$slug = trim($slug);
	// replace everything but unreserved characters (RFC 3986 section 2.3) and slashes by a hyphen
	$slug = preg_replace('~[^\\pL\d_\.\~/]~u', '-', $slug);
	$slug = rawurlencode($slug);
	$slug = str_replace("%2F", "/", $slug);

	return empty($slug) ? 'n-a' : $slug;
}

// prepare an existing episode slug for use in URL
function prepare_episode_slug_for_url($slug)
{
	$slug = trim($slug);
	$slug = rawurlencode($slug);
	
	// allow directories in slug
	$slug = str_replace("%2F", "/", $slug);

	return $slug;
}

function with_blog_scope($blog_id, $callback) {
	$result = NULL;

	if ($blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$result = $callback();
		restore_current_blog();
	} else {
		$result = $callback();
	}

	return $result;
}

function relative_time_steps($time) {
	$time_diff = time() - $time;
	$formated_time_string = date('Y-m-d h:i:s', $time);

	if ($time_diff == 0) {
		return __('Now', 'podlove-podcasting-plugin-for-wordpress');
	} else {
		$time_text = $formated_time_string;

		if     ($time_diff < 60)	$time_text = __( 'Just now', 'podlove-podcasting-plugin-for-wordpress' );
		elseif ($time_diff < 120)	$time_text = __( '1 minute ago', 'podlove-podcasting-plugin-for-wordpress' );
		elseif ($time_diff < 3600)	$time_text = sprintf(__( '%s minutes ago', 'podlove-podcasting-plugin-for-wordpress' ), floor($time_diff / 60));
		elseif ($time_diff < 7200)	$time_text = __( '1 hour ago', 'podlove-podcasting-plugin-for-wordpress' );
 		elseif ($time_diff < 86400)	$time_text = sprintf(__( '%s hours ago', 'podlove-podcasting-plugin-for-wordpress' ), floor($time_diff / 3600));

		return sprintf('<span title="%s">%s</span>', $formated_time_string, $time_text);
	}
}

function episode_types()
{
	return [
		'full'    => __('full (complete content of an episode)', 'podlove-podcasting-plugin-for-wordpress'),
		'trailer' => __('trailer (short, promotional piece of content that represents a preview of an episode)', 'podlove-podcasting-plugin-for-wordpress'),
		'bonus'   => __('bonus (extra content for an episode, for example behind the scenes information)', 'podlove-podcasting-plugin-for-wordpress')
	];
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
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		if ($page) {
			$url .= '?page=' . $page;
		}
	}

	// determine form html attributes
	$attributes_html = '';
	if ( isset( $args['attributes'] ) ) {
		$attributes = array();
		foreach ( $args['attributes'] as $attr_key => $attr_value ) {
			$attributes[] = sprintf( '%s = "%s"', $attr_key, esc_attr($attr_value) );
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
		<form action="<?php echo esc_url($url); ?>" method="<?php echo esc_attr($method); ?>" <?php echo $attributes_html ?>>
	<?php endif ?>

	<?php if ( isset( $args['hidden'] ) && $args['hidden'] ): ?>
		<?php foreach ( $args['hidden'] as $name => $value ): ?>
			<input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" />		
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

	<?php if ( isset($args['form_end']) && is_callable($args['form_end'])): ?>
		<?php call_user_func( $args['form_end'] ); ?>
	<?php endif; ?>

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
		'af-ZA' => "Afrikaans - South Africa",
		'ar' => "Arabic",
		'ar-AE' => "Arabic - United Arab Emirates",
		'ar-BH' => "Arabic - Bahrain",
		'ar-DZ' => "Arabic - Algeria",
		'ar-EG' => "Arabic - Egypt",
		'ar-IQ' => "Arabic - Iraq",
		'ar-JO' => "Arabic - Jordan",
		'ar-KW' => "Arabic - Kuwait",
		'ar-LB' => "Arabic - Lebanon",
		'ar-LY' => "Arabic - Libya",
		'ar-MA' => "Arabic - Morocco",
		'ar-OM' => "Arabic - Oman",
		'ar-QA' => "Arabic - Qatar",
		'ar-SA' => "Arabic - Saudi Arabia",
		'ar-SY' => "Arabic - Syria",
		'ar-TN' => "Arabic - Tunisia",
		'ar-YE' => "Arabic - Yemen",
		'az' => "Azeri",
		'az-AZ-Cyrl' => "Azeri (Cyrillic) - Azerbaijan",
		'az-AZ-Latn' => "Azeri (Latin) - Azerbaijan",
		'be' => "Belarusian",
		'be-BY' => "Belarusian - Belarus",
		'bg' => "Bulgarian",
		'bg-BG' => "Bulgarian - Bulgaria",
		'ca' => "Catalan",
		'ca-ES' => "Catalan - Catalan",
		'cs' => "Czech",
		'cs-CZ' => "Czech - Czech Republic",
		'da' => "Danish",
		'da-DK' => "Danish - Denmark",
		'de' => "German",
		'de-AT' => "German - Austria",
		'de-CH' => "German - Switzerland",
		'de-DE' => "German - Germany",
		'de-LI' => "German - Liechtenstein",
		'de-LU' => "German - Luxembourg",
		'div' => "Dhivehi",
		'div-MV' => "Dhivehi - Maldives",
		'el' => "Greek",
		'el-GR' => "Greek - Greece",
		'en' => "English",
		'en-AU' => "English - Australia",
		'en-BZ' => "English - Belize",
		'en-CA' => "English - Canada",
		'en-CB' => "English - Caribbean",
		'en-GB' => "English - United Kingdom",
		'en-IE' => "English - Ireland",
		'en-JM' => "English - Jamaica",
		'en-NZ' => "English - New Zealand",
		'en-PH' => "English - Philippines",
		'en-TT' => "English - Trinidad and Tobago",
		'en-US' => "English - United States",
		'en-ZA' => "English - South Africa",
		'en-ZW' => "English - Zimbabwe",
		'eo' => "Esperanto",
		'es' => "Spanish",
		'es-AR' => "Spanish - Argentina",
		'es-BO' => "Spanish - Bolivia",
		'es-CL' => "Spanish - Chile",
		'es-CO' => "Spanish - Colombia",
		'es-CR' => "Spanish - Costa Rica",
		'es-DO' => "Spanish - Dominican Republic",
		'es-EC' => "Spanish - Ecuador",
		'es-ES' => "Spanish - Spain",
		'es-GT' => "Spanish - Guatemala",
		'es-HN' => "Spanish - Honduras",
		'es-MX' => "Spanish - Mexico",
		'es-NI' => "Spanish - Nicaragua",
		'es-PA' => "Spanish - Panama",
		'es-PE' => "Spanish - Peru",
		'es-PR' => "Spanish - Puerto Rico",
		'es-PY' => "Spanish - Paraguay",
		'es-SV' => "Spanish - El Salvador",
		'es-UY' => "Spanish - Uruguay",
		'es-VE' => "Spanish - Venezuela",
		'et' => "Estonian",
		'et-EE' => "Estonian - Estonia",
		'eu' => "Basque",
		'eu-ES' => "Basque - Basque",
		'fa' => "Farsi",
		'fa-IR' => "Farsi - Iran",
		'fi' => "Finnish",
		'fi-FI' => "Finnish - Finland",
		'fo' => "Faroese",
		'fo-FO' => "Faroese - Faroe Islands",
		'fr' => "French",
		'fr-BE' => "French - Belgium",
		'fr-CA' => "French - Canada",
		'fr-CH' => "French - Switzerland",
		'fr-FR' => "French - France",
		'fr-LU' => "French - Luxembourg",
		'fr-MC' => "French - Monaco",
		'gl' => "Galician",
		'gl-ES' => "Galician - Galician",
		'gu' => "Gujarati",
		'gu-IN' => "Gujarati - India",
		'he' => "Hebrew",
		'he-IL' => "Hebrew - Israel",
		'hi' => "Hindi",
		'hi-IN' => "Hindi - India",
		'hr' => "Croatian",
		'hr-HR' => "Croatian - Croatia",
		'hu' => "Hungarian",
		'hu-HU' => "Hungarian - Hungary",
		'hy' => "Armenian",
		'hy-AM' => "Armenian - Armenia",
		'id' => "Indonesian",
		'id-ID' => "Indonesian - Indonesia",
		'is' => "Icelandic",
		'is-IS' => "Icelandic - Iceland",
		'it' => "Italian",
		'it-CH' => "Italian - Switzerland",
		'it-IT' => "Italian - Italy",
		'ja' => "Japanese",
		'ja-JP' => "Japanese - Japan",
		'ka' => "Georgian",
		'ka-GE' => "Georgian - Georgia",
		'kk' => "Kazakh",
		'kk-KZ' => "Kazakh - Kazakhstan",
		'kn' => "Kannada",
		'kn-IN' => "Kannada - India",
		'ko' => "Korean",
		'ko-KR' => "Korean - Korea",
		'kok' => "Konkani",
		'kok-IN' => "Konkani - India",
		'ky' => "Kyrgyz",
		'ky-KG' => "Kyrgyz - Kyrgyzstan",
		'lb' => "Luxembourgish",
		'lt' => "Lithuanian",
		'lt-LT' => "Lithuanian - Lithuania",
		'lv' => "Latvian",
		'lv-LV' => "Latvian - Latvia",
		'mk' => "Macedonian",
		'mk-MK' => "Macedonian - Former Yugoslav Republic of Macedonia",
		'mn' => "Mongolian",
		'mn-MN' => "Mongolian - Mongolia",
		'mr' => "Marathi",
		'mr-IN' => "Marathi - India",
		'ms' => "Malay",
		'ms-BN' => "Malay - Brunei",
		'ms-MY' => "Malay - Malaysia",
		'nb-NO' => "Norwegian (Bokm�l) - Norway",
		'nl' => "Dutch",
		'nl-BE' => "Dutch - Belgium",
		'nl-NL' => "Dutch - The Netherlands",
		'nn-NO' => "Norwegian (Nynorsk) - Norway",
		'no' => "Norwegian",
		'pa' => "Punjabi",
		'pa-IN' => "Punjabi - India",
		'pl' => "Polish",
		'pl-PL' => "Polish - Poland",
		'pt' => "Portuguese",
		'pt-BR' => "Portuguese - Brazil",
		'pt-PT' => "Portuguese - Portugal",
		'ro' => "Romanian",
		'ro-RO' => "Romanian - Romania",
		'ru' => "Russian",
		'ru-RU' => "Russian - Russia",
		'sa' => "Sanskrit",
		'sa-IN' => "Sanskrit - India",
		'sk' => "Slovak",
		'sk-SK' => "Slovak - Slovakia",
		'sl' => "Slovenian",
		'sl-SI' => "Slovenian - Slovenia",
		'sq' => "Albanian",
		'sq-AL' => "Albanian - Albania",
		'sr-SP-Cyrl' => "Serbian (Cyrillic) - Serbia",
		'sr-SP-Latn' => "Serbian (Latin) - Serbia",
		'sv' => "Swedish",
		'sv-FI' => "Swedish - Finland",
		'sv-SE' => "Swedish - Sweden",
		'sw' => "Swahili",
		'sw-KE' => "Swahili - Kenya",
		'syr' => "Syriac",
		'syr-SY' => "Syriac - Syria",
		'ta' => "Tamil",
		'ta-IN' => "Tamil - India",
		'te' => "Telugu",
		'te-IN' => "Telugu - India",
		'th' => "Thai",
		'th-TH' => "Thai - Thailand",
		'tr' => "Turkish",
		'tr-TR' => "Turkish - Turkey",
		'tt' => "Tatar",
		'tt-RU' => "Tatar - Russia",
		'uk' => "Ukrainian",
		'uk-UA' => "Ukrainian - Ukraine",
		'ur' => "Urdu",
		'ur-PK' => "Urdu - Pakistan",
		'uz' => "Uzbek",
		'uz-UZ-Cyrl' => "Uzbek (Cyrillic) - Uzbekistan",
		'uz-UZ-Latn' => "Uzbek (Latin) - Uzbekistan",
		'vi' => "Vietnamese",
		'zh-CHS' => "Chinese (Simplified)",
		'zh-CHT' => "Chinese (Traditional)",
		'zh-CN' => "Chinese - China",
		'zh-HK' => "Chinese - Hong Kong SAR",
		'zh-MO' => "Chinese - Macao SAR",
		'zh-SG' => "Chinese - Singapore",
		'zh-TW' => "Chinese - Taiwan"
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
		$temp['01-04'] = 'Books';
		$temp['01-05'] = 'Performing Arts';
		$temp['01-06'] = 'Visual Arts';

	$temp['02-00'] = 'Business';
		$temp['02-02'] = 'Careers';
		$temp['02-03'] = 'Investing';
		$temp['02-04'] = 'Management';
		$temp['02-06'] = 'Entrepreneurship';
		$temp['02-07'] = 'Marketing';
		$temp['02-08'] = 'Non-Profit';

	$temp['03-00'] = 'Comedy';
	  $temp['03-01'] = 'Comedy Interviews';
	  $temp['03-02'] = 'Improv';
	  $temp['03-03'] = 'Stand-Up';

	$temp['04-00'] = 'Education';
		$temp['04-04'] = 'Language Learning';
		$temp['04-05'] = 'Courses';
		$temp['04-06'] = 'How To';
		$temp['04-07'] = 'Self-Improvement';

	$temp['20-00'] = 'Fiction';
		$temp['20-01'] = 'Comedy Fiction';
		$temp['20-02'] = 'Drama';
		$temp['20-03'] = 'Science Fiction';

	$temp['06-00'] = 'Government';

	$temp['30-00'] = 'History';

	$temp['07-00'] = 'Health & Fitness';
		$temp['07-01'] = 'Alternative Health';
		$temp['07-02'] = 'Fitness';
		// $temp['07-03'] = 'Self-Help';
		$temp['07-04'] = 'Sexuality';
		$temp['07-05'] = 'Medicine';
		$temp['07-06'] = 'Mental Health';
		$temp['07-07'] = 'Nutrition';

	$temp['08-00'] = 'Kids & Family';
	  $temp['08-01'] = 'Education for Kids';
	  $temp['08-02'] = 'Parenting';
	  $temp['08-03'] = 'Pets & Animals';
		$temp['08-04'] = 'Stories for Kids';
		
	$temp['40-00'] = 'Leisure';
  	$temp['40-01'] = 'Animation & Manga';
  	$temp['40-02'] = 'Automotive';
  	$temp['40-03'] = 'Aviation';
  	$temp['40-04'] = 'Crafts';
  	$temp['40-05'] = 'Games';
  	$temp['40-06'] = 'Hobbies';
  	$temp['40-07'] = 'Home & Garden';
  	$temp['40-08'] = 'Video Games';

 
	$temp['09-00'] = 'Music';
  	$temp['09-01'] = 'Music Commentary';
  	$temp['09-02'] = 'Music History';
  	$temp['09-03'] = 'Music Interviews';
 
	$temp['10-00'] = 'News';
  	$temp['10-01'] = 'Business News';
  	$temp['10-02'] = 'Daily News';
  	$temp['10-03'] = 'Entertainment News';
  	$temp['10-04'] = 'News Commentary';
  	$temp['10-05'] = 'Politics';
  	$temp['10-06'] = 'Sports News';
  	$temp['10-07'] = 'Tech News';
 
	$temp['11-00'] = 'Religion & Spirituality';
		$temp['11-01'] = 'Buddhism';
		$temp['11-02'] = 'Christianity';
		$temp['11-03'] = 'Hinduism';
		$temp['11-04'] = 'Islam';
		$temp['11-05'] = 'Judaism';
		$temp['11-06'] = 'Religion';
		$temp['11-07'] = 'Spirituality';
	 
	$temp['12-00'] = 'Science';
		$temp['12-01'] = 'Medicine';
		$temp['12-02'] = 'Natural Sciences';
		$temp['12-03'] = 'Social Sciences';
		$temp['12-04'] = 'Astronomy';
		$temp['12-05'] = 'Chemistry';
		$temp['12-06'] = 'Earth Sciences';
		$temp['12-07'] = 'Life Sciences';
		$temp['12-08'] = 'Mathematics';
		$temp['12-09'] = 'Nature';
		$temp['12-10'] = 'Physics';
	 
	$temp['13-00'] = 'Society & Culture';
		// $temp['13-01'] = 'History';
		$temp['13-02'] = 'Personal Journals';
		$temp['13-03'] = 'Philosophy';
		$temp['13-04'] = 'Places & Travel';
		$temp['13-05'] = 'Relationships';
		$temp['13-06'] = 'Documentary';

	$temp['14-00'] = 'Sports';
		$temp['14-05'] = 'Baseball';
    $temp['14-06'] = 'Basketball';
    $temp['14-07'] = 'Cricket';
    $temp['14-08'] = 'Fantasy Sports';
    $temp['14-09'] = 'Football';
    $temp['14-10'] = 'Golf';
    $temp['14-11'] = 'Hockey';
    $temp['14-12'] = 'Rugby';
    $temp['14-13'] = 'Running';
    $temp['14-14'] = 'Soccer';
    $temp['14-15'] = 'Swimming';
    $temp['14-16'] = 'Tennis';
    $temp['14-17'] = 'Volleyball';
    $temp['14-18'] = 'Wilderness';
    $temp['14-19'] = 'Wrestling';
		 
	$temp['15-00'] = 'Technology';

	$temp['50-00'] = 'True Crime';

	$temp['16-00'] = 'TV & Film';
  	$temp['16-01'] = 'After Shows';
  	$temp['16-02'] = 'Film History';
  	$temp['16-03'] = 'Film Interviews';
  	$temp['16-04'] = 'Film Reviews';
  	$temp['16-05'] = 'TV Reviews';

	if ( $prefix_subcategories ) {
		foreach ($temp as $key => $val) {
			$parts  = explode( '-', $key );
			$cat    = $parts[ 0 ];
			$subcat = $parts[ 1 ];
		 
			if ( $subcat != '00' )
				$temp[ $key ] = $temp[ $cat . '-00' ] . ' > ' . $val;
		}
	}
 
	return $temp;
}
