<?php 
namespace Podlove\Modules\AppDotNet;
use \Podlove\Model;

class App_Dot_Net extends \Podlove\Modules\Base {

    protected $module_name = 'App.net';
    protected $module_description = 'Announces new podcast episodes on App.net';
    protected $module_group = 'external services';
	
    public function load() {
    	
    		if($this->get_module_option('adn_auth_key') !== "") {
				add_action('publish_podcast', array( $this, 'post_to_adn' ));
			}
			
			if( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'reset_adn_auth'));
    		}   
        
			if($this->get_module_option('adn_auth_key') == "") {
				$description = '<i class="podlove-icon-remove"></i> '
	    		             . __( 'You need to allow Podlove Publisher to access your App.net account. To do so please start the authorization process, follow the instructions and paste the obtained code in the field above.', 'podlove' )
	    		             . '<br><a href="http://auth.podlove.org/adn.php?step=1" class="button button-primary" target="_blank">' . __( 'Start authorization process now', 'podlove' ) . '</a>';
				$this->register_option( 'adn_auth_key', 'string', array(
				'label'       => __( 'Authorization', 'podlove' ),
				'description' => $description,
				'html'        => array( 'class' => 'regular-text', 'placeholder' => 'App.net auth code' )
				) );
			} else {
				$ch = curl_init('https://alpha-api.app.net/stream/0/token?access_token='.$this->get_module_option('adn_auth_key').'');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                                                                                  
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                                                                                                                                  

				$decoded_result = json_decode(curl_exec($ch)); 
				
				if(isset($decoded_result) AND $decoded_result !== "") { 
					$description = '<i class="podlove-icon-ok"></i> '
								 . sprintf(
									__( 'You are logged in as <strong>'.$decoded_result->data->user->username.'</strong>. If you want to logout, click %shere%s.', 'podlove' ),
									'<a href="' . admin_url( 'admin.php?page=podlove_settings_modules_handle&reset_appnet_auth_code=1' ) . '">',
									'</a>'
								);
				} else {
					$description = '<i class="podlove-icon-remove"></i> '
								 . sprintf(
									__( 'Something went wrong with the App.net connection. Please start the authorization process again. To do so click %shere%s', 'podlove' ),
									'<a href="' . admin_url( 'admin.php?page=podlove_settings_modules_handle&reset_appnet_auth_code=1' ) . '">',
									'</a>'
								);
		
				}
				$this->register_option( 'adn_auth_key', 'hidden', array(
				'label'       => __( 'Authorization', 'podlove' ),
				'description' => $description,
				'html'        => array( 'class' => 'regular-text' )
				) );		
			}
			
						

			if($this->get_module_option('adn_poster_announcement_text') == "") {			
				$description = '<i class="podlove-icon-remove"></i>' . __( 'You need to set a text to announce new episodes.', 'podlove' );
			} else {
				$description = __( 'The text that will be displayed on App.net.', 'podlove' );
			}
			$description .= __( '
				<p>
					Use these placeholders to customize your announcement:
				</p>
				<ul>
					<li>
						<code>{podcastTitle}</code> The title of your podcast
					</li>
					<li>
						<code>{linkedEpisodeTitle}</code> The title of your episode, linking to it
					</li>
					<li>
						<code>{episodeTitle}</code> The title of the episode
					</li>
					<li>
						<code>{episodeLink}</code> The permalink of the current episode
					</li>
					</li>
					<li>
						<code>{episodeSubtitle}</code> The subtitle of the episode
					</li>
				</ul>', 'podlove' );		

			$this->register_option( 'adn_poster_announcement_text', 'text', array(
				'label'       => __( 'Announcement text', 'podlove' ),
				'description' => $description,
				'html'        => array( 'cols' => '40', 'rows' => '6', 'placeholder' => 'Check out the new {podcastTitle} episode: {linkedEpisodeTitle}' )
			) );

    }
    
    public function post_to_adn() {
    
    	$episode = \Podlove\Model\Episode::find_one_by_post_id($_POST['post_ID']);
    	$podcast = \Podlove\Model\Podcast::get_instance();
    	$posted_text = $this->get_module_option('adn_poster_announcement_text');
    	
    	$posted_text = str_replace("{podcastTitle}", $podcast->title, $posted_text);
    	$posted_text = str_replace("{episodeTitle}", get_the_title($_POST['post_ID']), $posted_text);
    	$posted_text = str_replace("{episodeLink}", get_permalink($_POST['post_ID']), $posted_text);
    	$posted_text = str_replace("{episodeSubtitle}", $episode->subtitle, $posted_text);
    	
    	$posted_linked_title = array();
    	$start_position = 0;
    	
    	while(($position = mb_strpos( $posted_text, "{linkedEpisodeTitle}", $start_position, "UTF-8" )) !== FALSE) {
        	$episode_entry = array(
        		"url"  => get_permalink($_POST['post_ID']), 
        		"text" => get_the_title($_POST['post_ID']), 
        		"pos"  => $position, 
        		"len"  => mb_strlen( get_the_title($_POST['post_ID']), "UTF-8" )
        	);
        	array_push( $posted_linked_title, $episode_entry );
        	$start_position = $position + 1;
		}
    	
    	$posted_text = str_replace("{linkedEpisodeTitle}", get_the_title($_POST['post_ID']), $posted_text);
    
    	$data = array("text" => $posted_text, "entities" => array("links" => $posted_linked_title,"parse_links" => true));                                                  
		$data_string = json_encode($data);        
		
		$ch = curl_init('https://alpha-api.app.net/stream/0/posts?access_token='.$this->get_module_option('adn_auth_key').'');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string))                                                                       
		);                                                                                                                   

		$result = curl_exec($ch);
    }
    
    public function reset_adn_auth() {
    	if(isset( $_GET["reset_appnet_auth_code"] ) && $_GET["reset_appnet_auth_code"] == "1") {
				$this->update_module_option('adn_auth_key', "");
    			header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');    
    	}
    }
    
    
}