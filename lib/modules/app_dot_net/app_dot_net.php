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
			}
			
			$languages = array("ar" => "Arabic",
				"az" => "Azerbaijani",
				"bg" => "Bulgarian",
				"bn" => "Bengali",
				"bs" => "Bosnian",
				"ca" => "Catalan",
				"cs" => "Czech",
				"cy" => "Welsh",
				"da" => "Danish",
				"de" => "German",
				"el" => "Greek, Modern",
				"en" => "English",
				"en_GB" => "English, British",
				"es" => "Spanish",
				"es_AR" => "Spanish, Argentina",
				"es_MX" => "Spanish, Mexico",
				"es_NI" => "Spanish, Nicaragua",
				"et" => "Estonian",
				"eu" => "Basque",
				"fa" => "Persian",
				"fi" => "Finnish",
				"fr" => "French",
				"fy_NL" => "Western Frisian, Netherlands",
				"ga" => "Irish",
				"gl" => "Galician",
				"he" => "Hebrew, Modern",
				"hi" => "Hindi",
				"hr" => "Croatian",
				"hu" => "Hungarian",
				"id" => "Indonesian",
				"is" => "Icelandic",
				"it" => "Italian",
				"ja" => "Japanese",
				"ka" => "Georgian",
				"kk" => "Kazakh",
				"km" => "Khmer",
				"kn" => "Kannada",
				"ko" => "Korean",
				"lt" => "Lithuanian",
				"lv" => "Latvian",
				"mk" => "Macedonian",
				"ml" => "Malayalam",
				"mn" => "Mongolian",
				"nb" => "Norwegian Bokmål",
				"ne" => "Nepali",
				"nl" => "Dutch",
				"nn" => "Norwegian Nynorks",
				"no" => "Norwegian",
				"pa" => "Panjabi",
				"pl" => "Polish",
				"pt" => "Portuguese",
				"pt_BR" => "Portuguese, Brazil",
				"ro" => "Romanian",
				"ru" => "Russian",
				"sk" => "Slovak",
				"sl" => "Slovene",
				"sq" => "Albanian",
				"sr" => "Serbian",
				"sr_Latn" => "Serbian, Latin",
				"sv" => "Swedish",
				"sw" => "Swahili",
				"ta" => "Tamil",
				"te" => "Telugu",
				"th" => "Thai",
				"tr" => "Turkish",
				"tt" => "Tatar",
				"uk" => "Ukrainian",
				"ur" => "Urdu",
				"vi" => "Vietnamese",
				"zh_CN" => "Chinese, China",
				"zh_TW" => "Chinese, Taiwan"
			);
			asort($languages);
			
			$this->register_option( 'adn_language_annotation', 'select', array(
			'label'       => __( 'Language of Announcement', 'podlove' ),
			'description' => 'Your announcement will include an <a href="http://developers.app.net/docs/meta/annotations/" target="_blank">App.net language annotation</a>.',
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
			'description' => 'The Patter room of your Podcast.',
			'html'        => array( 'class' => 'regular-text adn-dropdown' ),
			'options'	  => $patter_rooms
			) );
			
			$this->register_option( 'adn_patter_room_announcement', 'checkbox', array(
			'label'       => __( 'Announcement in Patter room', 'podlove' ),
			'description' => 'The Announcement text will be posted in the chosen Patter room, too.'
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
					'placeholder' => __( 'Check out the new {podcastTitle} episode: {linkedEpisodeTitle}', 'podlove' ) )
			) );
			

			$this->register_option( 'adn_preview', 'callback', array(
				'label' => __( 'Announcement preview', 'podlove' ),
				'callback' => function() use ( $user ) {

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

					<link rel="stylesheet" href="<?php echo $this->get_module_url()."/chosen.min.css"; ?>" />
					<script type="text/javascript" src="<?php echo $this->get_module_url()."/chosen.jquery.min.js"; ?>"></script>
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
					
					function get_channels(token) {
						var module_url = "<?php echo $this->get_module_url(); ?>";
						jQuery.post(module_url + '/get_channels.php', { token: token }, function(received_data) {	
						var raw_channels = jQuery.parseJSON(received_data);
							jQuery(raw_channels.data).each(function(key, value) {
								var annotations = value.annotations;
									if(annotations.length !== 0) {
										jQuery(annotations).each(function(annotation_id, annotation) {
											if(annotation.type == "net.patter-app.settings") {
												var annotation_properties = annotation.value;
												jQuery("#podlove_module_app_dot_net_adn_patter_room").append('<option value="' + value.id + '">' + annotation_properties.name + '</option>');
											}
										});
									}
								delete annotations;
							});
						});
					}
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
    
    public function post_to_adn() {

    	$post_id = $_POST['post_ID'];
    	$post_title = $_POST['post_title'];

    	if ( ! get_post_meta( $post_id, '_podlove_episode_was_published', true ) ) {
	    	$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
	    	$podcast = \Podlove\Model\Podcast::get_instance();
	    	$posted_text = $this->get_module_option('adn_poster_announcement_text');
	    	
	    	$posted_text = str_replace("{podcastTitle}", $podcast->title, $posted_text);
	    	$posted_text = str_replace("{episodeTitle}", $post_title, $posted_text);
	    	$posted_text = str_replace("{episodeLink}", get_permalink( $post_id ), $posted_text);
	    	$posted_text = str_replace("{episodeSubtitle}", $episode->subtitle, $posted_text);
	    	
	    	$posted_linked_title = array();
	    	$start_position = 0;
	    	
	    	while( ($position = mb_strpos( $posted_text, "{linkedEpisodeTitle}", $start_position, "UTF-8" )) !== FALSE ) {
				$length = mb_strlen( $post_title, "UTF-8" );
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

	    	if ( strlen( $posted_text ) > 256 ) {
	    		$posted_text = substr( $posted_text, 0, 255 ) . "…";
	    	}
	    
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
    	
		update_post_meta( $post_id, '_podlove_episode_was_published', true );

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
			'Content-Length: ' . strlen($data_string))                                                                       
		);                                                                                                                   

		$result = curl_exec($ch);
		
		if($this->get_module_option('adn_patter_room_announcement') == "on") {
			if($this->get_module_option('adn_language_annotation') !== "") {
				$data = array("text" => $posted_text, "annotations" => array($language_annotation), "entities" => array("links" => $posted_linked_title,"parse_links" => true), "channel_id" => $this->get_module_option('adn_patter_room'));  
			} else {
				$data = array("text" => $posted_text, "entities" => array("links" => $posted_linked_title,"parse_links" => true), "channel_id" => $this->get_module_option('adn_patter_room'));  
			
			}
			$data_string = json_encode($data);  
		
			$ch = curl_init('https://alpha-api.app.net/stream/0/channels/'.$this->get_module_option('adn_patter_room').'/messages?access_token='.$this->get_module_option('adn_auth_key').'');                                                                      
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
    
    public function reset_adn_auth() {
    	if(isset( $_GET["reset_appnet_auth_code"] ) && $_GET["reset_appnet_auth_code"] == "1") {
				$this->update_module_option('adn_auth_key', "");
				delete_transient('podlove_adn_user');
    			header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');    
    	}
    }
    
    
}