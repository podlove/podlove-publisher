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
    		    		
    		if ( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
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
     * Refresh the list of auphonic presets
     */
    public function ajax_refresh_presets() {
		delete_transient('podlove_auphonic_presets');
		$result = $this->api->fetch_presets();
		
		return \Podlove\AJAX\AJAX::respond_with_json( $result );
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