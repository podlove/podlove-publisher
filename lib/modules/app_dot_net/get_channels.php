<?php

		$ch = curl_init('https://alpha-api.app.net/stream/0/channels?include_annotations=1&access_token='.$_POST["token"]);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string))                                                                       
		);                                                                                                                   

		$result = curl_exec($ch);
		
		print_r($result);

?>