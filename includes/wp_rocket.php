<?php 
// WP-Rocket plugin compatibility
// 
// via:
//   - https://sendegate.de/t/webplayer-2-podigee-wird-nur-nach-anmeldung-gezeigt/4586/4?u=ericteubert
//   - http://docs.wp-rocket.me/article/19-resolving-issues-with-minification
//   - http://docs.wp-rocket.me/article/39-excluding-external-js-from-minification
function podlove_fix_wprocket_excluded_external_js( $external_js ) {

	// exclude our externals since it creates issues if they are mashed together
	$external_js[] = 'cdn.podigee.com';
	$external_js[] = 'cdn.podlove.org';

	return $external_js;
}

add_filter( 'rocket_minify_excluded_external_js', 'podlove_fix_wprocket_excluded_external_js' );
