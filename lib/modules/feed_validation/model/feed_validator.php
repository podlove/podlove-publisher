<?php
namespace Podlove\Modules\FeedValidation\Model;

class FeedValidator extends \Podlove\Model\Base {

	// Define Icons for validation
	const FEED_VALIDATION_OK = '<i class="clickable podlove-icon-ok"></i>';
	const FEED_VALIDATION_INACTIVE = '<i class="podlove-icon-minus"></i>';
	const FEED_VALIDATION_ERROR = '<i class="clickable podlove-icon-remove"></i>';

	/**
	 * Get Feed Information (Size (gzip) and Last Modifcation)
	 */

	public static function getInformation( $feedid, $redirected=FALSE ) {
		$feed = \Podlove\Model\Feed::find_by_id( $feedid );

		$source = self::getSource( $feedid, $redirected );
		$feed_header = $source['headers'];
		$feed_body = $source['body'];
		$feed_items = $feed->post_ids();

		$last_modification = \Podlove\relative_time_steps(strtotime( isset($feed_header['last-modified']) ? $feed_header['last-modified'] : 0 ));
		$size = \Podlove\format_bytes(strlen( $feed_body ));

		if ( extension_loaded('zlib') ) {
			$size .= " (" .  \Podlove\format_bytes(strlen( gzdeflate( $feed_body , 9 ) )) . ")";
		}

		if ( $redirected === FALSE ) {
			$latest_item = "<a href=\"" . get_permalink( $feed_items[0] ) . "\">". get_the_title( $feed_items[0] ) ."</a>";
		} else {
			// Fetch latest item from source of redirected feed
			$start_item_tag  = strpos($feed_body, '<item');
			$end_item_tag	 = strpos($feed_body, '</item>');

			$first_item = substr($feed_body, $start_item_tag + 7, $end_item_tag - $start_item_tag);

			$start_title_tag = strpos($first_item, '<title');
			$end_title_tag = strpos($first_item, '</title>');

			$start_link_tag = strpos($first_item, '<link');
			$end_link_tag = strpos($first_item, '</link>');

			$title = substr($first_item, $start_title_tag + 7, $end_title_tag - $start_title_tag - 7 );
			$permalink = substr($first_item, $start_link_tag + 6, $end_link_tag - $start_link_tag - 7);

			$latest_item = "<a href=\"" . $permalink . "\">". $title ."</a>";
		}
		
		return array(
						'size'				=>	$size,
						'last_modification'	=>	$last_modification,
						'latest_item'		=>  $latest_item
					);
	}

	public static function getResolvedUrl($feedid, $redirected)
	{
		$feed = \Podlove\Model\Feed::find_by_id( $feedid );

		if ($redirected === FALSE) {
			$subscribe_url = $feed->get_subscribe_url();
			return $subscribe_url . ((strpos($subscribe_url, '?') === FALSE) ? '?' : '&') . "redirect=no";
		} else {
			return $feed->redirect_url;	
		}
	}

	/**
	 * Fetch feed source
	 */
	public static function getSource( $feedid, $redirected=FALSE )
	{
		$curl = new \Podlove\Http\Curl();
		$curl->request( self::getResolvedUrl($feedid, $redirected), array(
			'headers' => array( 'Content-type'  => 'application/json' ),
			'timeout' => 10,
			'compress' => true,
			'decompress' => false,
			'sslcertificates' => '',
			'_redirection' => ''
		) );

		$response = $curl->get_response();

		if( is_wp_error( $response ) )
			return FALSE; // Return FALSE if Error occured

		return $response;
	}

	/**
	 * Feed Validation via w3c validator API (http://validator.w3.org/feed/)
	 */
	public static function getValidation( $feedid, $redirected=FALSE )
	{
		$curl = new \Podlove\Http\Curl();
		$curl->request( "http://validator.w3.org/feed/check.cgi?output=soap12&url=" . self::getResolvedUrl($feedid, $redirected), array(
			'headers' => array( 'Content-type'  => 'application/soap+xml' ),
			'timeout' => 20,
			'compress' => true,
			'decompress' => false,
			'sslcertificates' => '',
			'_redirection' => ''
		) );
		$response = $curl->get_response();

		if( is_wp_error( $response ) )
			return FALSE; // Return FALSE if Error occured

		if( strpos( $response['body'], 'faultcode' ) )
			return FALSE; // Returning FALSE if feed is not recheable

		$xml = simplexml_load_string( $response['body'] ); 

		$namespaces = $xml->getNamespaces( true );
		$soap = $xml->children( $namespaces['env'] ); // Strip SOAP environment

		return $soap->Body->children( $namespaces['m'] )->children( $namespaces['m'] ); // Return errors and warnings
	}

	public static function getValidationErrorsandWarnings( $feedid, $redirected=FALSE )
	{
		$feed = \Podlove\Model\Feed::find_by_id( $feedid );

		$warning_and_error_list = self::getValidation( $feedid, $redirected );

		if( !$warning_and_error_list ) 
			return FALSE;

		$warning_list = array();
		$error_list = array();

		// Getting Warnings
		foreach ( $warning_and_error_list->warnings->warninglist->children()  as $warning_key => $warning  ) {
			$warning_list[] = get_object_vars( $warning ); // Converting object to array here to have a consistent data structure
		}

		foreach ( $warning_and_error_list->errors->errorlist->children()  as $error_key => $error  ) {
			$error_list[] = get_object_vars( $error ); // Converting object to array here to have a consistent data structure
		}

		return array(
						'validity'				=> $warning_and_error_list->validity->__toString(),
						'number_of_errors' 		=> $warning_and_error_list->errors->errorcount->__toString(),
						'number_of_warnings'	=> $warning_and_error_list->warnings->warningcount->__toString(),
						'errors'				=> $error_list,
						'warnings'				=> $warning_list
					);
	}

	public static function getValidationIcon( $feedid, $redirected=FALSE )
	{
		$feed = \Podlove\Model\Feed::find_by_id( $feedid );

		$errors_and_warnings = self::getValidationErrorsandWarnings( $feedid, $redirected );
		$feed_subscribe_url = ( $redirected === FALSE ? $feed->get_subscribe_url() : $feed->redirect_url );

		if( $redirected === TRUE )
			$redirected = ' (Redirected)';

		\Podlove\Log::get()->addInfo( 'Validate feed <a href="' . $feed_subscribe_url . '">' . $feed->name . $redirected . '</a>.' );

		if( !$errors_and_warnings ) {
			\Podlove\Log::get()->addInfo( 'Feed <a href="' . $feed_subscribe_url . '">' . $feed->name . '</a> is not accessible for validation.' );
			return self::FEED_VALIDATION_INACTIVE;
		}

		// Log Warnings and errors
		self::logValidation( $feedid, $errors_and_warnings, $redirected );

		return ( $errors_and_warnings['validity'] == 'true' ? self::FEED_VALIDATION_OK : self::FEED_VALIDATION_ERROR );
	}

	public static function logValidation( $feedid, $errors_and_warnings, $redirected=FALSE )
	{
		$feed = \Podlove\Model\Feed::find_by_id( $feedid );

		$feed_subscribe_url = ( $redirected === FALSE ? $feed->get_subscribe_url() : $feed->redirect_url );

		if( $redirected === TRUE )
			$redirected = ' (Redirected)';

		foreach ( $errors_and_warnings['warnings'] as $warning_key => $warning ) {
			\Podlove\Log::get()->addInfo( 'Warning: ' . $warning['text'] . ', line ' . $warning['line'] . ' in Feed <a href="' . $feed_subscribe_url . '">' . $feed->name . $redirected .'</a>.'   );	
		}

		foreach ( $errors_and_warnings['errors'] as $error_key => $error ) {
			\Podlove\Log::get()->addError( 'Error: ' . $error['text'] . ', line ' . $error['line'] . ' in Feed <a href="' . $feed_subscribe_url . '">' . $feed->name .  $redirected .'</a>.'   );	
		}
	}

}
?>