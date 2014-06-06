<?php
namespace Podlove\Modules\AppDotNet;

use \Podlove\Http;

class API {

	private $module;
	
	public function __construct(\Podlove\Modules\AppDotNet\App_Dot_Net $module)
	{
		$this->module = $module;
	}

    public function fetch_authorized_user()
    {
    	$cache_key = 'podlove_adn_user';

    	if ( ( $user = get_transient( $cache_key ) ) !== false ) {
    		return $user;
    	} else {
	    	if ( ! ( $token = $this->module->get_module_option('adn_auth_key') ) )
	    		return false;

	    	$curl = new Http\Curl();
	    	$curl->request(
	    		'https://alpha-api.app.net/stream/0/token?access_token=' . $token,
	    		array( 'timeout' => 10 )
	    	);
	    	$response = $curl->get_response();

	    	if ($curl->isSuccessful()) {
		    	$decoded_result = json_decode( $response['body'] );
		    	$user = $decoded_result ? $decoded_result->data->user : false;
		    	set_transient( $cache_key, $user, 60*60*24*365 ); // 1 year, we devalidate manually
		    	return $user;
	    	}
    	}

    	return false;
    }

    public function fetch_patter_rooms()
    {
    	$cache_key = 'podlove_adn_rooms';

    	if ( ( $patter_rooms = get_transient( $cache_key ) ) !== FALSE ) {
    		return $patter_rooms;
    	} else {
    		$url = 'https://alpha-api.app.net/stream/0/channels?include_annotations=1&access_token=' . $this->module->get_module_option('adn_auth_key');

    		$curl = new Http\Curl();
    		$curl->request( $url, array(
    			'headers' => array( 'Content-type'  => 'application/json' )
    		) );
    		$response = $curl->get_response();

    		if (!$curl->isSuccessful())
    			return array();
    		
    		$patter_rooms = array();
    		
    		foreach ( json_decode($response['body']) as $channel ) {
    			foreach ( $channel as $channel_details ) {
    				
    				if ( ! $this->channel_has_annotations( $channel_details ) )
    					continue;

    				foreach ( $channel_details->annotations as $annotation_id => $annotation_values ) {
    					if ( $annotation_values->type == "net.patter-app.settings" )
    						$patter_rooms[$channel_details->id] = $annotation_values->value->name;
    				}
    			}
    		}

    		set_transient( $cache_key, $patter_rooms, 60*60*24*365 ); // 1 year, we devalidate manually
    		return $patter_rooms;
    	}
    }

    public function fetch_broadcast_channels() {
    	$cache_key = 'podlove_adn_broadcast_channels';

    	if ( ( $broadcast_channels = get_transient( $cache_key ) ) !== FALSE ) {
    		return $broadcast_channels;
    	} else {
    		$url = 'https://alpha-api.app.net/stream/0/channels?include_annotations=1&access_token=' . $this->module->get_module_option('adn_auth_key');

    		$curl = new Http\Curl();
    		$curl->request( $url, array(
    			'headers' => array( 'Content-type'  => 'application/json' )
    		) );
    		$response = $curl->get_response();

    		if (!$curl->isSuccessful())
    			return array();
    		
    		$broadcast_channels = array();
    		
    		foreach ( json_decode($response['body'])->data as $channel ) {

    			if ( $channel->type == "net.app.core.broadcast" && $channel->you_can_edit == 1 ) {
    				$title = '';
    				foreach ($channel->annotations as $annotation) {
    					if( $annotation->type == "net.app.core.broadcast.metadata" )
    						$title = $annotation->value->title;
    				}

    				$broadcast_channels[$channel->id] = $title;
    			}	
    		}

    		set_transient( $cache_key, $broadcast_channels, 60*60*24*365 ); // 1 year, we devalidate manually
    		return $broadcast_channels;
    	}
    }

    /**
     * POST $data to the given $url
     * 
     * @param  string $url  ADN API URL
     * @param  array  $data
     */
	public function post($url, $data) {
		
		$data_string = json_encode($data);

		$curl = new Http\Curl();
		$curl->request( $url, array(
			'method' => 'POST',
			'timeout' => '5000',
			'body' => $data_string,
			'headers' => array(
				'Content-type'   => 'application/json',
				'Content-Length' => \Podlove\strlen($data_string)
			)
		) );
		
		$response = $curl->get_response();
		$body = json_decode( $response['body'] );

		if ( $body->meta->code !== 200 )
			\Podlove\Log::get()->addWarning( sprintf( 'Error: App.net Module failed to Post: %s (Code %s)', str_replace( "'", "''", $body->meta->error_message ), $body->meta->code ) );
	}

	private function channel_has_annotations($details) {
		return isset($details->annotations) && count($details->annotations) !== 0;
	}
}