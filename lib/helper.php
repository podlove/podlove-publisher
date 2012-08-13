<?php
namespace Podlove;

function format_bytes( $size, $decimals = 2 ) {
    $units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
    for ( $i = 0; $size >= 1024 && $i < 4; $i++ ) $size /= 1024;
    return round( $size, $decimals ) . $units[$i];
}

function get_setting( $name ) {
	
	$defaults = array(
		'merge_episodes' => 'off' // can't be "on"
	);

	$options = get_option( 'podlove' );
	$options = wp_parse_args( $options, $defaults );

	return $options[ $name ];
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

	// determine method
	$method = isset( $args['method'] ) ? $args['method'] : 'post';

	// determine context
	$context = isset( $args['context'] ) ? $args['context'] : '';

	// check if <form> should be printed
	$print_form = ! isset( $args['form'] ) || $args['form'] === true;

	?>
	<?php if ( $print_form ): ?>
		<form action="<?php echo $url; ?>" method="<?php echo $method; ?>">
	<?php endif ?>

	<?php if ( isset( $args['hidden'] ) && $args['hidden'] ): ?>
		<?php foreach ( $args['hidden'] as $name => $value ): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />		
		<?php endforeach ?>
	<?php endif ?>

	<table class="form-table">
		<?php call_user_func( $callback, new \Podlove\Form\Input\Builder( $object, $context ) ); ?>
	</table>
	<?php if ( ! isset( $args['submit_button'] ) || $args['submit_button'] === true ): ?>
		<?php submit_button(); ?>
	<?php endif ?>

	<?php if ( $print_form ): ?>
		</form>
	<?php endif ?>
	
	<?php
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
	ksort( $locales );
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
