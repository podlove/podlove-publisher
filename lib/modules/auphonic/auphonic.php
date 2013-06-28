<?php 
namespace Podlove\Modules\Auphonic;
use \Podlove\Model;

class Auphonic extends \Podlove\Modules\Base {

    protected $module_name = 'Auphonic';
    protected $module_description = 'Import Episode data from an Auphonic production';
    protected $module_group = 'external services';
	
    public function load() {
    		
    		if($this->get_module_option('auphonic_api_key') == "") { } else {
    			add_action( 'podlove_episode_form_beginning', array( $this, 'auphonic_episodes' ), 10, 2 );
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
    			$ch = curl_init('https://auphonic.com/api/user.json');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
				curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
					'Content-type: application/json',                                     
					'Authorization: Bearer '.$this->get_module_option('auphonic_api_key'))                                                                       
				);                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        

				$decoded_result = json_decode(curl_exec($ch));
    		
    			if(isset($decoded_result) AND $decoded_result !== "") {
					$description = '<i class="podlove-icon-ok"></i> '
								 . sprintf(
									__( 'You are logged in as <strong>'.$decoded_result->data->username.'</strong>. If you want to logout, click %shere%s.', 'podlove' ),
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
    		}
    }
    
    public function auphonic_episodes( $wrapper, $episode ) {
    	$wrapper->callback( 'import_from_auphonic_form', array(
			'label'    => __( 'Import Episode data from Auphonic', 'podlove' ),
			'callback' => array( $this, 'auphonic_episodes_form' )
		) );			
    }

    public function auphonic_episodes_form() {
		$ch = curl_init('https://auphonic.com/api/productions.json?limit=10');                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
		curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
			'Content-type: application/json',                                     
			'Authorization: Bearer '.$this->get_module_option('auphonic_api_key'))                                                                       
		);                                                              
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        

		$result = curl_exec($ch);

		$asset_assignments = Model\AssetAssignment::get_instance();

		?>
		<script type='text/javascript'>
		var PODLOVE = PODLOVE || {};

		(function($){

			PODLOVE.AuphonicImport = function () {

				function get_chapters_string_from_data (data) {
					var chapters_entry = "";

					$.each(data.data.chapters, function(index, value) {
						chapters_entry = chapters_entry + value.start + " " + value.title;
						if (value.url == "") {
					
						} else {
							chapters_entry = chapters_entry + " <" + value.url + ">";
						}
						chapters_entry = chapters_entry + '\n';
					});

					return chapters_entry;
				}

				function get_fields_to_update(data, chapter_asset_assignment) {
					var fields = [
						{ field: '#title'                 , value: data.data.metadata.title },
						{ field: '#_podlove_meta_subtitle', value: data.data.metadata.subtitle },
						{ field: '#_podlove_meta_summary' , value: data.data.metadata.summary },
						{ field: '#_podlove_meta_duration', value: data.data.length_timestring },
						{ field: '#_podlove_meta_slug'    , value: data.data.output_basename },
						{ field: '#new-tag-post_tag'      , value: data.data.metadata.tags.join(" , ") },
					];

					if (chapter_asset_assignment == 'manual') {
						fields.push({ field: '#_podlove_meta_chapters', value: get_chapters_string_from_data(data) });
					}

					return fields;
				}

				/**
				 * Import and override existing fields.
				 */
				function do_force_import(data, chapter_asset_assignment) {
					var fields = get_fields_to_update(data, chapter_asset_assignment);
					$.each(fields, function (index, field) {
						$(field.field).val(field.value);
					});
				}

				/**
				 * Import but do not override existing fields.
				 */
				function do_simple_import(data, chapter_asset_assignment) {
					var fields = get_fields_to_update(data, chapter_asset_assignment);
					$.each(fields, function (index, field) {
						if ($(field.field).val() == "") {
							$(field.field).val(field.value);
						}
					});
				}

				function fetch_production_data(token) {
					var uuid = $("#import_from_auphonic option:selected").val(),
					    module_url = "<?php echo $this->get_module_url(); ?>",
					    chapter_asset_assignment = "<?php echo $asset_assignments->chapters ?>";

					$("#fetch_production_status").html('<i class="podlove-icon-spinner rotate"></i>').show();
					$.getJSON(module_url + '/fetch_episode.php?uuid=' + uuid + '&access_token=' + token, function(data) {

						// hide prompt label which usually is placed above the title field
						$('#title-prompt-text').addClass('screen-reader-text');

						if (document.getElementById('force_import_from_auphonic').checked) {
							do_force_import(data, chapter_asset_assignment);
						} else {
							do_simple_import(data, chapter_asset_assignment);
						}

						// activate all assets if no asset is active
						if ($(".media_file_row input[type=checkbox]:checked").length === 0) {
							$(".media_file_row input[type=checkbox]:not(:checked)").click();
						}

						$("#fetch_production_status")
							.html('<i class="podlove-icon-ok"></i>')
							.delay(250)
							.fadeOut(500);
					});
				}

				$("#fetch_production_data_button").click(function () {
					fetch_production_data($(this).data('token'));
				});

			}
		}(jQuery));

		jQuery(function($) {
			PODLOVE.AuphonicImport();
		});
		</script>

		<span class='description'>
		<?php	
		if ( $asset_assignments->chapters == 'manual' ) {
			echo __( "Title, subtitle, summary, tags, duration, episode media file slug and chapters will be imported from Auphonic.", 'podlove' );
		} else {
			echo __( "Title, subtitle, summary, tags, duration and episode media file slug will be imported from Auphonic.", 'podlove' );
		}
		?>
		</span>

		<style type="text/css">
		#auphonic-import-form {
			line-height: 24px;
			padding-top: 5px
		}

		.auphonic-select-wrapper {
			float: left;
			margin-right: 10px;
		}

		.auphonic-button-wrapper { float: left;	}

		#fetch_production_data_button {
			padding-left: 15px;
			margin-right: 5px;
		}

		#fetch_production_data_button > div {
			display: inline-block;
			width: 5px;
		}

		.auphonic-checkbox-wrapper {
			float: left;
			text-align: left;
			margin-right: 10px;
			vertical-align: baseline;
		}

		.auphonic-checkbox-wrapper label { vertical-align: baseline; }
		.auphonic-checkbox-wrapper input { width: 18px; }
		</style>

		<div id="auphonic-import-form">

			<div class="auphonic-select-wrapper">
				<select name="import_from_auphonic" id="import_from_auphonic">
				<?php												
				foreach(json_decode($result)->data as $production_key => $production_data) {
					if($production_data->output_basename == "") {
						$displayed_name = $production_data->metadata->title;
					} else {
						$displayed_name = $production_data->output_basename;
					}
					?>
					<option value="<?php echo $production_data->uuid ?>">
						Production: <?php echo $displayed_name." (".date( "Y-m-d H:i:s", strtotime($production_data->creation_time)).") [".$production_data->status_string."]"; ?>
					</option>
					<?php
				}
				?>
				</select>
			</div>

			<div class="auphonic-button-wrapper" style="float: left">
				<a class='button' id='fetch_production_data_button' class='button' data-token='<?php echo $this->get_module_option('auphonic_api_key') ?>'>
					Import from Auphonic
					<div>
						<span id="fetch_production_status"></span>
					</div>
				</a>
			</div>

			<div class="auphonic-checkbox-wrapper">
				<input type='checkbox' id='force_import_from_auphonic'/>
				<label for='force_import_from_auphonic' title="<?php echo __( 'Overwrite all fields, even if they are already filled out.', 'podlove' ) ?>"><?php echo __( 'Force import', 'podlove' ) ?></label>
			</div>

			<div style="clear: both"></div>
		</div>
		<?php
    }
    
    public function check_code() { 
    	if( isset( $_GET["code"] ) && $_GET["code"] ) {
    		if($this->get_module_option('auphonic_api_key') == "") {
				$ch = curl_init('http://auth.podlove.org/auphonic.php');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");       
				curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(  
					   "redirect_uri" => get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle',                                                                      
					   "code" => $_GET["code"]));                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
			
				$result = curl_exec($ch);
						
				$this->update_module_option('auphonic_api_key', $result);
				header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
			}
    	}
    	
    	if ( isset( $_GET["reset_auphonic_auth_code"] ) && $_GET["reset_auphonic_auth_code"] == "1" ) {
    		$this->update_module_option('auphonic_api_key', "");
    		header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
    	}
    	
    }
    
}