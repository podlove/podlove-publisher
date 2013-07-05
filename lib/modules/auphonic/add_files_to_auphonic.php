<?php
		header('Content-type: application/json');
		
		$callurl = 'https://auphonic.com/api/production/'.$_GET["uuid"].'/upload.json?bearer_token='.$_GET["access_token"];

		$ch = curl_init($callurl);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');  
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			"image" => "@/Users/alex/Sites/podlove/wp-content/plugins/podlove-publisher/images/podlove/icon-adminpage32.png"));                                                            
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
			'Content-type: application/json')                                                                      
		);                                                              
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
	
		$result = curl_exec($ch);
		
		print_r($result);
	
?>