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

	public function was_activated( $module_name ) {
		Service::build();
		ShowService::build();
		ContributorService::build();
	}

	public function save_contributor() {
		if (!isset($_POST['podlove_contributor']) || !isset($_POST['contributor']))
			return;

		$services_appearances = $_POST['podlove_contributor']['services'];
		$contributor = $_POST['contributor'];

		foreach (\Podlove\Modules\Social\Model\ContributorService::all("WHERE `contributor_id` = " . $_POST['contributor']) as $service) {
			$service->delete();
		}

		foreach ($services_appearances as $service_appearance) {
			foreach ($service_appearance as $service_id => $service) {
				$c = new \Podlove\Modules\Social\Model\ContributorService;
				$c->position = $position;
				$c->contributor_id = $contributor;
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

	/**
	 * Social extension for podcast settings screen.
	 * 
	 * @param  TableWrapper $wrapper form wrapper
	 * @param  Podcast      $podcast podcast model
	 */
	public function podcast_settings_tab($tabs)
	{
		$tabs->addTab( new Settings\PodcastSettingsTab( __( 'Social', 'podlove' ) ) );
		return $tabs;
	}

	public static function services_form_table($current_services = array(), $form_base_name = 'podlove_contributor[services]') {
		$cjson = array();
		$converted_services = array();

		foreach (\Podlove\Modules\Social\Model\Service::all() as $service) {
			$cjson[$service->id] = array(
				'id'   			=> $service->id,
				'title'   		=> $service->title,
				'description'   => $service->description,
				'logo'   		=> "<img src='" . $service->get_logo() . "' width='38px' />",
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
						<th class="podlove-logo-column" colspand="2"></th>
						<th>Service</th>
						<th>Account</th>
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
				<td class="podlove-logo-column"></td>
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

						new_row.find('td.podlove-avatar-column').html(service.logo);
						// select service in service-dropdown
						new_row.find('select.podlove-service-dropdown option[value="' + service.id + '"]').attr('selected',true);
						// set value
						new_row.find('input.podlove-service-value').val(value);
						// set title
						new_row.find('input.podlove-service-title').val(title);
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
							row.find(".podlove-logo-column").html( service.logo );
							// Renaming all corresponding elements after the contributor has changed 
							row.find(".podlove-service-dropdown").attr("name", PODLOVE.Services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[id]");
							row.find(".podlove-service-value").attr("name", PODLOVE.Services_form_base_name + "[" + i + "]" + "[" + service.id + "]" + "[value]");
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

					$(document).on('click', '.service_remove',  function() {
						$(this).closest("tr").remove();
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

}