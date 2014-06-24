<?php 
namespace Podlove\Modules\Auphonic;
use \Podlove\Model;
use \Podlove\Http;

class Auphonic extends \Podlove\Modules\Base {

    protected $module_name = 'Auphonic';
    protected $module_description = 'Auphonic is an audio post production web service. This module adds an interface to episodes, so you can create and manage productions right from the publisher.';
    protected $module_group = 'external services';

    /**
     * API to Auphonic Service
     * 
     * @var Podlove\Modules\Auphonic\API_Wrapper
     */
    private $api;
	
    public function load() {

    		$this->api = new API_Wrapper($this);

    		new EpisodeEnhancer($this);

			add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
			add_action( 'wp_ajax_podlove-refresh-auphonic-presets', array( $this, 'ajax_refresh_presets' ) );

			add_action( 'wp_ajax_podlove-add-production-for-auphonic-webhook', array( $this, 'ajax_add_episode_for_auphonic_webhook' ) );
			add_action( 'wp', array( $this, 'auphonic_webhook' ) );
    		
			if( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'check_code'));
    		}    		

    		if ( $this->get_module_option('auphonic_api_key') == "" ) {
    			$auth_url = "https://auphonic.com/oauth2/authorize/?client_id=0e7fac528c570c2f2b85c07ca854d9&redirect_uri=" . urlencode(get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle') . "&response_type=code";
	    		$description = '<i class="podlove-icon-remove"></i> '
	    		             . __( 'You need to allow Podlove Publisher to access your Auphonic account. You will be redirected to this page once the auth process completed.', 'podlove' )
	    		             . '<br><a href="' . $auth_url . '" class="button button-primary">' . __( 'Authorize now', 'podlove' ) . '</a>';
				$this->register_option( 'auphonic_api_key', 'hidden', array(
					'label'       => __( 'Authorization', 'podlove' ),
					'description' => $description,
					'html'        => array( 'class' => 'regular-text' )
				) );	
    		} else {
				$user = $this->api->fetch_authorized_user();
    			if ( isset($user) && is_object($user) && is_object($user->data) ) {
					$description = '<i class="podlove-icon-ok"></i> '
								 . sprintf(
									__( 'You are logged in as %s. If you want to logout, click %shere%s.', 'podlove' ),
									'<strong>' . $user->data->username . '</strong>',
									'<a href="' . admin_url( 'admin.php?page=podlove_settings_modules_handle&reset_auphonic_auth_code=1' ) . '">',
									'</a>'
								);
				} else {
					$description = '<i class="podlove-icon-remove"></i> '
								 . sprintf(
									__( 'Something went wrong with the Auphonic connection. Please reset the connection and authorize again. To do so click %shere%s', 'podlove' ),
									'<a href="' . admin_url( 'admin.php?page=podlove_settings_modules_handle&reset_auphonic_auth_code=1' ) . '">',
									'</a>'
								);
				}

				$this->register_option( 'auphonic_api_key', 'hidden', array(
					'label'       => __( 'Authorization', 'podlove' ),
					'description' => $description,
					'html'        => array( 'class' => 'regular-text' )
				) );	
				
				// Fetch Auphonic presets
				$presets = $this->api->fetch_presets();
				if ( $presets && is_array( $presets->data ) ) {
					$preset_list = array();
					foreach( $presets->data as $preset_id => $preset ) {
						$preset_list[ $preset->uuid ] = $preset->preset_name;
					}
				} else {
					$preset_list[] = __( 'Presets could not be loaded', 'podlove' );
				}
					
				$this->register_option( 'auphonic_production_preset', 'select', array(
					'label'       => __( 'Auphonic production preset', 'podlove' ),
					'description' => '<span class="podlove_auphonic_production_refresh"><i class="podlove-icon-repeat"></i></span> This preset will be used, if you create Auphonic production from an Episode.',
					'html'        => array( 'class' => 'regular-text' ),
					'options'	  => $preset_list
				) );
    		
    		}
    }

    /**
     * Register Event for Auphonic Webhook
     */
    public function auphonic_webhook() {
    	$auth_key = 0;
    	$action = 'update';

    	if ( !isset( $_REQUEST['podlove-auphonic-production'] ) || empty( $_REQUEST['podlove-auphonic-production'] ) || empty( $_POST ) )
    		return;

    	$episodes_to_be_remote_published = get_option( 'podlove_episodes_to_be_remote_published' );

    	if ( !is_array( $episodes_to_be_remote_published ) )
    		return;

    	foreach ( $episodes_to_be_remote_published as $episode ) {
    		if ( $episode['post_id'] == $_REQUEST['podlove-auphonic-production'] ) {
    			$auth_key = $episode['auth_key'];
    			$action = $episode['action'];
    		}
    	}

    	if ( $_POST['status_string'] !== 'Done' )
    		return;

    	if ( $_REQUEST['authkey'] !== $auth_key )
    		return;
    	
    	// Update episode with production results
    	$this->update_production_data( $_REQUEST['podlove-auphonic-production'] );

    	if ( $action == 'publish' )
    		wp_publish_post( $_REQUEST['podlove-auphonic-production'] );
    }

    /** 
     * Updates Episode production data after Auphonic production has finished.
     * Basically, this is like pushing the "Get Production Results" button.
     */
    public function update_production_data( $post_id ) {
    	$episode = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );

    	$metadata = array(
    			'title' => get_the_title( $post_id ),
    			'subtitle' => $episode->subtitle,
    			'summary' => $episode->summary,
    			'duration' => $episode->duration,
    			'chapters' => $episode->chapters,
    			'slug' => $episode->slug,
    			'license' => $episode->license,
    			'license_url' => $episode->license_url,
    			'tags' => implode( ',', array_map( function( $tag ) {
    				return $tag->name;
    			}, wp_get_post_tags( $post_id ) ) )
    		);

    	$auphonic_metadata = array(
    			'title' => $_POST['metadata']['title'],
    			'subtitle' => $_POST['metadata']['subtitle'],
    			'summary' => $_POST['metadata']['summary'],
    			'duration' => $_POST['length_timestring'],
    			'chapters' => $this->convert_chapters_to_string( $_POST['chapters'] ),
    			'slug' => $_POST['output_basename'],
    			'license' => $_POST['metadata']['license'],
    			'license_url' => $_POST['metadata']['license_url'],
    			'tags' => implode( ',', $_POST['metadata']['tags'] )
    		);

    	// Merge both arrays
    	foreach ( $metadata as $metadata_key => $metadata_entry ) {
    		if ( is_null( $metadata_entry ) || empty( $metadata_entry ) )
    			$metadata[$metadata_key] = $auphonic_metadata[$metadata_key];
     	}

     	$episode->subtitle = $metadata['subtitle'];
     	$episode->summary = $metadata['summary'];
     	$episode->duration = $metadata['duration'];
     	$episode->chapters = $metadata['chapters'];
     	$episode->slug = $metadata['slug'];
     	$episode->license = $metadata['license'];
     	$episode->license_url = $metadata['license_url'];
     	$episode->save();

     	wp_update_post( array( 
     			'ID' => $post_id,
     			'post_title' => $metadata['title']
     		 ) );
     	wp_set_post_tags( $post_id, $metadata['tags'] );

    }

    public function convert_chapters_to_string( $chapters ) {
    	if ( !is_array( $chapters ) )
    		return;

    	$chapters_string = "";

    	foreach ( $chapters as $chapter ) {

    		$chapters_string .= $chapter['start_output'] . ' ';
    		$chapters_string .= $chapter['title'];

    		if ( !empty( $chapter['url'] ) )
    			$chapters_string = $chapters_string . ' <' . $chapter['url'] . '>';

    		$chapters_string .= "\n";
    	}

    	return $chapters_string;
    }

    /**
     * Refresh the list of auphonic presets
     */
    public function ajax_refresh_presets() {
		delete_transient('podlove_auphonic_presets');
		$result = $this->api->fetch_presets();
		
		return \Podlove\AJAX\AJAX::respond_with_json( $result );
	}

	/**
	 * Register a new Episode that can be published via Auphonic
	 */
	public function ajax_add_episode_for_auphonic_webhook() {
		$post_id = $_REQUEST['post_id'];
		$auth_key = $_REQUEST['authkey'];
		$action = $_REQUEST['flag'];

		if ( !$post_id || !$action || !$auth_key )
			return \Podlove\AJAX\AJAX::respond_with_json( false );

		$episodes_to_be_remote_published = get_option( 'podlove_episodes_to_be_remote_published' );

		if ( !is_array( $episodes_to_be_remote_published ) )
			$episodes_to_be_remote_published = array();
		
		if ( !in_array( $post_id , $episodes_to_be_remote_published ) ) {
			$episodes_to_be_remote_published[] = array(
					'post_id' => $post_id,
					'auth_key' => $auth_key,
					'action' => $action
				);
			update_option( 'podlove_episodes_to_be_remote_published', $episodes_to_be_remote_published );
		}

		return \Podlove\AJAX\AJAX::respond_with_json( true );
	}

    /**
     * Fetch name of logged in user via Auphonic API.
     *
     * Cached in transient "podlove_auphonic_user".
     * 
     * @return string
     */
    public function fetch_authorized_user() {
    	$cache_key = 'podlove_auphonic_user';

    	if ( ( $user = get_transient( $cache_key ) ) !== FALSE ) {
    		return $user;
    	} else {
	    	if ( ! ( $token = $this->get_module_option('auphonic_api_key') ) )
	    		return "";

	    	$curl = new Http\Curl();
	    	$curl->request( 'https://auphonic.com/api/user.json', array(
	    		'headers' => array(
	    			'Content-type'  => 'application/json',
	    			'Authorization' => 'Bearer ' . $this->get_module_option('auphonic_api_key')
	    		)
	    	) );
	    	$response = $curl->get_response();

    		if ($curl->isSuccessful()) {
				$decoded_user = json_decode( $response['body'] );
				$user = $decoded_user ? $decoded_user : FALSE;
				set_transient( $cache_key, $user, 60*60*24*365 ); // 1 year, we devalidate manually
    	    	return $user;
    		} else {
    			return false;
    		}
    	}
    }

    /**
     * Fetch list of presets via Auphonic APU.
     *
     * Cached in transient "podlove_auphonic_presets".
     * 
     * @return string
     */
    public function fetch_presets() {
    	$cache_key = 'podlove_auphonic_presets';

    	if ( ( $presets = get_transient( $cache_key ) ) !== FALSE ) {
    		return $presets;
    	} else {
	    	if ( ! ( $token = $this->get_module_option('auphonic_api_key') ) )
	    		return "";

    		$curl = new Http\Curl();
    		$curl->request( 'https://auphonic.com/api/presets.json', array(
    			'headers' => array(
    				'Content-type'  => 'application/json',
    				'Authorization' => 'Bearer ' . $this->get_module_option('auphonic_api_key')
    			)
    		) );
			$response = $curl->get_response();

			if ($curl->isSuccessful()) {
		    	$presets = json_decode( $response['body'] );
		    	set_transient( $cache_key, $presets, 60*60*24*365 ); // 1 year, we devalidate manually
		    	return $presets;
			} else {
				return array();
			}

    	}
    }

    public function save_post( $post_id ) {
    	
    	if ( get_post_type( $post_id ) !== 'podcast' )
    		return;

    	if ( ! current_user_can( 'edit_post', $post_id ) )
	        return;

	    if ( isset( $_REQUEST['_auphonic_production'] ) )
	    	update_post_meta( $post_id, '_auphonic_production', $_REQUEST['_auphonic_production'] );
    }

    public function admin_print_styles() {

    	$screen = get_current_screen();
    	if ( $screen->base != 'post' && $screen->post_type != 'podcast' && $screen->base != 'podlove_page_podlove_settings_modules_handle' )
    		return;

    	wp_register_style(
    		'podlove_auphonic_admin_style',
    		$this->get_module_url() . '/admin.css',
    		false,
    		\Podlove\get_plugin_header( 'Version' )
    	);
    	wp_enqueue_style('podlove_auphonic_admin_style');

    	wp_register_script(
    		'podlove_auphonic_admin_script',
    		$this->get_module_url() . '/admin.js',
    		array( 'jquery', 'jquery-ui-tabs' ),
    		\Podlove\get_plugin_header( 'Version' )
    	);
    	wp_enqueue_script('podlove_auphonic_admin_script');
    }
    
    public function check_code() { 
    	if ( isset( $_GET["code"] ) && $_GET["code"] ) {
    		if($this->get_module_option('auphonic_api_key') == "") {
				$ch = curl_init('https://auth.podlove.org/auphonic.php');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
				curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(  
					   "redirect_uri" => get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle',                                                                      
					   "code" => $_GET["code"]));                                                              
			
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  

				// verify against startssl crt
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_CAINFO, \Podlove\PLUGIN_DIR . '/cert/podlove.crt');

				$result = curl_exec($ch);
						
				$this->update_module_option('auphonic_api_key', $result);
				header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
			}
    	}
    	
    	if ( isset( $_GET["reset_auphonic_auth_code"] ) && $_GET["reset_auphonic_auth_code"] == "1" ) {
    		$this->update_module_option('auphonic_api_key', "");
    		delete_transient('podlove_auphonic_user');
    		delete_transient('podlove_auphonic_presets');
    		header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
    	}
    	
    }

}