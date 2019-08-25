<?php
use Podlove\Model;
use Podlove\Geo_Ip;

add_action( 'wp', 'podlove_handle_media_file_download' );
add_action( 'podlove_download_file', 'podlove_handle_media_file_tracking' );

function podlove_get_query_var($var_name) {
	if (isset($_GET[$var_name])) {
		return $_GET[$var_name];
	} else {
		return get_query_var($var_name);
	}
}

function podlove_get_remote_addr()
{
    if (getenv('HTTP_X_REAL_IP')) {
        return getenv('HTTP_X_REAL_IP');
    }
    if (getenv('HTTP_X_FORWARDED_FOR')) {
        return explode(',', getenv('HTTP_X_FORWARDED_FOR'))[0];
    }
    return getenv('REMOTE_ADDR');
}


function ga_track_download($request_id, $media_file, $ua_string, $ptm_context, $ptm_source) {
	// GA Tracking
	$debug_ga = false;
	$ga_collect_endpoint = 'https://www.google-analytics.com/' . ($debug_ga ? 'debug/' : '') . 'collect';

	$ga_tracking_id = trim(\Podlove\get_setting('tracking', 'ga'));
	if (!$ga_tracking_id || $ga_tracking_id === '') {
		return;
	}

	$episode = $media_file->episode();
	$title = $episode->title();

	$ga_params = array(
		// Basics
		'v' => '1', // version
		'tid' => $ga_tracking_id, // tracking id
		'cid' => $request_id, // client id
		'ua' => $ua_string, // user agent override
		'uip' => podlove_get_remote_addr(), // IP override
		'ds' => 'podlove', // data source

		// We highjack the campaign fields for context/source data.
		// Source / Medium maps to Podlove context / Podlove source.
		// This way all Podlove sources can be easily grouped into GA Channels.
		'cs' => $ptm_context, // campaign source
		'cm' => $ptm_source, // campaign medium
		'ci' => $episode->number, // campaign id
		'cn' => $title, // campaign name

		// Pageview params
		't' => 'pageview', // hit type
		'dh' => $_SERVER['HTTP_HOST'], // document host
		'dp' => $_SERVER['REQUEST_URI'], // document path
		'dt' => $title, // document title
	);

	$ga_param_fragments = array();
	array_walk($ga_params, function($item, $key) use(&$ga_param_fragments) {
		array_push($ga_param_fragments, sprintf('%s=%s', $key, rawurlencode($item)));
	});

	$body = implode('&', $ga_param_fragments);
	$curl = new \Podlove\Http\Curl();
	$curl->request( $ga_collect_endpoint, array(
		'method'  => 'POST',
		'body'    => $body,
	));

	if (!$curl->isSuccessful()) {
		if ($debug_ga) {
			header('x-ga-debug: http error');
		}
		\Podlove\Log::get()->addDebug('GA Measurement Protocol request failed.');
	} else {
		\Podlove\Log::get()->addDebug('GA Measurement Protocol request successful: ' . $body);
		if (!$debug_ga) {
			return;
		}

		$response = json_decode($curl->get_response()['body'], true);
		$hit_paring_result = $response['hitParsingResult'][0];
		if ($hit_paring_result['valid']) {
			header('x-ga-debug: valid');
			\Podlove\Log::get()->addDebug('GA Measurement Protocol hit valid.');
		} else {
			$debug_message = sprintf('%s(%s): %s', $hit_paring_result['parserMessage'][0]['messageType'], $response['hitParsingResult'][0]['parserMessage'][0]['parameter'], $response['hitParsingResult'][0]['parserMessage'][0]['description']);
			header(sprintf('x-ga-debug: ' . $debug_message ));
			\Podlove\Log::get()->addDebug('GA Measurement Protocol hit invalid.', $hit_paring_result['parserMessage'][0]);
		}
	}
}

function podlove_handle_media_file_tracking(\Podlove\Model\MediaFile $media_file) {

	if (\Podlove\get_setting('tracking', 'mode') !== "ptm_analytics")
		return;

	if (strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD')
		return;

	$intent = new Model\DownloadIntent;
	$intent->media_file_id = $media_file->id;
	$intent->accessed_at = date('Y-m-d H:i:s');

	$ptm_source  = trim(podlove_get_query_var('ptm_source'));
	$ptm_context = trim(podlove_get_query_var('ptm_context'));

	if ($ptm_source)
		$intent->source = $ptm_source;

	if ($ptm_context)
		$intent->context = $ptm_context;

	// set user agent
	$ua_string = trim($_SERVER['HTTP_USER_AGENT']);
	if ($agent = Model\UserAgent::find_or_create_by_uastring($ua_string)) {
		$intent->user_agent_id = $agent->id;
	}

	// save HTTP range header
	// @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35 for spec
	if (isset($_SERVER['HTTP_RANGE']))
		$intent->httprange = $_SERVER['HTTP_RANGE'];

	$ip_string = podlove_get_remote_addr();

	if (function_exists('openssl_digest')) {
		$intent->request_id = openssl_digest($ip_string . $ua_string, 'sha256');
	} else {
		$intent->request_id = sha1($ip_string . $ua_string);
	}

	if (Geo_Ip::is_enabled()) {
		$intent = $intent->add_geo_data($ip_string);
	}

	$intent->save();

	ga_track_download($intent->request_id, $media_file, $ua_string, $ptm_context, $ptm_source);
}

function podlove_handle_media_file_download() {

	$download_media_file = podlove_get_query_var('download_media_file');

	if (!$download_media_file)
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	// use this hook to short-circuit the download logic
	if (apply_filters('podlove_pre_media_file_download', false, $download_media_file))
		exit;

	$media_file_id = (int) $download_media_file;
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file ) {
		status_header( 404, 'Media File not found' );
		exit;
	}

	$episode_asset = $media_file->episode_asset();

	if ( ! $episode_asset ) {
		status_header( 404, 'Asset not found' );
		exit;
	}

	// if a file exists but no valid episode reference,
	// that means it has been removed
	$episode = $media_file->episode();

	if ( ! $episode || ! $episode->is_valid() ) {
		status_header( 410, 'Gone' );
		exit;
	}

	do_action('podlove_download_file', $media_file);

	// build redirect url
	$location = $media_file->add_ptm_parameters(
		$media_file->get_file_url(),
		[
			'source'  => trim(podlove_get_query_var('ptm_source')),
			'context' => trim(podlove_get_query_var('ptm_context')),
			'request' => substr(md5(uniqid(microtime() . mt_rand(), true)), 0, 12)
		]
	);

	header("HTTP/1.1 301 Moved Permanently");
	header("Location: " . $location);
	exit;
}

// add route for file downloads
add_action( 'init', function () {
    add_rewrite_rule(
        '^podlove/file/([0-9]+)/s/([^/]+)/c/([^/]+)/.+/?$',
        'index.php?download_media_file=$matches[1]&ptm_source=$matches[2]&ptm_context=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        '^podlove/file/([0-9]+)/s/([^/]+)/.+/?$',
        'index.php?download_media_file=$matches[1]&ptm_source=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        '^podlove/file/([0-9]+)/.+/?$',
        'index.php?download_media_file=$matches[1]',
        'top'
    );
}, 10 );

add_filter( 'query_vars', function ( $query_vars ){
    $query_vars[] = 'download_media_file';
    $query_vars[] = 'ptm_source';
    $query_vars[] = 'ptm_context';
    return $query_vars;
}, 10, 1 );

// don't add trailing slash to file URLs
add_filter('redirect_canonical', function($redirect_url, $requested_url) {
	if ((int) get_query_var('download_media_file')) {
		return false;
	} else {
		return $redirect_url;
	}
}, 10, 2);
