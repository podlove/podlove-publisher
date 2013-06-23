<?php
		if($_POST["code"] == "") {
			print_r("Error.");
		} else {
			$ch = curl_init('https://auphonic.com/oauth2/token/');                                                                      
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
			curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(                                                                          
				  "client_id" => "0e7fac528c570c2f2b85c07ca854d9",
				  "client_secret" => "e02c400e7b0eab2d1e1d8d9064b5ea",
				  "redirect_uri" => $_POST["redirect_uri"],
				  "grant_type" => "authorization_code",
				  "code" => $_POST["code"]));                                                              
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
			
			$result = curl_exec($ch);
			$parsed_result = json_decode($result);
			
			if(!isset($parsed_result->error) AND $parsed_result->access_token !== "") {
				echo $parsed_result->access_token;
			} else {
			}			
			
		}

?>