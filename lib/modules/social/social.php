<?php 
namespace Podlove\Modules\Social;

use \Podlove\Modules\Social\Model\Service;
use \Podlove\Modules\Social\Model\ShowService;
use \Podlove\Modules\Social\Model\ContributorService;

use \Podlove\Modules\Social\Settings\PodcastSettingsTab;

class Social extends \Podlove\Modules\Base {

	protected $module_name = 'Social';
	protected $module_description = 'Manage social media accounts.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_module_was_activated_social', array( $this, 'was_activated' ) );
		add_action( 'podlove_podcast_settings_tabs', array( $this, 'podcast_settings_tab' ) );

		add_action( 'update_option_podlove_podcast', array( $this, 'save_setting' ), 10, 2 );
		add_action( 'update_podlove_contributor', array( $this, 'save_contributor' ), 10, 2 );

		add_action( 'podlove_contributors_form_end', array( $this, 'services_form_for_contributors' ), 10, 2 );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		add_filter( "manage_podcast_page_podlove_contributors_settings_handle_columns", array( $this, 'add_new_contributor_column' ) );
	}

	public function was_activated( $module_name ) {
		Service::build();
		ShowService::build();
		ContributorService::build();

		$services = array(
			array(
					'title' 		=> 'App.net',
					'description'	=> 'App.net Account',
					'logo'			=> 'adn-128.png',
					'url_scheme'	=> 'https://alpha.app.net/%account-placeholder%'
				),
			array(
					'title' 		=> 'Bandcamp',
					'description'	=> 'Bandcamp URL',
					'logo'			=> 'bandcamp-128.png',
					'url_scheme'	=> '%account-placeholder%'
				),
			array(
					'title' 		=> 'Bitbucket',
					'description'	=> 'Bitbucket Account',
					'logo'			=> 'bitbucket-128.png',
					'url_scheme'	=> 'https://bitbucket.org/%account-placeholder%'
				),
			array(
					'title' 		=> 'DeviantART',
					'description'	=> 'DeviantART Account',
					'logo'			=> 'deviantart-128.png',
					'url_scheme'	=> 'https://%account-placeholder%.deviantart.com/'
				),
			array(
					'title' 		=> 'Dribbble',
					'description'	=> 'Dribbble Account',
					'logo'			=> 'dribbble-128.png',
					'url_scheme'	=> 'https://dribbble.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'Facebook',
					'description'	=> 'Facebook Account',
					'logo'			=> 'facebook-128.png',
					'url_scheme'	=> 'https://facebook.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'Flickr',
					'description'	=> 'Flickr Account',
					'logo'			=> 'flickr-128.png',
					'url_scheme'	=> 'https://secure.flickr.com/photos/%account-placeholder%'
				),
			array(
					'title' 		=> 'GitHub',
					'description'	=> 'GitHub Account',
					'logo'			=> 'github-128.png',
					'url_scheme'	=> 'https://github.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'Google+',
					'description'	=> 'Google+ URL',
					'logo'			=> 'googleplus-128.png',
					'url_scheme'	=> '%account-placeholder%'
				),
			array(
					'title' 		=> 'Instagram',
					'description'	=> 'Instagram Account',
					'logo'			=> 'instagram-128.png',
					'url_scheme'	=> 'https://http://instagram.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'Linkedin',
					'description'	=> 'Linkedin URL',
					'logo'			=> 'linkedin-128.png',
					'url_scheme'	=> '%account-placeholder%'
				),
			array(
					'title' 		=> 'Pinboard',
					'description'	=> 'Pinboard Account',
					'logo'			=> 'pinboard-128.png',
					'url_scheme'	=> 'https://pinboard.in/u:%account-placeholder%'
				),
			array(
					'title' 		=> 'Pinterest',
					'description'	=> 'Pinterest Account',
					'logo'			=> 'pinterest-128.png',
					'url_scheme'	=> 'https://www.pinterest.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'Soundcloud',
					'description'	=> 'Soundcloud Account',
					'logo'			=> 'soundcloud-128.png',
					'url_scheme'	=> 'https://soundcloud.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'Tumblr',
					'description'	=> 'Tumblr Account',
					'logo'			=> 'tumblr-128.png',
					'url_scheme'	=> 'https://%account-placeholder%.tumblr.com/'
				),
			array(
					'title' 		=> 'Twitter',
					'description'	=> 'Twitter Account',
					'logo'			=> 'twitter-128.png',
					'url_scheme'	=> 'https://twitter.com/%account-placeholder%'
				),
			array(
					'title' 		=> 'WWW',
					'description'	=> 'Website URL',
					'logo'			=> 'www-128.png',
					'url_scheme'	=> '%account-placeholder%'
				),
			array(
					'title' 		=> 'Xing',
					'description'	=> 'Xing URL',
					'logo'			=> 'xing-128.png',
					'url_scheme'	=> '%account-placeholder%'
				),
			array(
					'title' 		=> 'YouTube',
					'description'	=> 'YouTube Account',
					'logo'			=> 'youtube-128.png',
					'url_scheme'	=> 'https://www.youtube.com/user/%account-placeholder%'
				)
		);

		if( count(Service::all()) == 0 ) {
			foreach ($services as $service_key => $service) {
				$c = new \Podlove\Modules\Social\Model\Service;
				$c->title = $service['title'];
				$c->description = $service['description'];
				$c->logo = $service['logo'];
				$c->url_scheme = $service['url_scheme'];
				$c->save();
			}
		}

		if( count(ContributorService::all()) == 0 ) {

			$www_service			= \Podlove\Modules\Social\Model\Service::find_one_by_where("`title` = 'WWW'");
			$adn_service			= \Podlove\Modules\Social\Model\Service::find_one_by_where("`title` = 'App.net'");
			$twitter_service		= \Podlove\Modules\Social\Model\Service::find_one_by_where("`title` = 'Twitter'");
			$googleplus_service 	= \Podlove\Modules\Social\Model\Service::find_one_by_where("`title` = 'Google+'");
			$facebook_service		= \Podlove\Modules\Social\Model\Service::find_one_by_where("`title` = 'Facebook'");

			if (\Podlove\Modules\Base::is_active('contributors')) {
				$contributors = \Podlove\Modules\Contributors\Model\Contributor::all();

				foreach ($contributors as $contributor) {

					$position = 0;
					
					if( !is_null($contributor->www) ) {
						$c = new \Podlove\Modules\Social\Model\ContributorService;
						$c->contributor_id = $contributor->id;
						$c->service_id = $www_service->id;
						$c->value = $contributor->www;
						$c->position = $position;
						$c->save();
						$position++;
					}

					if( !is_null($contributor->adn) ) {
						$c = new \Podlove\Modules\Social\Model\ContributorService;
						$c->contributor_id = $contributor->id;
						$c->service_id = $adn_service->id;
						$c->value = $contributor->adn;
						$c->position = $position;
						$c->save();
						$position++;
					}

					if( !is_null($contributor->twitter) ) {
						$c = new \Podlove\Modules\Social\Model\ContributorService;
						$c->contributor_id = $contributor->id;
						$c->service_id = $twitter_service->id;
						$c->value = $contributor->twitter;
						$c->position = $position;
						$c->save();
						$position++;
					}

					if( !is_null($contributor->googleplus) ) {
						$c = new \Podlove\Modules\Social\Model\ContributorService;
						$c->contributor_id = $contributor->id;
						$c->service_id = $googleplus_service->id;
						$c->value = $contributor->googleplus;
						$c->position = $position;
						$c->save();
						$position++;
					}

					if( !is_null($contributor->facebook) ) {
						$c = new \Podlove\Modules\Social\Model\ContributorService;
						$c->contributor_id = $contributor->id;
						$c->service_id = $facebook_service->id;
						$c->value = $contributor->facebook;
						$c->position = $position;
						$c->save();
						$position++;
					}				

				}
			}
		}

	}

	public function save_contributor( $contributor ) {
		if (!isset($_POST['podlove_contributor']) )
			return;

		$services_appearances = $_POST['podlove_contributor']['services'];

		foreach (\Podlove\Modules\Social\Model\ContributorService::all("WHERE `contributor_id` = " . $contributor->id) as $service) {
			$service->delete();
		}

		foreach ($services_appearances as $service_appearance) {
			foreach ($service_appearance as $service_id => $service) {
				$c = new \Podlove\Modules\Social\Model\ContributorService;
				$c->position = $position;
				$c->contributor_id = $contributor->id;
				$c->service_id = $service_id;
				$c->value = $service['value'];
				$c->title = $service['title'];
				$c->save();
			}
			$position++;
		}
	}

	public function save_setting($old, $new)
	{
		if (!isset($new['services']))
			return;

		$services_appearances = $new['services'];

		foreach (\Podlove\Modules\Social\Model\ShowService::all() as $service) {
			$service->delete();
		}

		$position = 0;
		foreach ($services_appearances as $service_appearance) {
			foreach ($service_appearance as $service_id => $service) {
				$c = new \Podlove\Modules\Social\Model\ShowService;
				$c->position = $position;
				$c->service_id = $service_id;
				$c->value = $service['value'];
				$c->title = $service['title'];
				$c->save();
			}
			$position++;
		}
	}

	public function podcast_settings_tab($tabs)
	{
		$tabs->addTab( new Settings\PodcastSettingsTab( __( 'Social', 'podlove' ) ) );
		return $tabs;
	}

	public function add_new_contributor_column($columns)
	{
			$keys = array_keys($columns);
		    $insertIndex = array_search('gender', $keys) + 1; // after author column

		    // insert contributors at that index
		    $columns = array_slice($columns, 0, $insertIndex, true) +
		           array("social" => __('Social', 'podlove')) +
			       array_slice($columns, $insertIndex, count($columns) - 1, true);

		    return $columns;
	}

	public function services_form_for_contributors($wrapper) {

		$wrapper->subheader( __( 'Social', 'podlove' ) );

		$wrapper->callback( 'services_form_table', array(
			'callback' => function() {

				$services = \Podlove\Modules\Social\Model\ContributorService::all("WHERE `contributor_id` = " . $_GET['contributor'] . " ORDER BY `position` ASC");

				echo '</table>';
				\Podlove\Modules\Social\Social::services_form_table($services);
				echo '<table class="form-table">';
			}
		) );
	}

	public static function services_form_table($current_services = array(), $form_base_name = 'podlove_contributor[services]') {
		$cjson = array();
		$converted_services = array();

		foreach (\Podlove\Modules\Social\Model\Service::all() as $service) {
			$cjson[$service->id] = array(
				'id'   			=> $service->id,
				'title'   		=> $service->title,
				'description'   => $service->description,
				'url_scheme'   	=> $service->url_scheme				
			);			
		}

		foreach ($current_services as $current_service_key => $service) {
			$converted_services[$service->id] = array(
				'id'   			=> $service->service_id,
				'value'   		=> $service->value,
				'title'   		=> $service->title
			);
		}
		
		?>
		<div id="services-form">
			<table class="podlove_alternating" border="0" cellspacing="0">
				<thead>
					<tr>
						
						<th>Service</th>
						<th>Account/URL</th>
						<th>Title</th>
						<th style="width: 60px">Remove</th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody id="services_table_body" style="min-height: 50px;">
					<tr class="services_table_body_placeholder" style="display: none;">
						<td><em><?php echo __('No Services were added yet.', 'podlove') ?></em></td>
					</tr>
				</tbody>
			</table>

			<div id="add_new_contributor_wrapper">
				<input class="button" id="add_new_service_button" value="+" type="button" />
			</div>

			<script type="text/template" id="service-row-template">
			<tr class="media_file_row podlove-service-table" data-contributor-id="{{service-id}}">
				
				<td class="podlove-service-column">
					<select name="<?php echo $form_base_name ?>[{{id}}][{{service-id}}][id]" class="chosen-image podlove-service-dropdown">
						<option value=""><?php echo __('Choose Service', 'podlove') ?></option>
						<?php foreach ( \Podlove\Modules\Social\Model\Service::all() as $service ): ?>
							<option value="<?php echo $service->id ?>" data-img-src="<?php echo $service->get_logo() ?>"><?php echo $service->title; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td>
					<input type="text" name="<?php echo $form_base_name ?>[{{id}}][{{service-id}}][value]" class="podlove-service-value" />
					<i class="podlove-icon-share podlove-service-link"></i>
				</td>
				<td>
					<input type="text" name="<?php echo $form_base_name ?>[{{id}}][{{service-id}}][title]" class="podlove-service-title" />
				</td>
				<td>
					<span class="service_remove">
						<i class="clickable podlove-icon-remove"></i>
					</span>
				</td>
				<td class="move column-move"><i class="reorder-handle podlove-icon-reorder"></i></td>
			</tr>
			</script>

			<script type="text/javascript">
				var PODLOVE = PODLOVE || {};
				var i = 0;
				var existing_services = <?php echo json_encode($converted_services); ?>;

				PODLOVE.Services = <?php echo json_encode(array_values($cjson)); ?>;
				PODLOVE.Services_form_base_name = "<?php echo $form_base_name ?>";

				(function($) {

					function update_chosen() {
						$(".chosen").chosen();
						$(".chosen-image").chosenImage();
					}

					function fetch_service(service_id) {
						service_id = parseInt(service_id, 10);

						return $.grep(PODLOVE.Services, function(service, index) {
							return parseInt(service.id, 10) === service_id;
						})[0]; // Using [0] as the returned element has multiple indexes
					}

					function add_new_service() {
						var row = '';
						row = $("#service-row-template").html();
						var new_row = $("#services_table_body");
						new_row.append(row);
						
						// Update Chosen before we focus on the new service
						update_chosen();
						var new_row_id = new_row.find('select.podlove-service-dropdown').last().attr('id');	
						service_dropdown_handler();
						
						// Focus new service
						$("#" + new_row_id + "_chzn").find("a").focus();
					}

					function add_service_row(service, value, title) {
						var row = '';

						// add service to table
						row = $("#service-row-template").html();
						row = row.replace(/\{\{service-id\}\}/g, service.id);
						row = row.replace(/\{\{id\}\}/g, i);
						$("#services_table_body").append(row);
						i++;
						
						var new_row = $("#services_table_body tr:last");

						// select service in service-dropdown
						new_row.find('select.podlove-service-dropdown option[value="' + service.id + '"]').attr('selected',true);
						// set service description
						new_row.find('label.podlove-service-description').html(service.description);
						// set value
						new_row.find('input.podlove-service-value').val(value);
						// set title
						new_row.find('input.podlove-service-title').val(title);
						// Show account/URL if not empty
						if( new_row.find('input.podlove-service-value').val() !== '' )
							new_row.find('input.podlove-service-value').parent().find(".podlove-service-link").show();
					}

					function service_dropdown_handler() {
						$('select.podlove-service-dropdown').change(function() {
							service = fetch_service(this.value);
							row = $(this).parent().parent();

							// Check for empty contributors / for new field
							if( typeof service === 'undefined' ) {
								row.find(".podlove-logo-column").html(""); // Empty avatar column and hide edit button
								row.find(".podlove-service-edit").hide();
								return;
							}

							// Setting data attribute and avatar field
							row.data("service-id", service.id);
							// Renaming all corresponding elements after the contributor has changed 
							row.find(".podlove-service-dropdown").attr("name", PODLOVE.Services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[id]");
							row.find(".podlove-service-value").attr("name", PODLOVE.Services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[value]");
							row.find(".podlove-service-value").attr("placeholder", service.description);
							row.find(".podlove-service-value").attr("title", service.description);
							row.find(".podlove-service-link").data("service-url-scheme", service.url_scheme);
							row.find(".podlove-service-title").attr("name", PODLOVE.Services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[title]");
							row.find(".podlove-service-edit").show(); // Show Edit Button
							i++; // continue using "i" which was already used to add the existing contributions
						});
					}

					function add_service( service ) {
						add_service_row(fetch_service(service.id), service.value, service.title);
					}

					$(document).on('click', "#add_new_service_button", function() {
						add_new_service();
					});

					$(document).on('click', '.podlove-service-link',  function() {
						if( $(this).parent().find(".podlove-service-value").val() !== '' )
							window.open( $(this).data("service-url-scheme").replace( '%account-placeholder%', $(this).parent().find(".podlove-service-value").val() ) );
					});	

					$(document).on('click', '.service_remove',  function() {
						$(this).closest("tr").remove();
					});

					$(document).on('keydown', '.podlove-service-value',  function() {
						$(this).parent().find(".podlove-service-link").show();
					});

					$(document).on('focusout', '.podlove-service-value',  function() {
						if( $(this).val() == '' )
							$(this).parent().find(".podlove-service-link").hide();
					});	

					$("#podlove_podcast").on('click', 'h3.hndle',  function() {
						$("#contributors_table_body").empty();
						$.each(existing_services, function(index, service) {
							add_service(service);
						});
						update_chosen();
					});	

					$(document).ready(function() {

						$.each(existing_services, function(index, service) {
							add_service(service);
						});

						$("#services_table_body td").each(function(){
						    $(this).css('width', $(this).width() +'px');
						});

						$("#services_table_body").sortable({
							handle: ".reorder-handle",
							helper: function(e, tr) {
							    var $originals = tr.children();
							    var $helper = tr.clone();
							    $helper.children().each(function(index) {
							    	// Set helper cell sizes to match the original sizes
							    	$(this).width($originals.eq(index).width());
							    });
							    return $helper.css({
							    	background: '#EAEAEA'
							    });
							}
						});

						service_dropdown_handler();
						update_chosen();
					});
				}(jQuery));

			</script>
		</div>
		<?php		
	}

	public function admin_print_styles() {

		wp_register_style(
			'podlove_social_admin_style',
			$this->get_module_url() . '/admin.css',
			false,
			\Podlove\get_plugin_header( 'Version' )
		);
		wp_enqueue_style('podlove_social_admin_style');
	}

}