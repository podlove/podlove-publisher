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

		add_filter( 'podlove_contributor_settings_tabs', array( $this, 'register_contributor_tabs' ), 10, 2 );

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

		self::build_missing_services();
	}

	public static function build_missing_services() {

		$file = implode(
			DIRECTORY_SEPARATOR,
			array(\Podlove\PLUGIN_DIR, 'lib', 'modules', 'social', 'data', 'services.yml')
		);
		$services = Yaml::parse(file_get_contents($file));

		foreach ($services as $service_key => $service) {

			$service_exists = (bool) Service::find_one_by_where(
				sprintf('`category` = "%s" AND `type` = "%s"', $service['category'], $service['name'])
			);

			if (!$service_exists) {
				$s = new Service;
				$s->title = $service['title'];
				$s->category = $service['category'];
				$s->type = $service['name'];
				$s->description = $service['description'];
				$s->logo = $service['logo'];
				$s->url_scheme = $service['url_scheme'];
				$s->save();
			}
		}
	}

	public function save_contributor( $contributor ) {
		if (!isset($_POST['podlove_contributor']) )
			return;

		if (!isset($_POST['podlove_contributor']['services']) && !isset($_POST['podlove_contributor']['donations']))
			return;

		$delete_service = function ($type) use ($contributor) {
			foreach (\Podlove\Modules\Social\Model\ContributorService::all("WHERE `contributor_id` = " . $contributor->id) as $ContributorService) {
				$service = \Podlove\Modules\Social\Model\Service::find_by_id($ContributorService->service_id);
				if ( $service->category == $type )
					$ContributorService->delete();
			}
		};

		foreach (array('donations', 'services') as $type) {
			$position = 0;

			if (isset($_POST['podlove_contributor'][$type]) ) {
				$delete_service( ( $type == 'donations' ? 'donation' : 'social' ) );
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

	public function register_contributor_tabs($tabs) {
		$tabs->addTab( new \Podlove\Modules\Social\Settings\Tab\Social( __( 'Social', 'podlove' ) ) );
		$tabs->addTab( new \Podlove\Modules\Social\Settings\Tab\Donation( __( 'Donation', 'podlove' ) ) );
		return $tabs;
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
		<div id="<?php echo $wrapper_id ?>" class="social_wrapper" data-category="<?php echo $category ?>">
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
				PODLOVE.Social = PODLOVE.Social || {};
				PODLOVE.Social.<?php echo $category ?> = {
					existing_services: <?php echo json_encode($converted_services); ?>,
					services: <?php echo json_encode(array_values($cjson)); ?>,
					form_base_name: "<?php echo $form_base_name ?>"
				};

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

		wp_register_script(
			'podlove_social_admin_script',
			$this->get_module_url() . '/js/admin.js',
			array( 'jquery' ),
			\Podlove\get_plugin_header( 'Version' )
		);
		wp_enqueue_script('podlove_social_admin_script');
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
