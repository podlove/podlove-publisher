<?php 
namespace Podlove\Modules\AppDotNet;
use \Podlove\Model;

class App_Dot_Net extends \Podlove\Modules\Base {

    protected $module_name = 'App.net';
    protected $module_description = 'Announces new podcast episodes on App.net';
    protected $module_group = 'external services';
	
    public function load() {
    
    		$module_url = $this->get_module_url();
    	
    		if($this->get_module_option('adn_auth_key') !== "") {
				add_action('publish_podcast', array( $this, 'post_to_adn_handler' ));
				add_action('delayed_adn_post', array( $this, 'post_to_adn_delayer' ), 10, 2);
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
				'html'        => array( 'class' => 'regular-text', 'placeholder' => 'App.net authentication code' )
				) );
			} else {
				
				if ( $user = $this->fetch_authorized_user() ) { 
					$description = '<i class="podlove-icon-ok"></i> '
								 . sprintf(
									__( 'You are logged in as %s. If you want to logout, click %shere%s.', 'podlove' ),
									'<strong>' . $user->username . '</strong>',
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
				
				$languages = array("ar" => "Arabic",
					"sq" => "Albanian",
					"az" => "Azerbaijani",
					"eu" => "Basque",
					"bg" => "Bulgarian",
					"bn" => "Bengali",
					"bs" => "Bosnian",
					"ca" => "Catalan",
					"zh_CN" => "Chinese, China",
					"zh_TW" => "Chinese, Taiwan",
					"hr" => "Croatian",
					"cs" => "Czech",
					"da" => "Danish",
					"nl" => "Dutch",
					"en" => "English",
					"en_GB" => "English, British",
					"et" => "Estonian",
					"fi" => "Finnish",
					"fr" => "French",
					"gl" => "Galician",
					"ka" => "Georgian",
					"de" => "German",
					"el" => "Greek, Modern",
					"he" => "Hebrew, Modern",
					"hi" => "Hindi",
					"hu" => "Hungarian",
					"is" => "Icelandic",
					"id" => "Indonesian",
					"ga" => "Irish",
					"it" => "Italian",
					"ja" => "Japanese",
					"kn" => "Kannada",
					"kk" => "Kazakh",
					"km" => "Khmer",
					"ko" => "Korean",
					"lv" => "Latvian",
					"lt" => "Lithuanian",
					"mk" => "Macedonian",
					"ml" => "Malayalam",
					"mn" => "Mongolian",
					"nb" => "Norwegian Bokmål",
					"ne" => "Nepali",
					"nn" => "Norwegian Nynorks",
					"no" => "Norwegian",
					"pa" => "Panjabi",
					"fa" => "Persian",
					"pl" => "Polish",
					"pt" => "Portuguese",
					"pt_BR" => "Portuguese, Brazil",
					"ro" => "Romanian",
					"ru" => "Russian",
					"es" => "Spanish",
					"sr" => "Serbian",
					"sr_Latn" => "Serbian, Latin",
					"sk" => "Slovak",
					"sl" => "Slovene",
					"es_AR" => "Spanish, Argentina",
					"es_MX" => "Spanish, Mexico",
					"es_NI" => "Spanish, Nicaragua",
					"sw" => "Swahili",
					"sv" => "Swedish",
					"ta" => "Tamil",
					"tt" => "Tatar",
					"te" => "Telugu",
					"th" => "Thai",
					"tr" => "Turkish",
					"uk" => "Ukrainian",
					"ur" => "Urdu",
					"vi" => "Vietnamese",
					"cy" => "Welsh",
					"fy_NL" => "Western Frisian, Netherlands"
				);
			
				$this->register_option( 'adn_language_annotation', 'select', array(
					'label'       => __( 'Language of Announcement', 'podlove' ),
					'description' => 'Selecting the language of the Announcement, will include an <a href="http://developers.app.net/docs/meta/annotations/" target="_blank">App.net language annotation</a>.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $languages
				) );

				$ch = curl_init('https://alpha-api.app.net/stream/0/channels?include_annotations=1&access_token='.$this->get_module_option('adn_auth_key'));                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
				curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                                                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
					'Content-Type: application/json'                                                                                                                                                  
				));                                                                                                                   

				$result = curl_exec($ch);
			
				$patter_rooms = array();
		
				foreach(json_decode($result) as $channel) {
					foreach($channel as $channel_details) {
						if(isset($channel_details->annotations)) {
							if(count($channel_details->annotations) !== 0) {
								foreach($channel_details->annotations as $annotation_id => $annotation_values) {
									if($annotation_values->type == "net.patter-app.settings") {
										$patter_rooms[$channel_details->id] = $annotation_values->value->name;
									}
								}
							}
						}
					}
				}

				$this->register_option( 'adn_patter_room', 'select', array(
					'label'       => __( 'Patter room', 'podlove' ),
					'description' => 'From the list of subscribed <a href="http://patter-app.net/faq.html" target="_blank">Patter rooms</a>, choose the one related to your Podcast.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $patter_rooms
				) );
			
				$this->register_option( 'adn_patter_room_announcement', 'checkbox', array(
					'label'       => __( 'Announcement in Patter room', 'podlove' ),
					'description' => 'The Announcement text will be posted in the chosen Patter room, too.'
				) );
				
			}
			
			$this->register_option( 'adn_post_delay', 'string', array(
				'label'       => __( 'Post delay', 'podlove' ),
				'description' => 'The new Episode will be announced with a delay of HH:MM:SS.',
				'html'        => array( 'class' => 'regular-text', 'placeholder' => '00:00:00' )
			) );

			$description = '';
			if ( $this->get_module_option('adn_poster_announcement_text') == "" ) {			
				$description = '<i class="podlove-icon-remove"></i>'
				             . __( 'You need to set a text to announce new episodes.', 'podlove' );
			}

			$description .= __( 'App.net allows 256 characters per post. Try to keep the announcement text short. Your episode titles will need more space than the placeholders.', 'podlove' );

			$description .= '
				' . __( 'Use these placeholders to customize your announcement', 'podlove' ) . ':
				<code title="' . __( 'The title of your podcast', 'podlove' ) . '">{podcastTitle}</code>
				<code title="' . __( 'The title of your episode, linking to it', 'podlove' ) . '">{linkedEpisodeTitle}</code>
				<code title="' . __( 'The title of the episode', 'podlove' ) . '">{episodeTitle}</code>
				<code title="' . __( 'The permalink of the current episode', 'podlove' ) . '">{episodeLink}</code>
				<code title="' . __( 'The subtitle of the episode', 'podlove' ) . '">{episodeSubtitle}</code>';		

			$this->register_option( 'adn_poster_announcement_text', 'text', array(
				'label'       => __( 'Announcement text', 'podlove' ),
				'description' => $description,
				'html'        => array(
					'cols' => '50',
					'rows' => '4',
					'placeholder' => __( 'Check out the new {podcastTitle} episode: {linkedEpisodeTitle}', 'podlove' )
				)
			) );
			

			$this->register_option( 'adn_preview', 'callback', array(
				'label' => __( 'Announcement preview', 'podlove' ),
				'callback' => function() use ( $user, $module_url ) {

					if ( ! $user )
						return;

					?>
					<style type="text/css">
					#podlove_adn_post_preview {
						border: 1px solid #ddd;
						background: white;
						padding: 15px 15px 5px 15px;
						font-style: normal;
						color: #333;
						font-size: 14px;
						font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
					}

					.adn.avatar {
						display: block;
						width: 57px;
						height: 57px;
						background-size: 57px 57px;
						float: left;
					}

					.adn.username {
						font-weight: bold;
					}

					.adn.content {
						margin-left: 67px;
					}

					.adn.body {
						min-height: 39px;
						line-height: 18px;
						margin-bottom: 10px;
					}

					.adn.footer {
						font-size: 12px;
						color: #524f54;
						text-align: left;
						line-height: 18px;
					}

					.adn.footer ul {
						color: rgb(111, 116, 119);
						margin: 0 0 0px 0px;
					}

					.adn.footer li {
						display: inline-block;
						margin-right: 10px;
					}
					
					.adn-dropdown {
						width: 180px;
					}					
					</style>
					
					<?php
					$podcast = Model\Podcast::get_instance();
					if ( $episode = Model\Episode::find_one_by_where('slug IS NOT NULL') ) {
						$example_data = array(
							'episode'      => get_the_title( $episode->post_id ),
							'episode-link' => get_permalink( $episode->post_id ),
							'subtitle'     => $episode->subtitle
						);
					} else {
						$example_data = array(
							'episode'      => 'My Example Episode',
							'episode-link' => 'http://www.example.com/episode/001',
							'subtitle'     => 'My Example Subtitle'
						);
					}
					?>
					<div id="podlove_adn_post_preview"
							data-podcast="<?php echo $podcast->title ?>"
							data-episode="<?php echo $example_data['episode'] ?>"
							data-episode-link="<?php echo $example_data['episode-link'] ?>"
							data-episode-subtitle="<?php echo $example_data['subtitle'] ?>">
						<div class="adn avatar" style="background-image:url(<?php echo $user->avatar_image->url ?>);"></div>
						<div class="adn content">
							<div class="adn username"><?php echo $user->username ?></div>
							<div class="adn body">Lorem ipsum dolor ...</div>
					
							<div class="adn footer">
								<ul>
									<li>
										<i class="podlove-icon-time"></i> now
									</li>
									<li>
										<i class="podlove-icon-reply"></i> Reply
									</li>
									<li>
										<i class="podlove-icon-share"></i> via Podlove Publisher
									</li>
								</ul>
							</div>
						</div>

						<div style="clear: both"></div>
					</div>

					<script type="text/javascript">
					var PODLOVE = PODLOVE || {};

					(function($){

						PODLOVE.AppDotNet = function () {
							var $textarea = $("#podlove_module_app_dot_net_adn_poster_announcement_text"),
							    $preview = $("#podlove_adn_post_preview");

							var parseUri = function (str) {
								var	o   = parseUri.options,
									m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
									uri = {},
									i   = 14;

								while (i--) uri[o.key[i]] = m[i] || "";

								uri[o.q.name] = {};
								uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
									if ($1) uri[o.q.name][$1] = $2;
								});

								return uri;
							};

							parseUri.options = {
								strictMode: false,
								key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
								q:   {
									name:   "queryKey",
									parser: /(?:^|&)([^&=]*)=?([^&]*)/g
								},
								parser: {
									strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
									loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
								}
							};

							var nl2br = function (str, is_xhtml) {
							    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
							    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
							}

							var endsWith = function (str, suffix) {
							    return str.indexOf(suffix, str.length - suffix.length) !== -1;
							}

							var update_preview = function() {
								var text = $textarea.val(),
									podcast = $preview.data('podcast'),
								    episode_link = $preview.data('episode-link'),
								    episode_subtitle = $preview.data('episode-subtitle'),
								    episode = $preview.data('episode');

								text = text.replace("{podcastTitle}", podcast);
								text = text.replace("{episodeTitle}", episode);
								text = text.replace("{episodeLink}",  episode_link);
								text = text.replace("{episodeSubtitle}", episode_subtitle);

								safetyNet = 0;
								shortened = false;
								while (safetyNet < 1000 && text.replace(/\{linkedEpisodeTitle\}/g, episode).length > 256) {
									safetyNet++;
									if (endsWith(text, "{linkedEpisodeTitle}") && episode.length > 0) {
										episode = episode.slice(0,-1); // shorten episode title by one character at a time
									} else {
										text = text.slice(0,-1); // shorten text by one character at a time
									}
									shortened = true;
								}

								text = text.replace(/\{linkedEpisodeTitle\}/g, '<a href="' + episode_link + '">' + episode + '</a> [' + parseUri(episode_link)['host'] + ']')

								if (shortened) {
									text = text + "…";
								}

								$(".adn.body", $preview).html(nl2br(text));
							};
							
							jQuery("#podlove_module_app_dot_net_adn_poster_announcement_text").autogrow();
							jQuery(".adn-dropdown").chosen(); 

							$textarea.keyup(function() {
								update_preview();
							});

							update_preview();
						}

					}(jQuery));


					jQuery(function($) {
						PODLOVE.AppDotNet();
					});
					</script>
					<?php
				}
			) );

    }

    /**
     * Fetch name of logged in user via ADN API.
     *
     * Cached in transient "podlove_adn_username".
     * 
     * @return string
     */
    public function fetch_authorized_user() {

    	$cache_key = 'podlove_adn_user';

    	if ( ( $user = get_transient( $cache_key ) ) !== FALSE ) {
    		return $user;
    	} else {
	    	if ( ! ( $token = $this->get_module_option('adn_auth_key') ) )
	    		return "";

	    	$ch = curl_init( 'https://alpha-api.app.net/stream/0/token?access_token=' . $token );                                                                      
	    	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET");
	    	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
	    	$decoded_result = json_decode( curl_exec( $ch ) );

	    	$user = $decoded_result ? $decoded_result->data->user : FALSE;
	    	set_transient( $cache_key, $user, 60*60*24*365 ); // 1 year, we devalidate manually
	    	return $user;
    	}

    }
    
    public function post_to_adn($post_id, $post_title) {

    	/**
    	 * String helper functions.
    	 *
    	 * Default to multibyte-functions but fall back to simple
    	 * ones if multibyte module is not available.
    	 */
    	$strpos_fun = function($haystack, $needle, $offset = 0, $encoding = 'UTF-8') {
    		if (function_exists('mb_strpos'))
    			return mb_strpos($haystack, $needle, $offset, $encoding);
    		else
    			return strpos($haystack, $needle, $offset);
    	};

    	$strlen_fun = function($str, $encoding = 'UTF-8') {
    		if (function_exists('mb_strlen'))
    			return mb_strlen($str, $encoding);
    		else
    			return strlen($str);
    	};

    	$substr_fun = function($str, $start, $length = NULL, $encoding = 'UTF-8') {
    		if (function_exists('mb_substr'))
    			return mb_substr($str, $start, $length, $encoding);
    		else
    			return substr($str, $start, $length);
    	};

    	$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
    	$podcast = \Podlove\Model\Podcast::get_instance();
    	$posted_text = $this->get_module_option('adn_poster_announcement_text');
    	
    	$posted_text = str_replace("{podcastTitle}", $podcast->title, $posted_text);
    	$posted_text = str_replace("{episodeTitle}", $post_title, $posted_text);
    	$posted_text = str_replace("{episodeLink}", get_permalink( $post_id ), $posted_text);
    	$posted_text = str_replace("{episodeSubtitle}", $episode->subtitle, $posted_text);
    	
    	$posted_linked_title = array();
    	$start_position = 0;
    	
    	while ( ($position = $strpos_fun( $posted_text, "{linkedEpisodeTitle}", $start_position, "UTF-8" )) !== FALSE ) {
			$length = $strlen_fun( $post_title, "UTF-8" );
        	$episode_entry = array(
        		"url"  => get_permalink( $post_id ), 
        		"text" => $post_title, 
        		"pos"  => $position, 
        		"len"  => ($position + $length <= 256) ? $length : 256 - $position
        	);
        	array_push( $posted_linked_title, $episode_entry );
        	$start_position = $position + 1;
		}
    	
    	$posted_text = str_replace("{linkedEpisodeTitle}", $post_title, $posted_text);

    	if ( $strlen_fun( $posted_text ) > 256 ) {
    		$posted_text = $substr_fun( $posted_text, 0, 255 ) . "…";
    	}
    	
    	if($this->get_module_option('adn_language_annotation') !== "") {
    		$language_annotation = array("type" => "net.app.core.language", "value" => array("language" => $this->get_module_option('adn_language_annotation')));
        }
        
        if($this->get_module_option('adn_language_annotation') !== "") {
        	$data = array("text" => $posted_text, "annotations" => array($language_annotation), "entities" => array("links" => $posted_linked_title,"parse_links" => true));
        } else {
    		$data = array("text" => $posted_text, "entities" => array("links" => $posted_linked_title,"parse_links" => true));                                                  
    	}
		$data_string = json_encode($data);        
		
		$ch = curl_init('https://alpha-api.app.net/stream/0/posts?access_token='.$this->get_module_option('adn_auth_key').'');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
		curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . $strlen_fun($data_string))                                                                       
		);       
		
		$is_already_published = get_post_meta($post_id, '_podlove_episode_was_published', true);                                                                                                            

		if(!$is_already_published) {
			$result = curl_exec($ch);
			$parsed_result = json_decode($result);
			if (isset($parsed_result->meta) && isset($parsed_result->meta->error_message) && $parsed_result->meta->error_message) {
				\Podlove\Log::get()->addError( 'Failed to post to ADN', array(
					'episode_id' => $episode->id,
					'error' => $parsed_result->meta->error_message
				) );
			}
		}

		if($this->get_module_option('adn_patter_room_announcement') == "on") {
			$crosspost_annotation = array("type" => "net.app.core.crosspost", "value" => array("canonical_url" => "http://patter-app.net/room.html?channel=".$this->get_module_option('adn_patter_room')));
			$invite_annotation = array("type" => "net.app.core.channel.invite", "value" => array("channel_id" => $this->get_module_option('adn_patter_room')));

		
			if($this->get_module_option('adn_language_annotation') !== "") {
				$data = array("text" => $posted_text, "annotations" => array($language_annotation, $crosspost_annotation, $invite_annotation), "entities" => array("links" => $posted_linked_title,"parse_links" => true), "channel_id" => $this->get_module_option('adn_patter_room'));  
			} else {
				$data = array("text" => $posted_text, "annotations" => array($crosspost_annotation, $invite_annotation),  "entities" => array("links" => $posted_linked_title,"parse_links" => true), "channel_id" => $this->get_module_option('adn_patter_room'));  
			
			}
			$data_string = json_encode($data);  
		
			$ch = curl_init('https://alpha-api.app.net/stream/0/channels/'.$this->get_module_option('adn_patter_room').'/messages?access_token='.$this->get_module_option('adn_auth_key').'');                                                                      
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
			curl_setopt($ch, CURLOPT_USERAGENT, 'Podlove Publisher (http://podlove.org/)');                                                              
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
				'Content-Type: application/json',                                                                                
				'Content-Length: ' . $strlen_fun($data_string))                                                                       
			);                                                                                                                  

			if(!$is_already_published) {
				$result = curl_exec($ch);
				$parsed_result = json_decode($result);
				if (isset($parsed_result->meta) && isset($parsed_result->meta->error_message) && $parsed_result->meta->error_message) {
					\Podlove\Log::get()->addError( 'Failed to post to ADN Patter Room', array(
						'episode_id' => $episode->id,
						'error' => $parsed_result->meta->error_message
					) );
				}
			}
		}
		
		 update_post_meta( $post_id, '_podlove_episode_was_published', true );
    }
    
    public function reset_adn_auth() {
    	if(isset( $_GET["reset_appnet_auth_code"] ) && $_GET["reset_appnet_auth_code"] == "1") {
				$this->update_module_option('adn_auth_key', "");
				delete_transient('podlove_adn_user');
    			header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');    
    	}
    }
    
	public function post_to_adn_handler($postid) {
	    $post_id = $_POST['post_ID'];
    	$post_title = $_POST['post_title'];
    
    	if($this->get_module_option('adn_post_delay') !== "" AND $this->get_module_option('adn_post_delay') !== "00:00:00") {
    		$delayed_time = strtotime($this->get_module_option('adn_post_delay'));
    		$delayed_time_in_seconds = date("H", $delayed_time) * 3600 + date("i", $delayed_time) * 60 + date("s", $delayed_time);
			wp_schedule_single_event( time()+$delayed_time_in_seconds, "delayed_adn_post", array($post_id, $post_title));
		} else {
			$this->post_to_adn($post_id, $post_title);
		}
	}
	
	public function post_to_adn_delayer($post_id, $post_title) {
		$this->post_to_adn($post_id, $post_title);
	}
    
}