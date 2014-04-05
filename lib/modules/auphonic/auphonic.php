<?php 
namespace Podlove\Modules\Auphonic;
use \Podlove\Model;
use \Podlove\Http;

class Auphonic extends \Podlove\Modules\Base {

    protected $module_name = 'Auphonic';
    protected $module_description = 'Import Episode data from an Auphonic production';
    protected $module_group = 'external services';
	
    public function load() {

			add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
			add_action( 'wp_ajax_podlove-refresh-auphonic-presets', array( $this, 'ajax_refresh_presets' ) );
    		
    		if($this->get_module_option('auphonic_api_key') == "") { } else {
    			add_action( 'podlove_episode_form_beginning', array( $this, 'auphonic_episodes' ), 10, 2 );
				// add_action( 'podlove_episode_form_beginning', array( $this, 'create_auphonic_production' ), 10, 2 );    			
    		}
    		
			if( isset( $_GET["page"] ) && $_GET["page"] == "podlove_settings_modules_handle") {
    			add_action('admin_bar_init', array( $this, 'check_code'));
    		}    		
    		
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
				$user = $this->fetch_authorized_user();
    			if( isset($user) AND $user !== "" ) {
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
				$presets = $this->fetch_presets();
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

    		add_action( 'save_post', array( $this, 'save_post' ) );
    }

    /**
     * Refresh the list of auphonic presets
     */
    public function ajax_refresh_presets() {
		delete_transient('podlove_auphonic_presets');
		$result = $this->fetch_presets();
		
		return \Podlove\AJAX\AJAX::respond_with_json( $result );
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
    
    public function auphonic_episodes( $wrapper, $episode ) {
    	$wrapper->callback( 'import_from_auphonic_form', array(
			'label'    => __( 'Auphonic', 'podlove' ),
			'callback' => array( $this, 'auphonic_episodes_form' )
		) );			
    }

    public function auphonic_episodes_form() {
		$asset_assignments = Model\AssetAssignment::get_instance();
		?>

		<input type="hidden" id="_auphonic_production" name="_auphonic_production" value="<?php echo get_post_meta( get_the_ID(), '_auphonic_production', true ) ?>" />
		<input type="hidden" id="auphonic" value="1"
			data-api-key="<?php echo $this->get_module_option('auphonic_api_key') ?>"
			data-presetuuid="<?php echo $this->get_module_option('auphonic_production_preset') ?>"
			data-assignment-chapter="<?php echo $asset_assignments->chapters ?>"
			data-assignment-image="<?php echo $asset_assignments->image ?>"
			data-module-url="<?php echo $this->get_module_url() ?>"
			/>

		<div id="auphonic-box">

			<div id="auphonic-production-status" class="auphonic-status status-info"></div>

			<fieldset>
				<legend>Create Production</legend>
				<div class="auphonic-segment">
					<div class="auphonic_production_head">
						<label for="auphonic_services">
							Source
						</label>
					</div>
					<select id="auphonic_services">
						<option><?php echo __( 'Loading sources ...' ) ?></option>
					</select>
				</div>
				
				<div class="auphonic-segment">
					<div class="auphonic_production_head">
						<label for="auphonic_production_files">
							Master Audio File
						</label>
						<span id="fetch_auphonic_production_files" title="<?php echo __( 'Fetch available audio files.', 'podlove' ) ?>">
							<span class="state_idle"><i class="podlove-icon-repeat"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>
					</div>
					<select id="auphonic_production_files" name="input_file">
						<option>-</option>
					</select>
					<input type="text" id="auphonic_http_upload_url" name="auphonic_http_upload_url" style="display:none" class="large-text" />
					<input type="file" id="auphonic_local_upload_url" name="auphonic_local_upload_url" style="display:none" class="large-text" />
				</div>

				<div class="auphonic-row">

					<button class="button button-primary" id="create_auphonic_production_button" title="<?php echo __( 'Create a production for the selected file.', 'podlove' ) ?>">
						<span class="indicating_button_wrapper">
							<span class="state_idle"><i class="podlove-icon-plus"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>
						Create Production
					</button>

					<label>
						<input type="checkbox" id="auphonic_start_after_creation"> <?php echo __( 'Start after creation', 'podlove' ) ?>
					</label>
				</div>
			</fieldset>

			<fieldset>
				<legend>Manage Production</legend>
				<div class="auphonic-row">
						<select name="import_from_auphonic" id="auphonic_productions">
							<option><?php echo __( 'Loading productions ...', 'podlove' ) ?></option>
						</select>
						<span title="fetch available productions" id="reload_productions_button" data-token='<?php echo $this->get_module_option('auphonic_api_key') ?>'>
							<span class="state_idle"><i class="podlove-icon-repeat"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>

						<button class="button" id="open_production_button" title="<?php echo __('Open in Auphonic', 'podlove') ?>">
							<span class="indicating_button_wrapper">
								<i class="podlove-icon-share"></i>
							</span>
							Open Production
						</button>

					<div style="clear: both"></div>

				</div>

				<div id="auphonic-selected-production">
					<div class="auphonic-row">

						<button class="button button-primary" id="start_auphonic_production_button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cogs"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Start Production
						</button>

						<button class="button" id="stop_auphonic_production_button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-ban-circle"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Stop Production
						</button>

						<label>
							<input type="checkbox" id="auphonic_publish_after_finishing"> <?php echo __( 'Publish episode when done', 'podlove' ) ?>
						</label>
					</div>

					<div class="auphonic-row">
						<button id="fetch_production_results_button" class="button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cloud-download"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Get Production Results
						</button>
						<button id="fetch_production_data_button" class="button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cloud-download"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Import Episode Metadata
						</button>
					</div>
				</div>
			</fieldset>

		</div>
		<?php
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