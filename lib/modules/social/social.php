<?php 
namespace Podlove\Modules\Social;

use \Podlove\Modules\Social\Model\Service;
use \Podlove\Modules\Social\Model\ShowService;
use \Podlove\Modules\Social\Model\ContributorService;

use \Podlove\Modules\Social\Settings\PodcastSettingsSocialTab;
use \Podlove\Modules\Social\Settings\PodcastSettingsDonationTab;

use Symfony\Component\Yaml\Yaml;

class Social extends \Podlove\Modules\Base {

	protected $module_name = 'Social & Donations';
	protected $module_description = 'Manage social media accounts and donations.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_module_was_activated_social', array( $this, 'was_activated' ) );
		add_action( 'podlove_podcast_settings_tabs', array( $this, 'podcast_settings_social_tab' ) );
		add_action( 'podlove_podcast_settings_tabs', array( $this, 'podcast_settings_donation_tab' ) );

		add_action( 'update_option_podlove_podcast', array( $this, 'save_social_setting' ), 10, 2 );
		add_action( 'update_option_podlove_podcast', array( $this, 'save_donation_setting' ), 10, 2 );
		add_action( 'update_podlove_contributor', array( $this, 'save_contributor' ), 10, 2 );

		add_action( 'podlove_contributors_form_end', array( $this, 'services_form_for_contributors' ), 10, 2 );
		add_action( 'podlove_contributors_form_end', array( $this, 'donations_form_for_contributors' ), 10, 2 );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		add_filter( "manage_podcast_page_podlove_contributors_settings_handle_columns", array( $this, 'add_new_contributor_column' ) );

		add_action( 'wp_ajax_podlove-services-delete-contributor-services', array($this, 'delete_contributor_services') );
		add_action( 'wp_ajax_podlove-services-delete-podcast-services', array($this, 'delete_podcast_services') );

		add_action('podlove_xml_export', array($this, 'expandExportFile'));
		add_action('podlove_xml_import', array($this, 'expandImport'));

		add_filter('podlove_twig_file_loader', function($file_loader) {
			$file_loader->addPath(implode(DIRECTORY_SEPARATOR, array(\Podlove\PLUGIN_DIR, 'lib', 'modules', 'social', 'templates')), 'social');
			return $file_loader;
		});

		\Podlove\Modules\Contributors\Template\Contributor::add_accessor(
			'services', array('\Podlove\Modules\Social\TemplateExtensions', 'accessorContributorServices'), 5
		);

		\Podlove\Template\Podcast::add_accessor(
			'services', array('\Podlove\Modules\Social\TemplateExtensions', 'accessorPodcastServices'), 4
		);

		add_filter('podlove_cache_tainting_classes', array($this, 'cache_tainting_classes'));

		RepairSocial::init();
		AppDotNet::init();
		Shortcodes::init();
	}

	public function cache_tainting_classes($classes) {
		return array_merge($classes, array(
			Service::name(),
			ShowService::name(),
			ContributorService::name()
		));
	}

	public function was_activated( $module_name ) {
		Service::build();
		ShowService::build();
		ContributorService::build();

		$file = implode(
			DIRECTORY_SEPARATOR,
			array(\Podlove\PLUGIN_DIR, 'lib', 'modules', 'social', 'data', 'services.yml')
		);
		$services = Yaml::parse(file_get_contents($file));

		if (count(Service::all()) == 0) {
			foreach ($services as $service_key => $service) {
				$c = new \Podlove\Modules\Social\Model\Service;
				$c->title = $service['title'];
				$c->category = $service['category'];
				$c->type = $service['name'];
				$c->description = $service['description'];
				$c->logo = $service['logo'];
				$c->url_scheme = $service['url_scheme'];
				$c->save();
			}
		}
	}

	public function save_contributor( $contributor ) {

		foreach (\Podlove\Modules\Social\Model\ContributorService::all("WHERE `contributor_id` = " . $contributor->id) as $service) {
			$service->delete();
		}

		if (!isset($_POST['podlove_contributor']) )
			return;

		foreach (array('donations', 'services') as $type) {
			$position = 0;

			if (isset($_POST['podlove_contributor'][$type]) ) {
				foreach ($_POST['podlove_contributor'][$type] as $service_appearance) {
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
		}
	}

	public function save_service_setting($old, $new, $form_key='services', $type='social') {
		foreach (\Podlove\Modules\Social\Model\ShowService::find_by_category( $type ) as $service) {
			$service->delete();
		}

		if (!isset($new[$form_key]))
			return;

		$services_appearances = $new[$form_key];

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

	public function save_social_setting($old, $new)
	{
		$this->save_service_setting($old, $new);
	}

	public function save_donation_setting($old, $new)
	{
		$this->save_service_setting($old, $new, 'donations', 'donation');
	}

	public function podcast_settings_social_tab($tabs)
	{
		$tabs->addTab( new Settings\PodcastSettingsSocialTab( __( 'Social', 'podlove' ) ) );
		return $tabs;
	}

	public function podcast_settings_donation_tab($tabs)
	{
		$tabs->addTab( new Settings\PodcastSettingsDonationTab( __( 'Donations', 'podlove' ) ) );
		return $tabs;
	}

	public function add_new_contributor_column($columns)
	{
			$keys = array_keys($columns);
		    $insertIndex = array_search('gender', $keys) + 1; // after author column

		    // insert contributors at that index
		    $columns = array_slice($columns, 0, $insertIndex, true) +
		           array(
		           		"social" => __('Social', 'podlove'),
		           		"donation" => __('Donation', 'podlove')
		           	) +
			       array_slice($columns, $insertIndex, count($columns) - 1, true);

		    return $columns;
	}

	public function services_form_for_contributors($wrapper) {

		$wrapper->subheader( __( 'Social', 'podlove' ) );

		$wrapper->callback( 'services_form_table', array(
			'nolabel' => true,
			'callback' => function() {

				if (isset($_GET['contributor'])) {
					$services = \Podlove\Modules\Social\Model\ContributorService::find_by_contributor_id_and_category( $_GET['contributor'] );
				} else {
					$services = array();
				}

				\Podlove\Modules\Social\Social::services_form_table($services);
			}
		) );
	}

	public function donations_form_for_contributors($wrapper) {

		$wrapper->subheader( __( 'Donations', 'podlove' ) );

		$wrapper->callback( 'services_form_table', array(
			'nolabel' => true,
			'callback' => function() {

				if (isset($_GET['contributor'])) {
					$services = \Podlove\Modules\Social\Model\ContributorService::find_by_contributor_id_and_category( $_GET['contributor'], 'donation' );
				} else {
					$services = array();
				}

				\Podlove\Modules\Social\Social::services_form_table( $services, 'podlove_contributor[donations]', 'donation' );
			}
		) );
	}

	public static function services_form_table($current_services = array(), $form_base_name = 'podlove_contributor[services]', $category = 'social') {
		$cjson = array();
		$converted_services = array();
		$wrapper_id = "services-form-$category";

		foreach (\Podlove\Modules\Social\Model\Service::find_all_by_property( 'category', $category ) as $service) {
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
		<div id="<?php echo $wrapper_id ?>">
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
				<tbody class="services_table_body" style="min-height: 50px;">
					<tr class="services_table_body_placeholder" style="display: none;">
						<td><em><?php echo __('No Services were added yet.', 'podlove') ?></em></td>
					</tr>
				</tbody>
			</table>

			<div id="add_new_contributor_wrapper">
				<input class="button" id="add_new_service_button-<?php echo $category ?>" value="+" type="button" />
			</div>

			<script type="text/template" id="service-row-template-<?php echo $category ?>">
			<tr class="media_file_row podlove-service-table" data-service-id="{{service-id}}">
				
				<td class="podlove-service-column">
					<select name="<?php echo $form_base_name ?>[{{id}}][{{service-id}}][id]" class="chosen-image podlove-service-dropdown">
						<option value=""><?php echo __('Choose Service', 'podlove') ?></option>
						<?php foreach ( \Podlove\Modules\Social\Model\Service::all( 'WHERE `category` = \'' . $category . '\' ORDER BY `title`' ) as $service ): ?>
							<option value="<?php echo $service->id ?>" data-img-src="<?php echo $service->get_logo() ?>"><?php echo $service->title; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td>
					<input type="text" name="<?php echo $form_base_name ?>[{{id}}][{{service-id}}][value]" id="podlove_contributor_services_{{id}}_{{service-id}}_value" class="podlove-service-value podlove-check-input" /><span class="podlove-input-status" data-podlove-input-status-for="podlove_contributor_services_{{id}}_{{service-id}}_value"></span>
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

				(function($) {
					var i = 0;
					var existing_services = <?php echo json_encode($converted_services); ?>;
					var services = <?php echo json_encode(array_values($cjson)); ?>;
					var services_form_base_name = "<?php echo $form_base_name ?>";

					function update_chosen() {
						$(".chosen").chosen();
						$(".chosen-image").chosenImage();
					}

					function fetch_service(service_id) {
						service_id = parseInt(service_id, 10);

						return $.grep(services, function(service, index) {
							return parseInt(service.id, 10) === service_id;
						})[0]; // Using [0] as the returned element has multiple indexes
					}

					function service_dropdown_handler() {
						$(document).on('change', 'select.podlove-service-dropdown', function() {
							service = fetch_service(this.value);
							row = $(this).closest("tr");

							// Check for empty contributors / for new field
							if( typeof service === 'undefined' ) {
								row.find(".podlove-logo-column").html(""); // Empty avatar column and hide edit button
								row.find(".podlove-service-edit").hide();
								return;
							}

							// Setting data attribute and avatar field
							row.data("service-id", service.id);
							// Renaming all corresponding elements after the contributor has changed 
							row.find(".podlove-service-dropdown").attr("name", services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[id]");
							row.find(".podlove-service-value").attr("name", services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[value]");
							row.find(".podlove-service-value").attr("placeholder", service.description);
							row.find(".podlove-service-value").attr("title", service.description);
							row.find(".podlove-service-link").data("service-url-scheme", service.url_scheme);
							row.find(".podlove-service-title").attr("name", services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[title]");

							// If this is an Twitter or App.net account remove @
							if ( service.title == 'Twitter' || service.title == 'App.net' )
								row.find(".podlove-service-value").data("podlove-input-remove", "@");

							// If this is an Website, check if the URL is valid
							if ( service.title == 'Website' )
								row.find(".podlove-service-value").data("podlove-input-type", "url");

							i++; // continue using "i" which was already used to add the existing contributions
						});
					}

					$(document).on('click', '.podlove-service-link',  function() {
						if( $(this).parent().find(".podlove-service-value").val() !== '' )
							window.open( $(this).data("service-url-scheme").replace( '%account-placeholder%', $(this).parent().find(".podlove-service-value").val() ) );
					});	

					$(document).on('keydown', '.podlove-service-value',  function() {
						$(this).parent().find(".podlove-service-link").show();
					});

					$(document).on('focusout', '.podlove-service-value',  function() {
						if( $(this).val() == '' )
							$(this).parent().find(".podlove-service-link").hide();
					});

					$(document).ready(function() {
						var i = 0;

						service_dropdown_handler();

						$("#<?php echo $wrapper_id ?> table").podloveDataTable({
							rowTemplate: "#service-row-template-<?php echo $category; ?>",
							deleteHandle: ".service_remove",
							sortableHandle: ".reorder-handle",
							addRowHandle: "#add_new_service_button-<?php echo $category ?>",
							data: existing_services,
							dataPresets: services,
							onRowLoad: function(o) {
								o.row = o.row.replace(/\{\{service-id\}\}/g, o.object.id);
								o.row = o.row.replace(/\{\{id\}\}/g, i);
								i++;
							},
							onRowAdd: function(o) {
								var row = $("#<?php echo $wrapper_id ?> .services_table_body tr:last");

								// select object in object-dropdown
								row.find('select.podlove-service-dropdown option[value="' + o.object.id + '"]').attr('selected',true);
								// set value
								row.find('input.podlove-service-value').val(o.entry.value);
								// set title
								row.find('input.podlove-service-title').val(o.entry.title);
								// Show account/URL if not empty
								if( row.find('input.podlove-service-value').val() !== '' )
									row.find('input.podlove-service-value').parent().find(".podlove-service-link").show();

								// Update Chosen before we focus on the new service
								update_chosen();
								var new_row_id = row.find('select.podlove-service-dropdown').last().attr('id');	
								$('select.podlove-service-dropdown').change();
								
								// Focus new service
								$("#" + new_row_id + "_chzn").find("a").focus();
								clean_up_input();
							},
							onRowDelete: function(tr) {
								var object_id = tr.data("object-id"),
								    ajax_action = "podlove-services-delete-";

								switch(services_form_base_name) {
									case "podlove_contributor[donations]": /* fall through */
									case "podlove_contributor[services]":
										ajax_action += "contributor-services";
										break;
									case "podlove_podcast[donations]": /* fall through */
									case "podlove_podcast[services]":
										ajax_action += "podcast-services";
										break;
									default:
										console.log("Error when deleting social/donation entry: unknows form type");
								}

								var data = {
									action: ajax_action,
									object_id: object_id
								};

								$.ajax({
									url: ajaxurl,
									data: data,
									dataType: 'json'
								});
							}
						});

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

	public function delete_contributor_services() {
		$object_id = (int) $_REQUEST['object_id'];

		if (!$object_id)
			return;

		if ($service = ContributorService::find_by_id($object_id))
			$service->delete();
	}

	public function delete_podcast_services() {
		$object_id = (int) $_REQUEST['object_id'];

		if (!$object_id)
			return;

		if ($service = ShowService::find_by_id($object_id))
			$service->delete();
	}
	
	public function expandExportFile(\SimpleXMLElement $xml) {
		\Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'services', 'service', '\Podlove\Modules\Social\Model\Service');
		\Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributorServices', 'contributorService', '\Podlove\Modules\Social\Model\ContributorService');
		\Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'showServices', 'showService', '\Podlove\Modules\Social\Model\ShowService');
	}

	public function expandImport($xml) {
		\Podlove\Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'service', '\Podlove\Modules\Social\Model\Service');
		\Podlove\Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'contributorService', '\Podlove\Modules\Social\Model\ContributorService');
		\Podlove\Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'showService', '\Podlove\Modules\Social\Model\ShowService');
	}

}
