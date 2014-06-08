<?php 
namespace Podlove\Modules\AppDotNet;
use \Podlove\Model;

class App_Dot_Net extends \Podlove\Modules\Base {

	protected $module_name = 'App.net';
	protected $module_description = 'Support for Announcements on App.net';
	protected $module_group = 'external services';
	
	/**
	 * API to ADN Service
	 * 
	 * @var Podlove\Modules\AppDotNet\API
	 */
	private $api;

	public function load() {

			$this->api = new API_Wrapper($this);
	
			$module_url = $this->get_module_url();
			$user = null;

			$selected_role = $this->get_module_option('adn_contributor_filter_role');
			$selected_group = $this->get_module_option('adn_contributor_filter_group');

			add_action( 'podlove_module_was_activated_app_dot_net', array( $this, 'was_activated' ) );

			add_action( 'wp_ajax_podlove-refresh-channel', array( $this, 'ajax_refresh_channel' ) );
			add_action( 'wp_ajax_podlove-adn-post', array( $this, 'ajax_post_to_adn' ) );
			add_action( 'wp_ajax_podlove-preview-adn-post', array( $this, 'ajax_preview_alpha_post' ) );
	
			if ($this->get_module_option('adn_auth_key') !== "" ) {
				add_action('publish_podcast', array( $this, 'post_to_adn_handler' ));
				add_action('publish_future_podcast', array( $this, 'post_to_adn_handler' ));
				add_action('delayed_adn_post', array( $this, 'post_to_adn' ), 10, 2);
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
				if ( $user = $this->api->fetch_authorized_user() ) { 
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
					'description' => '<span class="podlove_adn_patter_refresh" data-category="patter_room"><i class="podlove-icon-repeat"></i></span>From the list of subscribed <a href="http://patter-app.net/faq.html" target="_blank">Patter rooms</a>, choose the one related to your Podcast.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $this->api->fetch_patter_rooms()
				) );

				$this->register_option( 'adn_broadcast', 'checkbox', array(
					'label'       => __( 'Broadcast', 'podlove' ),
					'description' => 'Send announcement via App.net Broadcast Channel.'
				) );

				$this->register_option( 'adn_broadcast_channel', 'select', array(
					'description' => '<span class="podlove_adn_broadcast_refresh" data-category="broadcast_channel"><i class="podlove-icon-repeat"></i></span> From the list of your Broadcast channels, choose the one related to your Podcast.',
					'html'        => array( 'class' => 'regular-text adn-dropdown' ),
					'options'	  => $this->api->fetch_broadcast_channels()
				) );

				$this->register_option( 'adn_automatic_announcement', 'checkbox', array(
					'label'       => __( 'Automatic Announcement', 'podlove' ),
					'description' => 'Announces new podcast episodes on App.net'
				) );

				$adn_post_delay_hours   = str_pad( $this->get_module_option('adn_post_delay_hours'), 2, 0, STR_PAD_LEFT );
				$adn_post_delay_minutes = str_pad( $this->get_module_option('adn_post_delay_minutes'), 2, 0, STR_PAD_LEFT );

				$this->register_option( 'adn_post_delay', 'callback', array(
					'label' => __( 'Post delay', 'podlove' ),
					'callback' => function() use ( $adn_post_delay_hours, $adn_post_delay_minutes ) {
						?>
							<input type="text" name="podlove_module_app_dot_net[adn_post_delay_hours]" id="podlove_module_app_dot_net_adn_post_delay_hours" value="<?php echo( $adn_post_delay_hours ? $adn_post_delay_hours : '' ); ?>" class="regular-text" placeholder="00" >
								<label for="podlove_module_app_dot_net_adn_post_delay_hours">Hours</label>
							<input type="text" name="podlove_module_app_dot_net[adn_post_delay_minutes]" id="podlove_module_app_dot_net_adn_post_delay_minutes" value="<?php echo( $adn_post_delay_minutes ? $adn_post_delay_minutes : '' ); ?>" class="regular-text" placeholder="00" >
								<label for="podlove_module_app_dot_net_adn_post_delay_minutes">Minutes</label>
						<?php
					}
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

				$description = $this->tags_description( $description );

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
					'callback' => function() use ( $selected_role, $selected_group, $user, $module_url ) {

						if ( ! $user )
							return;

						$podcast = Model\Podcast::get_instance();
						if ( $episode = Model\Episode::find_one_by_where('slug IS NOT NULL') ) {
							$example_data = array(
								'episode'      => get_the_title( $episode->post_id ),
								'episode-link' => get_permalink( $episode->post_id ),
								'subtitle'     => $episode->subtitle,
								'contributors' => ''
							);
							$example_data = apply_filters( 'podlove_adn_example_data', $example_data, $episode->post_id, $selected_role, $selected_group );
						} else {
							$example_data = array(
								'episode'      => 'My Example Episode',
								'episode-link' => 'http://www.example.com/episode/001',
								'subtitle'     => 'My Example Subtitle',
								'contributors' => '@example @elpmaxe'
							);
						}
						?>
						<div id="podlove_adn_post_preview"
								data-podcast="<?php echo $podcast->title ?>"
								data-episode="<?php echo $example_data['episode'] ?>"
								data-episode-link="<?php echo $example_data['episode-link'] ?>"
								data-episode-subtitle="<?php echo $example_data['subtitle'] ?>"
								data-contributors="<?php echo $example_data['contributors'] ?>">
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

				$this->register_option( 'adn_manual_post', 'callback', array(
					'label' => __( 'Manual Announcement', 'podlove' ),
					'callback' => function() {
						?>
							<select id="adn_manual_post_episode_selector" class="chosen">
								<?php
								$episodes = Model\Episode::allByTime();
								foreach ( $episodes as $episode ) {
									$post = get_post( $episode->post_id );
									echo "<option value='" . $episode->post_id . "'>" . $post->post_title . "</option>";
								}
								?>
							</select>
							<span class="button" id="adn_manual_post_alpha">
								Announce, as configured 
								<span class="adn-post-status-pending">
									<i class="podlove-icon-spinner rotate"></i>
								</span>
								<span class="adn-post-status-ok">
									<i class="podlove-icon-ok"></i>
								</span>
							</span>
						<?php
					}
				) );
				
			}
	}

	public function was_activated() {
		$episodes = Model\Episode::all();
		foreach ( $episodes as $episode ) {
			$post = get_post( $episode->post_id );
			if ( $post->post_status == 'publish' && !get_post_meta( $episode->post_id, '_podlove_episode_was_published', true ) )
				update_post_meta( $episode->post_id, '_podlove_episode_was_published', true );
		}
	}

	public function ajax_refresh_channel() {
		$category = $_REQUEST['category'];
		switch ( $category ) {
			case 'broadcast_channel':
				delete_transient('podlove_adn_broadcast_channels');
				$result = $this->api->fetch_broadcast_channels();
			break;
			case 'patter_room':
				delete_transient('podlove_adn_rooms');
				$result = $this->api->fetch_patter_rooms();
			break;
		}
		
		return \Podlove\AJAX\AJAX::respond_with_json( $result );
	}

	private function is_already_published($post_id) {
		return get_post_meta($post_id, '_podlove_episode_was_published', true);
	}

	private function post_to_alpha($data) {
		$url = sprintf(
			'https://alpha-api.app.net/stream/0/posts?access_token=%s',
			$this->get_module_option('adn_auth_key')
		);

		$this->api->post($url, $data);
	}

	private function post_to_patter($data) {

		if ( $this->get_module_option('adn_patter_room_announcement') !== "on" )
			return;

		$data['channel_id'] = $this->get_module_option('adn_patter_room');
		$data['annotations'][] = $this->get_crosspost_annotation();
		$data['annotations'][] = $this->get_invite_annotation();

		$url = sprintf(
			'https://alpha-api.app.net/stream/0/channels/%s/messages?access_token=%s',
			$this->get_module_option('adn_patter_room'),
			$this->get_module_option('adn_auth_key')
		);

		$this->api->post($url, $data);
	}

	private function broadcast($data, $post_id) {

		if ( $this->get_module_option('adn_broadcast') !== "on" )
			return;

		$data['channel_id'] = $this->get_module_option('adn_broadcast_channel');
		$data['annotations'][] = $this->get_broadcast_metadata( get_the_title( $post_id ) );
		$data['annotations'][] = $this->get_read_more_link( get_permalink( $post_id ) );

		$url = sprintf(
			'https://alpha-api.app.net/stream/0/channels/%s/messages?access_token=%s',
			$this->get_module_option('adn_broadcast_channel'),
			$this->get_module_option('adn_auth_key')
		);

		$this->api->post($url, $data);
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

	public function post_to_adn($post_id) {

		$episode = Model\Episode::find_one_by_post_id( $post_id );
		$episode_text = $this->get_text_for_episode( $post_id );

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

		$data['annotations'][] = $this->get_episode_cover( $post_id );

		$this->post_to_alpha($data);
		$this->post_to_patter($data);

		// Unset Links for the Broadcast
		unset($data['entities']['links']);

		// Change Announcement text for broadcast
		$data['text'] = ( !empty( $episode->subtitle ) ? $episode->subtitle . "\n\n" : '' ) . $episode->summary;

		$this->broadcast( $data, $post_id );
		
		update_post_meta( $post_id, '_podlove_episode_was_published', true );
	}

	public function replace_tags( $post_id ) {
		$selected_role = $this->get_module_option('adn_contributor_filter_role');
		$selected_group = $this->get_module_option('adn_contributor_filter_group');

		$text = $this->get_module_option('adn_poster_announcement_text');
		$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
		$podcast = Model\Podcast::get_instance();
		$post = get_post( $post_id );
		$post_title = $post->post_title;
		
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
		$text = apply_filters( 'podlove_adn_tags', $text, $post_id, $selected_role, $selected_group );

		return array(
				'text' => $text,
				'posted_linked_title' => $posted_linked_title
			);
	}

	public function ajax_preview_alpha_post() {
		if( !$_REQUEST['post_id'] )
			return;

		$result = $this->replace_tags( $_REQUEST['post_id'] );

		return \Podlove\AJAX\AJAX::respond_with_json( array( 'preview' => $result['text'] ) );
	}

	private function get_text_for_episode($post_id) {
		$post = $this->replace_tags( $post_id );

		if ( \Podlove\strlen( $post['text'] ) > 256 )
			$post['text'] = \Podlove\substr( $post['text'], 0, 255 ) . "…";

		return array(
			'text' => $post['text'],
			'link_annotation' => $post['posted_linked_title']
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

		$cover = $episode->get_cover_art_with_fallback();

		if ( empty( $cover ) )
			return;
		
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
			delete_transient('podlove_adn_broadcast_channels');
			header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');    
		}
	}

	public function ajax_post_to_adn() {
		if( !$_REQUEST['post_id'] )
			return;

		$this->post_to_adn( $_REQUEST['post_id'] );
	}
	
	public function post_to_adn_handler( $postid ) {
		if ( $this->is_already_published( $post_id ) || $this->get_module_option('adn_automatic_announcement') !== 'on' )
			return;

		$post_id = $_POST['post_ID'];

		$adn_post_delay_hours   = str_pad( $this->get_module_option('adn_post_delay_hours'), 2, 0, STR_PAD_LEFT );
		$adn_post_delay_minutes = str_pad( $this->get_module_option('adn_post_delay_minutes'), 2, 0, STR_PAD_LEFT );
	
		$delayed_time = strtotime( $adn_post_delay_hours . $adn_post_delay_minutes );
		$delayed_time_in_seconds = date("H", $delayed_time) * 3600 + date("i", $delayed_time) * 60;

		wp_schedule_single_event( time()+$delayed_time_in_seconds, "delayed_adn_post", array( $post_id ) );
	}

	private function tags_description( $description ) {
		return apply_filters( 'podlove_adn_tags_description', $description );
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