<?php 
namespace Podlove\Modules\PodloveImportFromAuphonic;
use \Podlove\Model;

class Podlove_import_from_auphonic extends \Podlove\Modules\Base {

    protected $module_name = 'Podlove Auphonic Importer';
    protected $module_description = 'Import Episode data from an Auphonic production';
	
    public function load() {
    		
    		if($this->get_module_option('auphonic_api_key') == "") { } else {
    			add_action( 'podlove_episode_form', array( $this, 'auphonic_episodes' ), 10, 3 );
    		}
    		
			if($_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'check_code'));
    		}    		
    		
    		if($_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'check_code'));
    		}  
    		
    		if($this->get_module_option('auphonic_api_key') == "") {
    			$this->register_option( 'auphonic_api_key', 'hidden', array(
					'label'       => __( 'Podlove can access your Auphonic account?', 'podlove' ),
					'description' => __( '<span style="font-weight: bold; color: red !important;">!!!</span> You need to allow Podlove to access your Auphonic account. This can be done <a href="https://auphonic.com/oauth2/authorize/?client_id=0e7fac528c570c2f2b85c07ca854d9&redirect_uri='.urlencode(get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle').'&response_type=code" target="_self">here</a> (you will be redirected to this page, once the auth process completed).', 'podlove' ),
					'html'        => array( 'class' => 'regular-text' )
				) );
    		} else {
    			$this->register_option( 'auphonic_api_key', 'hidden', array(
					'label'       => __( 'Podlove can access your Auphonic account?', 'podlove' ),
					'description' => __( '<span style="font-weight: bold; color: green !important;">✓</span> If you want to reset your connection with Auphonic click <a href="'.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle&reset_auphonic_auth_code=1'.'">here</a>.', 'podlove' ),
					'html'        => array( 'class' => 'regular-text' )
				) );
    		}

    }
    
    public function auphonic_episodes() {
    	echo "<div><span><label>Import Episode data from Auphonic</label></span></div>";
    			$ch = curl_init('https://auphonic.com/api/productions.json?limit=10');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
				curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
					'Content-type: application/json',                                     
					'Authorization: Bearer '.$this->get_module_option('auphonic_api_key'))                                                                       
				);                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
			
				$result = curl_exec($ch);
				
				$asset_assignments = Model\AssetAssignment::get_instance();
				
				echo "<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js'></script><script>
					
					function fetch_production_data(token) {
						var uuid = $(\"#import_from_auphonic option:selected\").val();
						
						$.getJSON('".get_site_url()."/wp-content/plugins/podlove-publisher/lib/modules/podlove_import_from_auphonic/fetch_episode.php?uuid=' + uuid + '&access_token=' + token, function(data) {
						
						if (document.getElementById('force_import_from_auphonic').checked){

							document.getElementById('title').value = data.data.metadata.title;

							document.getElementById('_podlove_meta_subtitle').value = data.data.metadata.subtitle;

							document.getElementById('_podlove_meta_summary').value = data.data.metadata.summary;

							document.getElementById('_podlove_meta_duration').value = data.data.length_timestring;

							document.getElementById('_podlove_meta_slug').value = data.data.output_basename;

							document.getElementById('new-tag-post_tag').value = data.data.metadata.tags.join(\", \");";

							if ( $asset_assignments->chapters == 'manual' ) {
				
									echo "
											var chapters_entry = \"\";
											$.each(data.data.chapters, function(index, value) {
												chapters_entry = chapters_entry + value.start + \" \" + value.title;
												if(value.url == \"\") {
									
												} else {
													chapters_entry = chapters_entry + \" <\" + value.url + \">\";
												}
												chapters_entry = chapters_entry + '\\n';
											});							
											document.getElementById('_podlove_meta_chapters').value = chapters_entry;						
									";
						
							}													
						
							echo "} else {
								if($(\"#title\").val() == \"\") {
									document.getElementById('title').value = data.data.metadata.title;
								}
						
								if($(\"#_podlove_meta_subtitle\").val() == \"\") {
									document.getElementById('_podlove_meta_subtitle').value = data.data.metadata.subtitle;
								}
						
								if($(\"#_podlove_meta_summary\").val() == \"\") {
									document.getElementById('_podlove_meta_summary').value = data.data.metadata.summary;
								}
						
								if($(\"#_podlove_meta_duration\").val() == \"\") {
									document.getElementById('_podlove_meta_duration').value = data.data.length_timestring;
								}
						
								if($(\"#_podlove_meta_slug\").val() == \"\") {
									document.getElementById('_podlove_meta_slug').value = data.data.output_basename;
								}
						
								if($(\"#new-tag-post_tag\").val() == \"\") {
									document.getElementById('new-tag-post_tag').value = data.data.metadata.tags.join(\", \");
								}						
							";
				
							if ( $asset_assignments->chapters == 'manual' ) {
				
									echo "
										if($(\"#_podlove_meta_chapters\").val() == \"\") {
											var chapters_entry = \"\";
							
											$.each(data.data.chapters, function(index, value) {
												chapters_entry = chapters_entry + value.start + \" \" + value.title;
												if(value.url == \"\") {
									
												} else {
													chapters_entry = chapters_entry + \" <\" + value.url + \">\";
												}
												chapters_entry = chapters_entry + '\\n';
											});
							
											document.getElementById('_podlove_meta_chapters').value = chapters_entry;
										}
						
									";
						
							}
								
					echo "}});
						}</script>
					";
				
				echo "<form name='import_from_auphonic_form'><select name=\"import_from_auphonic\" id=\"import_from_auphonic\">\n";
				foreach(json_decode($result)->data as $production_key => $production_data) {
					if($production_data->output_basename == "") {
						$displayed_name = $production_data->metadata->title;
					} else {
						$displayed_name = $production_data->output_basename;
					}
					echo "	<option value=\"".$production_data->uuid."\">".$displayed_name." (".date( "Y-m-d H:i:s", strtotime($production_data->creation_time)).")</option>\n";
					$displayed_name = "";
				}
				
				echo "</select><input type='button' class='button' style='width: 150px;' onclick='fetch_production_data(\"".$this->get_module_option('auphonic_api_key')."\")' value=\"Import from Auphonic\"/>
							<input type='checkbox' style='width: 20px;' id='force_import_from_auphonic'/><label for='force_import_from_auphonic'>Force import (overwrites all fields, even if filled out)</label>
							<p><span class='description'>";
							
							if ( $asset_assignments->chapters == 'manual' ) {
								echo "Title, subtitle, summary, tags, duration, mediafile slug and Chapters";
							} else {
								echo "Title, subtitle, summary, tags, duration and mediafile slug";
							}
							
				echo "		will be imported from Auphonic.</span></p>
							</form>\n";				
    }
    
    public function check_code() { 
    	if($_GET["code"] == "") {
    	
    	} else {
    		if($this->get_module_option('auphonic_api_key') == "") {
				$ch = curl_init('http://auth.podlove.org/auphonic.php');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
				curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(  
					   "redirect_uri" => get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle',                                                                      
					   "code" => $_GET["code"]));                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
			
				$result = curl_exec($ch);
						
				$this->update_module_option('auphonic_api_key', $result);
				header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
			}
    	}
    	
    	if($_GET["reset_auphonic_auth_code"] == "1") {
    		$this->update_module_option('auphonic_api_key', "");
    		header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
    	}
    	
    }
    
}