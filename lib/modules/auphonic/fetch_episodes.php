<?php
		header('Content-type: application/json');
		
		$callurl = 'https://auphonic.com/api/productions.json?limit=10&bearer_token='.$_REQUEST["access_token"];

		$ch = curl_init($callurl);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');  
		if ( isset( $_REQUEST["data"] ) ) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($_REQUEST["data"]));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
			'Content-type: application/json')                                                                      
		);                                                              
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
	
		$result = curl_exec($ch);
		
		print_r($result);
	
?>