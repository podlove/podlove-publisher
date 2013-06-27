<?php 
namespace Podlove\Modules\AppDotNet;
use \Podlove\Model;

class App_Dot_Net extends \Podlove\Modules\Base {

    protected $module_name = 'App.net';
    protected $module_description = 'Broadcasts new podcast episodes on App.net';
    protected $module_group = 'external services';
	
    public function load() {
    	
    		if($this->get_module_option('adn_poster_auth') !== "") {
				add_action('publish_podcast', array( $this, 'post_to_adn' ));
			}
        
			if($this->get_module_option('adn_poster_auth') == "") {
				$description = __( '<strong style="color: red;">You need to authorize Podlove to use your App.net account.</strong> To do so please start the authorization process <a target="_blank" href="http://auth.podlove.org/adn.php?step=1">here</a>.', 'podlove' );
			} else {
				$description = __( 'Your App.net auth key', 'podlove' );
			}
			$this->register_option( 'adn_poster_auth', 'string', array(
				'label'       => __( 'Auth Key', 'podlove' ),
				'description' => $description,
				'html'        => array( 'class' => 'regular-text' )
			) );			

			if($this->get_module_option('adn_poster_announcement_text') == "") {			
				$description = '<strong style="color: red;">' . __( 'You need to set a text to broadcast new episodes.', 'podlove' ) . '</strong>';
			} else {
				$description = __( 'The text that will be displayed on App.net.', 'podlove' );
			}
			$description .= __( '
				<p>
					Use these placeholders to customize your post:
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
				'label'       => __( 'Broadcast text', 'podlove' ),
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
    }
    
    
}