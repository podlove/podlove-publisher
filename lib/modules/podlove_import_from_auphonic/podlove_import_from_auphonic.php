<?php 
namespace Podlove\Modules\PodloveImportFromAuphonic;
use \Podlove\Model;

class Podlove_import_from_auphonic extends \Podlove\Modules\Base {

    protected $module_name = 'Podlove Import From Auphonic';
    protected $module_description = 'Import Episode data from an Auphonic production';
	
    public function load() {
    		
    		if($this->get_module_option('auphonic_api_key') == "") { } else {
    			add_action( 'podlove_episode_form_beginning', array( $this, 'auphonic_episodes' ), 10, 2 );
    		}
    		
			if( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'check_code'));
    		}    		
    		
    		if( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
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
    
    public function auphonic_episodes( $wrapper, $episode ) {
    	$wrapper->callback( 'import_from_auphonic_form', array(
			'label'    => __( 'Import Episode data from Auphonic', 'podlove' ),
			'callback' => array( $this, 'auphonic_episodes_form' )
		) );			
    }

    public function auphonic_episodes_form() {
		$ch = curl_init('https://auphonic.com/api/productions.json?limit=10');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
		curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
			'Content-type: application/json',                                     
			'Authorization: Bearer '.$this->get_module_option('auphonic_api_key'))                                                                       
		);                                                              
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        

		$result = curl_exec($ch);

		$asset_assignments = Model\AssetAssignment::get_instance();

		?>
		<script type='text/javascript'>
		function fetch_production_data(token) {
			var uuid = jQuery("#import_from_auphonic option:selected").val(),
			    module_url = "<?php echo $this->get_module_url(); ?>",
			    chapter_asset_assignment = "<?php echo $asset_assignments->chapters ?>";

			jQuery.getJSON(module_url + '/fetch_episode.php?uuid=' + uuid + '&access_token=' + token, function(data) {

				// hide prompt label which usually is placed above the title field
				jQuery('#title-prompt-text').addClass('screen-reader-text');

				if (document.getElementById('force_import_from_auphonic').checked) {
					jQuery('#title').val(data.data.metadata.title);
					jQuery('#_podlove_meta_subtitle').val(data.data.metadata.subtitle);
					jQuery('#_podlove_meta_summary').val(data.data.metadata.summary);
					jQuery('#_podlove_meta_duration').val(data.data.length_timestring);
					jQuery('#_podlove_meta_slug').val(data.data.output_basename);
					jQuery('#new-tag-post_tag').val(data.data.metadata.tags.join(", "));

					if (chapter_asset_assignment == 'manual') {
						var chapters_entry = "";
						jQuery.each(data.data.chapters, function(index, value) {
							chapters_entry = chapters_entry + value.start + " " + value.title;
							if (value.url == "") {
						
							} else {
								chapters_entry = chapters_entry + " <" + value.url + ">";
							}
							chapters_entry = chapters_entry + '\n';
						});							
						jQuery('#_podlove_meta_chapters').val(chapters_entry);	
					}

				} else {
					if (jQuery("#title").val() == "") {
						jQuery('#title').val(data.data.metadata.title);
					}
			
					if (jQuery("#_podlove_meta_subtitle").val() == "") {
						jQuery('#_podlove_meta_subtitle').val(data.data.metadata.subtitle);
					}
			
					if (jQuery("#_podlove_meta_summary").val() == "") {
						jQuery('#_podlove_meta_summary').val(data.data.metadata.summary);
					}
			
					if (jQuery("#_podlove_meta_duration").val() == "") {
						jQuery('#_podlove_meta_duration').val(data.data.length_timestring);
					}
			
					if (jQuery("#_podlove_meta_slug").val() == "") {
						jQuery('#_podlove_meta_slug').val(data.data.output_basename);
					}
			
					if (jQuery("#new-tag-post_tag").val() == "") {
						jQuery('#new-tag-post_tag').val(data.data.metadata.tags.join(", "));
					}

					if (chapter_asset_assignment == 'manual') {
						if (jQuery("#_podlove_meta_chapters").val() == "") {
							var chapters_entry = "";
						
							jQuery.each(data.data.chapters, function(index, value) {
								chapters_entry = chapters_entry + value.start + " " + value.title;
								if (value.url == "") {
						
								} else {
									chapters_entry = chapters_entry + " <" + value.url + ">";
								}
								chapters_entry = chapters_entry + '\n';
							});
						
							jQuery('#_podlove_meta_chapters').val(chapters_entry);
						}
					}
				}
			});
		}
		</script>
		<?php												
						
		echo "<select name=\"import_from_auphonic\" id=\"import_from_auphonic\">\n";
		foreach(json_decode($result)->data as $production_key => $production_data) {
			if($production_data->output_basename == "") {
				$displayed_name = $production_data->metadata->title;
			} else {
				$displayed_name = $production_data->output_basename;
			}
			echo "	<option value=\"".$production_data->uuid."\">".$displayed_name." (".date( "Y-m-d H:i:s", strtotime($production_data->creation_time)).")</option>\n";
			$displayed_name = "";
		}

		echo "</select>\n<input type='button' class='button' style='width: 150px;' onclick='fetch_production_data(\"".$this->get_module_option('auphonic_api_key')."\")' value=\"Import from Auphonic\"/>
					<input type='checkbox' style='width: 20px;' id='force_import_from_auphonic'/><label for='force_import_from_auphonic'>Force import (overwrites all fields, even if filled out)</label>
					<p><span class='description'>";
					
					if ( $asset_assignments->chapters == 'manual' ) {
						echo "Title, subtitle, summary, tags, duration, mediafile slug and Chapters";
					} else {
						echo "Title, subtitle, summary, tags, duration and mediafile slug";
					}
					
		echo "		will be imported from Auphonic.</span></p>\n";	
    }
    
    public function check_code() { 
    	if( isset( $_GET["code"] ) && $_GET["code"] ) {
    		if($this->get_module_option('auphonic_api_key') == "") {
				$ch = curl_init('http://auth.podlove.org/auphonic.php');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
				curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(  
					   "redirect_uri" => get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle',                                                                      
					   "code" => $_GET["code"]));                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
			
				$result = curl_exec($ch);
						
				$this->update_module_option('auphonic_api_key', $result);
				header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
			}
    	}
    	
    	if ( isset( $_GET["reset_auphonic_auth_code"] ) && $_GET["reset_auphonic_auth_code"] == "1" ) {
    		$this->update_module_option('auphonic_api_key', "");
    		header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
    	}
    	
    }
    
}