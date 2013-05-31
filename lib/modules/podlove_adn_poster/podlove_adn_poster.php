<?php 
namespace Podlove\Modules\PodloveADNPoster;
use \Podlove\Model;

class Podlove_adn_poster extends \Podlove\Modules\Base {

    protected $module_name = 'Podlove ADN Poster';
    protected $module_description = 'Broadcasts new podcast episodes on App.net';
	
	
    public function load() {
    
    	//add_action( 'wp', array( $this, 'bla' ) );
    	
    	if($this->get_module_option('adn_poster_auth') !== "") {
			add_action('new_to_publish', array( $this, 'post_to_adn' ));
			add_action('draft_to_publish', array( $this, 'post_to_adn' ));
			add_action('auto-draft_to_publish', array( $this, 'post_to_adn' ));
			add_action('pending_to_publish', array( $this, 'post_to_adn' ));
			add_action('private_to_publish', array( $this, 'post_to_adn' ));
			add_action('future_to_publish', array( $this, 'post_to_adn' ));
		}
        
        // Register Key

			
			if($this->get_module_option('adn_poster_auth') == "") {
				$this->register_option( 'adn_poster_auth', 'string', array(
					'label'       => __( 'Auth Key', 'podlove' ),
					'description' => __( '<strong style="color: red;">You need to authorize Podlove to use your App.net account.</strong> To do so please start the authorization process <a target="_blank" href="http://www2.synthx.de/adnauth.php?step=1">here</a>.', 'podlove' ),
					'html'        => array( 'class' => 'regular-text' )
				) );	
			} else {
				$this->register_option( 'adn_poster_auth', 'string', array(
					'label'       => __( 'Auth Key', 'podlove' ),
					'description' => __( 'Your App.net auth key', 'podlove' ),
					'html'        => array( 'class' => 'regular-text' )
				) );			
			}

			if($this->get_module_option('adn_poster_announcement_text') == "") {			
				$this->register_option( 'adn_poster_announcement_text', 'text', array(
					'label'       => __( 'Broadcast text', 'podlove' ),
					'description' => __( '<strong style="color: red;">You need to set a text, Podlove uses to broadcast new episodes.</strong><p>Feel free to to use this keys, in order to customize your post:</p><ul><li><code>%p</code> The title of your podcast</li><li><code>%t</code> The title of the episode</li><li><code>%s</code> The subtitle of the episode</li><li><code>%u</code> The permalink of the current episode</li></ul><p>E.g. <em>Hey guys! Check out the new episode from %p with the name %t right here: %u</em></p>', 'podlove' ),
					'html'        => array( 'cols' => '40', 'rows' => '6' )
				) );
			} else {
				$this->register_option( 'adn_poster_announcement_text', 'text', array(
					'label'       => __( 'Broadcast text', 'podlove' ),
					'description' => __( 'The text that will be displayed on App.net. <p>Feel free to to use this keys, in order to customize your post:</p><ul><li><code>%p</code> The title of your podcast</li><li><code>%t</code> The title of the episode</li><li><code>%s</code> The subtitle of the episode</li><li><code>%u</code> The permalink of the current episode</li></ul><p>E.g. <em>Hey guys! Check out the new episode from %p with the name %t right here: %u</em></p>', 'podlove' ),
					'html'        => array( 'cols' => '40', 'rows' => '6' )
				) );		
			}

    }
    
	public function bla() {
		//$this->form_annotations("4");
	}    
    
    public function form_annotations($get_id) {
    	
    	global $wpdb;
    	$filetypes_db = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."podlove_filetype`" );
    	$feeds = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."podlove_feed`" );
    
    	$episode = \Podlove\Model\Episode::find_one_by_post_id($get_id);  	
    	$podcast = \Podlove\Model\Podcast::get_instance();
    	$episode_assets = \Podlove\Model\EpisodeAsset::all(); //find_one_by_id($episode->id)
    	
    	// Genutze Filetypes abfragen
    	foreach($episode_assets as $key => $value) {
    		$filetypes[] = $value->file_type_id;
    	}
    	
    	// Array, welcher vorhandene Dateien sammelt
    	$episode_files = array();
    	
    	foreach($filetypes as $type) {
    		foreach($filetypes_db as $key => $value) {
    			if($value->id !== $type) {
    				if(file_exists($_SERVER["DOCUMENT_ROOT"]."/podlove/wp-content/audio/".$episode->slug.".".$value->extension)) {
    					if(!in_array($_SERVER["DOCUMENT_ROOT"]."/podlove/wp-content/audio/".$episode->slug.".".$value->extension, $episode_files)) {
    						$episode_files[] = array(	"file-title" => $value->name, 
    													"file-url" => $podcast->media_file_base_uri.$episode->slug.".".$value->extension,
    													"file-mime-type" => $value->mime_type);
    						
    					}	
    				}
    			}
    		}
    	}
    	
    	$episode_entry = array(
    	
    			"title" => get_the_title("4"),
    			"subtitle" => $episode->subtitle,
    			"summary" => $episode->summary,
    			"homepage" => $podcast->publisher_url,
    			"media-files" => $episode_files
    	
    	);
    	
    	$feed_urls = array();
    	
    	foreach ($feeds as $key => $value) {
    		$asset = \Podlove\Model\EpisodeAsset::find_one_by_id($value->episode_asset_id);
    		foreach($filetypes_db as $keye => $valuee) {
    			if($valuee->id == $asset->file_type_id) {
    				$feed_mime_type = $valuee->mime_type;
    			}
    		}
    		$feeds_urls[] = array(
    								"feed-title" => $value->name,
    								"feed-url" => get_bloginfo("wpurl")."/".$value->slug."/",
    								"file-mime-type" => $feed_mime_type);    			
    									
    		unset($feed_mime_type);
    	}
    	


    	$podcast_entry = array(
    	
    			"title" => $podcast->title,
    			"subtitle" => $podcast->subtitle,
    			"summary" => $podcast->summary,
    			"link" => $podcast->publisher_url,
    			"feeds" => $feeds_urls
    	
    	);
    	
    	$annotation = array(array("type" => "org.podlove.podcast-description", "value" => $podcast_entry), array("type" => "org.podlove.episode-description", "value" => $episode_entry));
    	
    	return $annotation;
    	
    }   
    
    public function post_to_adn() {
    
    	$episode = \Podlove\Model\Episode::find_one_by_post_id($_POST['post_ID']);
    	$podcast = \Podlove\Model\Podcast::get_instance();
    	$annotations = $this->form_annotations($_POST['post_ID']);
    	
    	$posted_text = $this->get_module_option('adn_poster_announcement_text');
    	$posted_text = str_replace("%p", $podcast->title, $posted_text);
    	$posted_text = str_replace("%t", get_the_title($_POST['post_ID']), $posted_text);
    	$posted_text = str_replace("%u", get_permalink($_POST['post_ID']), $posted_text);
    	$posted_text = str_replace("%s", $episode->subtitle, $posted_text);
    
    	$data = array("text" => $posted_text, "annotations" => $annotations);                                                  
		$data_string = json_encode($data);                                                                                   

		$ch = curl_init('https://alpha-api.app.net/stream/0/posts?access_token='.$this->get_module_option('adn_poster_auth').'');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string))                                                                       
		);                                                                                                                   

		$result = curl_exec($ch);
		
		//print_r(json_encode($data));

    }
    
    
}