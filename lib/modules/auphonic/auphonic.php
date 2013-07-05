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
				add_action( 'podlove_episode_form_beginning', array( $this, 'create_auphonic_production' ), 10, 2 );    			
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

				$decoded_user_information = json_decode(curl_exec($ch));
    		
    			if(isset($decoded_user_information) AND $decoded_user_information !== "") {
					$description = '<i class="podlove-icon-ok"></i> '
								 . sprintf(
									__( 'You are logged in as <strong>'.$decoded_user_information->data->username.'</strong>. If you want to logout, click %shere%s.', 'podlove' ),
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
				
    			$ch = curl_init('https://auphonic.com/api/presets.json');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");       
				curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());                                                              
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                     
					'Content-type: application/json',                                     
					'Authorization: Bearer '.$this->get_module_option('auphonic_api_key'))                                                                       
				);                                                              
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  	
				
				$decoded_presets = json_decode(curl_exec($ch));
				$preset_list = array();
				
				foreach($decoded_presets->data as $preset_id => $preset_information) {
					$preset_list[$preset_information->uuid] = $preset_information->preset_name;
				}
				
				$this->register_option( 'auphonic_production_preset', 'select', array(
				'label'       => __( 'Auphonic production preset', 'podlove' ),
				'description' => 'This preset will be used, if you create Auphonic production from an Episode.',
				'html'        => array( 'class' => 'regular-text' ),
				'options'	  => $preset_list
				) );
    		
    		}
    }
    
    public function auphonic_episodes( $wrapper, $episode ) {
    	$wrapper->callback( 'import_from_auphonic_form', array(
			'label'    => __( 'Import Episode data from Auphonic', 'podlove' ),
			'callback' => array( $this, 'auphonic_episodes_form' )
		) );			
    }
    
    public function create_auphonic_production( $wrapper ) {
    	$wrapper->callback( 'create_auphonic_production_form', array(
			'label'    => __( 'Create Auphonic production from Episode', 'podlove' ),
			'callback' => array( $this, 'create_auphonic_production_form' )
		) );			
    }
    
    public function create_auphonic_production_form() {
    	$asset_assignments = Model\AssetAssignment::get_instance();
    ?>
    
    	<span class='description'>
		<?php	
		if ( $asset_assignments->chapters == 'manual' ) {
			echo __( "Title, subtitle, summary, duration, year, episode media file slug and chapters will be will be added to the created Auphonic production.", 'podlove' );
		} else {
			echo __( "Title, subtitle, summary, duration, year and episode media file slug will be will be added to the created Auphonic production.", 'podlove' );
		}
		?>
		</span>
		
		<div id="create-auphonic-production-form">
			<div class="auphonic-select-wrapper">
				<div class="auphonic-button-wrapper">
					<a class='button' id='create_auphonic_production_button' class='button' data-token='<?php echo $this->get_module_option('auphonic_api_key') ?>' data-presetuuid='<?php echo $this->get_module_option('auphonic_production_preset') ?>'>
						Create Auphonic Production
						<div>
							<span id="fetch_create_production_status"></span>
						</div>
					</a>
				</div>
				<span id="new_created_episode_data"></span>
			</div>
			<div style="clear: both"></div>
		</div>
	<?php
    }

    public function auphonic_episodes_form() {

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
				
				/**
				 * Create Auphonic production.
				 */				
				 function create_auphonic_production(token) {
				 	var presetuuid = $("#create_auphonic_production_button").data('presetuuid');
				 	var chapter_asset_assignment = "<?php echo $asset_assignments->chapters ?>";
				 	var cover_art_asset_assignment = "<?php echo $asset_assignments->image ?>";
				 	var module_url = "<?php echo $this->get_module_url(); ?>";
				 	var auphonic_production_data = new Object();
				 	var auphonic_production_metadata = new Object();
				 	var auphonic_files = new Object();
				 	
				 	var raw_production_tags = $(".tagchecklist").text();
				 	var raw_chapters = $("#_podlove_meta_chapters").val();
				 	
				 	var now = new Date();
				 	var chapters = new Array();
				 	
				 	$("#fetch_create_production_status").html('<i class="podlove-icon-spinner rotate"></i>').show();
				 	
				 	if(typeof presetuuid !== undefined && presetuuid !== "") {
				 		auphonic_production_data.preset = presetuuid;
				 	}
				 	
				 	auphonic_production_data.length_timestring = $("#_podlove_meta_duration").val();
				 	auphonic_production_data.output_basename= $("#_podlove_meta_slug").val();
				 	auphonic_production_metadata.title = $("#title").val();
				 	auphonic_production_metadata.subtitle = $("#_podlove_meta_subtitle").val();
				 	auphonic_production_metadata.summary = $("#_podlove_meta_summary").val();
				 	auphonic_production_metadata.year = now.getFullYear();
				 	/* auphonic_production_metadata.tags = raw_production_tags.substring(2, raw_production_tags.length).split('X\u00a0'); */
				 		
				 	if(typeof chapter_asset_assignment !== 'undefined') {
				 		if (chapter_asset_assignment == 'manual' && raw_chapters !== "") {
				 			$(raw_chapters.split('\n')).each(function (index, value) {
				 				if(value !== "\n" && value !== "") {
									var chapter = new Object();
									chapter.start = value.substring(0, value.indexOf(" "));
									if(value.indexOf("<") == -1) {
										chapter.title = value.substring(value.indexOf(" ") + 1, value.length);
										chapter.url = "";
									} else {
										chapter.title = value.substring(value.indexOf(" ") + 1, value.lastIndexOf(" "));
										chapter.url = value.substring(value.lastIndexOf(" "), value.length).substring(2, value.substring(value.lastIndexOf(" "), value.length).length - 1);
									}
									chapters[index] = chapter;
									delete chapter;
								}
				 			});
				 			auphonic_production_data.chapters = chapters;
				 		}
				 	}
								 		
				 	auphonic_production_data.metadata = auphonic_production_metadata;

					$.post(module_url + "/create_auphonic_production.php?access_token=" + token, { data: JSON.stringify(auphonic_production_data) }).always(function(data) {
						console.log(data);
						var new_episode_data = data.data;						
						$("#fetch_create_production_status")
							.html('<i class="podlove-icon-ok"></i>')
							.delay(250)
							.fadeOut(500);
						$("#new_created_episode_data")
							.html('<i>The production was successfully created. You can edit this episode <a href="https://auphonic.com/engine/upload/edit/' + new_episode_data.uuid + '" target="_blank">here</a>.')
							.delay(250);
						delete new_episode_data;
						fetch_episodes(token);
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
					}).done(function() {
						$("#fetch_production_status")
							.html('<i class="podlove-icon-ok"></i>')
							.delay(250)
							.fadeOut(500);
					});
				}
				
				function fetch_episodes(token) {
					$("#reload_episodes_status").html('<i class="podlove-icon-spinner rotate"></i>').show();				
					var module_url = "<?php echo $this->get_module_url(); ?>";
					var productions = $.getJSON(module_url + '/fetch_episodes.php?access_token=' + token, function(data) {
						var production_list = new Array();
						var auphonic_productions = data.data;
						$("#import_from_auphonic").empty();
						$(auphonic_productions).each(function(key, value) {				
							var date = new Date(value.change_time);				
							$("#import_from_auphonic").append('<option value="' + value.uuid + '">' + value.output_basename + ' (' + date.getFullYear() + '-' + ("0" + (date.getMonth() + 1)).slice(-2) + '-' + ("0" + (date.getDay() + 1)).slice(-2) + ' ' + date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds() + ') [' + value.status_string + ']</option>\n');
						});
						
						delete auphonic_productions;
					}).done(function() {
						$("#reload_episodes_status")
							.html('<i class="podlove-icon-ok"></i>')
							.delay(250)
							.fadeOut(500);
					});
				}

				$("#fetch_production_data_button").click(function () {
					fetch_production_data($(this).data('token'));
				});
				
				$("#create_auphonic_production_button").click(function () {
					create_auphonic_production($(this).data('token'));
				});
				
				$("#reload_productions_button").click(function () {
					fetch_episodes($(this).data('token'));
				});
				
				$("#open_production_button").click(function () {
					window.open('https://auphonic.com/engine/upload/edit/' + $("#import_from_auphonic").find(":selected").val());
				});
				
				$(document).ready(function() {
					fetch_episodes('<?php echo $this->get_module_option('auphonic_api_key') ?>');
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
		#auphonic-import-form, #create-auphonic-production-form {
			line-height: 24px;
			padding-top: 5px;
		}

		.auphonic-select-wrapper {
			float: left;
			margin-right: 10px;
		}
		
		.auphonic-select-wrapper label {
			display: inline-block;
			margin-right: 5px;
			vertical-align: baseline;
		}
		
		#import_from_auphonic {
			width: 280px;
		}

		.auphonic-button-wrapper { float: left;	}

		#fetch_production_data_button, #create_auphonic_production_button {
			padding-left: 15px;
			margin-right: 5px;
		}

		#fetch_production_data_button > div, #create_auphonic_production_button > div {
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
				<label for="import_from_auphonic">Production</label>
				<select name="import_from_auphonic" id="import_from_auphonic">
					<option value="<?php echo $production_data->uuid ?>">

					</option>
				</select>
			</div>
			
			<div class="auphonic-button-wrapper" style="float: left; margin-right: 10px;">
				<a class='button' id='reload_productions_button' class='button' data-token='<?php echo $this->get_module_option('auphonic_api_key') ?>'>
					&#x21bb; <span id="reload_episodes_status"></span>
				</a>
			</div>
			
			<div class="auphonic-button-wrapper" style="float: left; margin-right: 10px;">
				<a class='button' id='open_production_button' class='button' data-token='<?php echo $this->get_module_option('auphonic_api_key') ?>'>
					Open in Auphonic
				</a>
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
				<label for='force_import_from_auphonic' title="<?php echo __( 'Overwrite all fields, even if they are already filled out.', 'podlove' ) ?>"><?php echo __( 'Overwrite existing content', 'podlove' ) ?></label>
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