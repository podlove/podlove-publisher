<?php
		header('Content-type: application/json');

		$callurl = 'https://auphonic.com/api/production/'.$_GET['uuid'].'.json?bearer_token='.$_GET["access_token"];

		$ch = curl_init($callurl);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
			'Content-type: application/json')                                                                      
		);                                                              
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
	
		$result = curl_exec($ch);
		
		if ( curl_errno( $ch ) ) {
			header("HTTP/1.1 503 Service Temporarily Unavailable");
			header("Status: 503 Service Temporarily Unavailable");
		} else {
			print_r( $result );
		}
