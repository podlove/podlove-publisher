<?php
use Leth\IPAddress\IP, Leth\IPAddress\IPv4, Leth\IPAddress\IPv6;
use \Podlove\Model;

add_action( 'wp', 'podlove_handle_media_file_download' );

function podlove_handle_media_file_download() {

	$get_query_var = function($var_name) {
		if (isset($_GET[$var_name])) {
			return $_GET[$var_name];
		} else {
			return; get_query_var($var_name);
		}		
	}

	$download_media_file = (int) $get_query_var('download_media_file');
	$ptm_source          = trim($get_query_var('ptm_source'));
	$ptm_context         = trim($get_query_var('ptm_context'));

	if (!$download_media_file)
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	// FIXME: this is a hack for bitlove => so move it in this module AND make sure the location in valid
	// if download_media_file is a URL, download directly
	if ( filter_var( $download_media_file, FILTER_VALIDATE_URL ) ) {
		$parsed_url = parse_url($download_media_file);
		$file_name = substr( $parsed_url['path'], strrpos( $parsed_url['path'], "/" ) + 1 );
		header( "Expires: 0" );
		header( 'Cache-Control: must-revalidate' );
	    header( 'Pragma: public' );
		header( "Content-Type: application/x-bittorrent" );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=$file_name" );
		header( "Content-Transfer-Encoding: binary" );
		ob_clean();
		flush();
		while ( @ob_end_flush() ); // flush and end all output buffers
		readfile( $download_media_file );
		exit;
	}

	$media_file_id = $download_media_file;
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file ) {
		status_header( 404 );
		exit;
	}

	$episode_asset = $media_file->episode_asset();

	if ( ! $episode_asset || ! $episode_asset->downloadable ) {
		status_header( 404 );
		exit;
	}

	if (\Podlove\get_setting('tracking', 'mode') === "ptm_analytics" && strtoupper($_SERVER['REQUEST_METHOD']) !== 'HEAD') {
		$intent = new Model\DownloadIntent;
		$intent->media_file_id = $media_file_id;
		$intent->accessed_at = date('Y-m-d H:i:s');
		
		if ($ptm_source)
			$intent->source = $ptm_source;

		if ($ptm_context)
			$intent->context = $ptm_context;

		// set user agent
		$ua_string = trim($_SERVER['HTTP_USER_AGENT']);
		if (strlen($ua_string)) {
			if (!($agent = Model\UserAgent::find_one_by_user_agent($ua_string))) {
				$agent = new Model\UserAgent;
				$agent->user_agent = $ua_string;
				$agent->save();
			}
			$intent->user_agent_id = $agent->id;
		}

		// save HTTP range header
		// @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35 for spec
		if (isset($_SERVER['HTTP_RANGE']))
			$intent->httprange = $_SERVER['HTTP_RANGE'];

		// get ip, but don't store it
		$ip = IP\Address::factory($_SERVER['REMOTE_ADDR']);
		if (method_exists($ip, 'as_IPv6_address')) {
			$ip = $ip->as_IPv6_address();
		}
		$ip_string = $ip->format(IP\Address::FORMAT_COMPACT);

		// Generate a hash from IP address and UserAgent so we can identify
		// identical requests without storing an IP address.
		$intent->request_id = openssl_digest($ip_string . $ua_string, 'sha256');
		$intent = $intent->add_geo_data($ip_string);

		$intent->save();
	}

	$location = $media_file->add_ptm_parameters(
		$media_file->get_file_url(),
		array(
			'source'  => $intent->source,
			'context' => $intent->context
		)
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
