<?php 
namespace Podlove\Modules\AppDotNet;
use \Podlove\Model;
use \Podlove\Http;

class App_Dot_Net extends \Podlove\Modules\Base {

    protected $module_name = 'App.net';
    protected $module_description = 'Announces new podcast episodes on App.net';
    protected $module_group = 'external services';
	
    public function load() {
    
    		$module_url = $this->get_module_url();
    		$user = null;
    	
    		if ($this->get_module_option('adn_auth_key') !== "") {
				add_action('publish_podcast', array( $this, 'post_to_adn_handler' ));
				add_action('delayed_adn_post', array( $this, 'post_to_adn_delayer' ), 10, 2);
			}
			
			if ( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'reset_adn_auth'));
    		}   
        
    		// Import all posts as already published
    		add_filter( 'wp_import_post_meta', function($postmetas, $post_id, $post) {
    			$postmetas[] = array(
    				'key' => '_podlove_episode_was_published',
    				'value' => true
    			);
    			return $postmetas;
    		}, 10, 3 );

			if ($this->get_module_option('adn_auth_key') == "") {
				$description = '<i class="podlove-icon-remove"></i> '
	    		             . __( 'You need to allow Podlove Publisher to access your App.net account. To do so please start the authorization process, follow the instructions and paste the obtained code in the field above.', 'podlove' )
	    		             . '<br><a href="https://auth.podlove.org/adn.php?step=1" class="button button-primary" target="_blank">' . __( 'Start authorization process now', 'podlove' ) . '</a>';
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
			
				$this->register_option( 'adn_language_annotation', 'select', array(
					'label'       => __( 'Language of Announcement', 'podlove' ),
					'description' => 'Selecting the language of the Announcement, will include an <a href="http://developers.app.net/docs/meta/annotations/" target="_blank">App.net language annotation</a>.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $this->get_languages()
				) );
			
				$this->register_option( 'adn_patter_room_announcement', 'checkbox', array(
					'label'       => __( 'Patter', 'podlove' ),
					'description' => 'Post announcement to Patter room, too.'
				) );

				$this->register_option( 'adn_patter_room', 'select', array(
					'description' => 'From the list of subscribed <a href="http://patter-app.net/faq.html" target="_blank">Patter rooms</a>, choose the one related to your Podcast.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $this->get_patter_rooms()
				) );

				$this->register_option( 'adn_broadcast', 'checkbox', array(
					'label'       => __( 'Broadcast', 'podlove' ),
					'description' => 'Enables functionality for App.net Broadcasts.'
				) );

				$this->register_option( 'adn_broadcast_channel', 'select', array(
					'description' => 'From the list of your Broadcast channels, choose the one related to your Podcast.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $this->get_broadcast_channels()
				) );


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

						<script type="text/javascript" src="<?php echo $module_url ?>/adn.js"></script>
						<link rel="stylesheet" type="text/css" href="<?php echo $module_url ?>/adn.css" />
						<?php
					}
				) );
				
			}
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

    	if ( ( $user = get_transient( $cache_key ) ) !== false ) {
    		return $user;
    	} else {
	    	if ( ! ( $token = $this->get_module_option('adn_auth_key') ) )
	    		return false;

	    	$curl = new Http\Curl();
	    	$curl->request( 'https://alpha-api.app.net/stream/0/token?access_token=' . $token);
	    	$response = $curl->get_response();

	    	if ($curl->isSuccessful()) {
		    	$decoded_result = json_decode( $response['body'] );
		    	$user = $decoded_result ? $decoded_result->data->user : false;
		    	set_transient( $cache_key, $user, 60*60*24*365 ); // 1 year, we devalidate manually
		    	return $user;
	    	}
    	}

    	return false;
    }

    private function is_already_published($post_id) {
    	return get_post_meta($post_id, '_podlove_episode_was_published', true);
    }
    
    private function send_data_to_adn($url, $data) {
    	
    	$data_string = json_encode($data);

    	$curl = new Http\Curl();
    	$curl->request( $url, array(
    		'method' => 'POST',
    		'body' => $data_string,
    		'headers' => array(
    			'Content-type'   => 'application/json',
    			'Content-Length' => \Podlove\strlen($data_string)
    		)
    	) );

    	$curl->get_response();
    }

    private function post_to_alpha($data) {
		$url = sprintf(
			'https://alpha-api.app.net/stream/0/posts?access_token=%s',
			$this->get_module_option('adn_auth_key')
		);

		$this->send_data_to_adn($url, $data);
    }

    private function post_to_patter($data) {

    	if ( $this->get_module_option('adn_patter_room_announcement') !== "on" )
    		return;

		$data['channel_id'] = $this->get_module_option('adn_patter_room');
		$data['annotations'][] = $this->get_crosspost_annotation();
		$data['annotations'][] = $this->get_invite_annotation();
    	$data['annotations'][] = $this->get_episode_cover( $_POST['post_ID'] );

		$url = sprintf(
			'https://alpha-api.app.net/stream/0/channels/%s/messages?access_token=%s',
			$this->get_module_option('adn_patter_room'),
			$this->get_module_option('adn_auth_key')
		);

		$this->send_data_to_adn($url, $data);
    }

    private function broadcast($data) {

    	if ( $this->get_module_option('adn_broadcast') !== "on" )
    		return;

    	$data['channel_id'] = $this->get_module_option('adn_broadcast_channel');
    	$data['annotations'][] = $this->get_broadcast_metadata( $_POST['post_title'] );
    	$data['annotations'][] = $this->get_read_more_link( get_permalink($_POST['post_ID']) );
    	$data['annotations'][] = $this->get_episode_cover( $_POST['post_ID'] );

    	$url = sprintf(
    		'https://alpha-api.app.net/stream/0/channels/%s/messages?access_token=%s',
    		$this->get_module_option('adn_broadcast_channel'),
    		$this->get_module_option('adn_auth_key')
    	);

    	$this->send_data_to_adn($url, $data);
    }

    private function get_broadcast_metadata($subject) {
    	return array(
    		"type" => "net.app.core.broadcast.message.metadata",
    		"value" => array(
    			"subject" => $subject
    		)
    	);
    }

    private function get_read_more_link($read_more_link) {
    	return array(
    		"type" => "net.app.core.crosspost",
    		"value" => array(
    			"canonical_url" => $read_more_link
    		)
    	);
    }

    public function post_to_adn($post_id, $post_title) {

    	if ( $this->is_already_published($post_id) )
    		return;

    	$episode = Model\Episode::find_one_by_post_id( $post_id );
    	$episode_text = $this->get_text_for_episode( $episode, $post_id, $post_title );

    	$text            = $episode_text['text'];
    	$link_annotation = $episode_text['link_annotation'];
        
        $data = array(
        	"text" => $text,
        	"entities" => array(
        		"links" => $link_annotation,
        		"parse_links" => true
        	),
        	"annotations" => array()
        );

        if ($this->get_module_option('adn_language_annotation') !== "")
        	$data['annotations'][] = $this->get_language_annotation();

    	$data['annotations'][] = $this->get_episode_cover( $_POST['post_ID'] );

        $this->post_to_alpha($data);
        $this->post_to_patter($data);

        // Change Announcement text for broadcast

        $data['text'] = ( !empty( $_POST['_podlove_meta']['subtitle'] ) ? $_POST['_podlove_meta']['subtitle'] . "\n\n" : '' ) . $_POST['_podlove_meta']['summary'];
        $this->broadcast($data);
		
		update_post_meta( $post_id, '_podlove_episode_was_published', true );
    }

    private function get_text_for_episode($episode, $post_id, $post_title) {

		$podcast = Model\Podcast::get_instance();
		$text = $this->get_module_option('adn_poster_announcement_text');
		
		$text = str_replace("{podcastTitle}", $podcast->title, $text);
		$text = str_replace("{episodeTitle}", $post_title, $text);
		$text = str_replace("{episodeLink}", get_permalink( $post_id ), $text);
		$text = str_replace("{episodeSubtitle}", $episode->subtitle, $text);
		
		$posted_linked_title = array();
		$start_position = 0;
		
		while ( ($position = \Podlove\strpos( $text, "{linkedEpisodeTitle}", $start_position, "UTF-8" )) !== FALSE ) {
			$length = \Podlove\strlen( $post_title, "UTF-8" );
	    	$episode_entry = array(
	    		"url"  => get_permalink( $post_id ), 
	    		"text" => $post_title, 
	    		"pos"  => $position, 
	    		"len"  => ($position + $length <= 256) ? $length : 256 - $position
	    	);
	    	array_push( $posted_linked_title, $episode_entry );
	    	$start_position = $position + 1;
		}
		
		$text = str_replace("{linkedEpisodeTitle}", $post_title, $text);

		if ( \Podlove\strlen( $text ) > 256 )
			$text = \Podlove\substr( $text, 0, 255 ) . "…";

		return array(
			'text' => $text,
			'link_annotation' => $posted_linked_title
		);
    }

    private function get_crosspost_annotation() {
    	return array(
    		"type" => "net.app.core.crosspost",
    		"value" => array(
    			"canonical_url" => "http://patter-app.net/room.html?channel=" . $this->get_module_option('adn_patter_room')
    		)
    	);
    }

    private function get_invite_annotation() {
    	return array(
    		"type" => "net.app.core.channel.invite",
    		"value" => array(
    			"channel_id" => $this->get_module_option('adn_patter_room')
    		)
    	);
    }

    private function get_language_annotation() {
    	return array(
    		"type" => "net.app.core.language",
    		"value" => array(
    			"language" => $this->get_module_option('adn_language_annotation')
    		)
    	);
    }

    private function get_episode_cover( $post_id ) {
    	$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );

    	if( !empty( $_POST['_podlove_meta']['cover_art'] ) ) {
    		$cover = $_POST['_podlove_meta']['cover_art'];
    	} else {
    		$cover = $episode->get_cover_art_with_fallback();
    	}
    	
    	$cover_info = getimagesize( $cover );

    	return array(
    		"type" => "net.app.core.oembed",
    		"value" => array(
				"type" => "photo",
				"version" => "1.0",
				"width" => $cover_info[0],
				"height" => $cover_info[1],
				"url" => $cover,
				"thumbnail_width" => $cover_info[0],
				"thumbnail_height" => $cover_info[1],
				"thumbnail_url" => $cover
    		)
    	);
    }
    
    public function reset_adn_auth() {
    	if (isset( $_GET["reset_appnet_auth_code"] ) && $_GET["reset_appnet_auth_code"] == "1") {
			$this->update_module_option('adn_auth_key', "");
			delete_transient('podlove_adn_user');
			delete_transient('podlove_adn_rooms');
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
 
	public function get_patter_rooms() {
		$cache_key = 'podlove_adn_rooms';

		if ( ( $patter_rooms = get_transient( $cache_key ) ) !== FALSE ) {
			return $patter_rooms;
		} else {
			$url = 'https://alpha-api.app.net/stream/0/channels?include_annotations=1&access_token=' . $this->get_module_option('adn_auth_key');

			$curl = new Http\Curl();
			$curl->request( $url, array(
				'headers' => array( 'Content-type'  => 'application/json' )
			) );
			$response = $curl->get_response();

			if (!$curl->isSuccessful())
				return array();
			
			$patter_rooms = array();
			
			foreach ( json_decode($response['body']) as $channel ) {
				foreach ( $channel as $channel_details ) {
					
					if ( ! $this->channel_has_annotations( $channel_details ) )
						continue;

					foreach ( $channel_details->annotations as $annotation_id => $annotation_values ) {
						if ( $annotation_values->type == "net.patter-app.settings" )
							$patter_rooms[$channel_details->id] = $annotation_values->value->name;
					}
				}
			}

			set_transient( $cache_key, $patter_rooms, 60*60*24*365 ); // 1 year, we devalidate manually
			return $patter_rooms;
		}
	}

	public function get_broadcast_channels() {
		$cache_key = 'podlove_adn_broadcast_channels';

		if ( ( $broadcast_channels = get_transient( $cache_key ) ) !== FALSE ) {
			return $broadcast_channels;
		} else {
			$url = 'https://alpha-api.app.net/stream/0/channels?include_annotations=1&access_token=' . $this->get_module_option('adn_auth_key');

			$curl = new Http\Curl();
			$curl->request( $url, array(
				'headers' => array( 'Content-type'  => 'application/json' )
			) );
			$response = $curl->get_response();

			if (!$curl->isSuccessful())
				return array();
			
			$broadcast_channels = array();
			
			foreach ( json_decode($response['body'])->data as $channel ) {

				if ( $channel->type == "net.app.core.broadcast" && $channel->you_can_edit == 1 ) {
					$title = '';
					foreach ($channel->annotations as $annotation) {
						if( $annotation->type == "net.app.core.broadcast.metadata" )
							$title = $annotation->value->title;
					}

					$broadcast_channels[$channel->id] = $title;
				}	
			}

			set_transient( $cache_key, $broadcast_channels, 60*60*24*365 ); // 1 year, we devalidate manually
			return $broadcast_channels;
		}
	}

	private function channel_has_annotations($details) {
		return isset($details->annotations) && count($details->annotations) !== 0;
	}

	private function get_languages() {
		return array(
			"ar" => "Arabic",
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
	}
}