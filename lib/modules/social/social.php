<?php

namespace Podlove\Modules\Social;

use Podlove\Modules\Social\Model\ContributorService;
use Podlove\Modules\Social\Model\Service;
use Podlove\Modules\Social\Model\ShowService;
use Symfony\Component\Yaml\Yaml;

class Social extends \Podlove\Modules\Base
{
    protected $module_name = 'Social & Donations';
    protected $module_description = 'Manage social media accounts and donations.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_action('podlove_module_was_activated_social', [$this, 'was_activated']);
        add_action('podlove_podcast_settings_tabs', [$this, 'podcast_settings_social_tab']);
        add_action('podlove_podcast_settings_tabs', [$this, 'podcast_settings_donation_tab']);

        add_action('update_option_podlove_podcast', [$this, 'save_social_setting'], 10, 2);
        add_action('update_option_podlove_podcast', [$this, 'save_donation_setting'], 10, 2);
        add_action('rest_api_init', [$this, 'api_init']);
        add_action('podlove_update_entity_contributor', [$this, 'save_contributor'], 10, 2);
        add_action('podlove_create_entity_contributor', [$this, 'save_contributor'], 10, 2);

        add_filter('podlove_contributor_settings_sections', [$this, 'register_contributor_sections'], 10, 2);

        add_action('admin_print_styles', [$this, 'admin_print_styles']);

        add_filter('podlove_contributor_list_table_columns', [$this, 'add_new_contributor_column']);

        add_action('podlove_xml_export', [$this, 'expandExportFile']);
        add_action('podlove_import_jobs', [$this, 'expandImport']);

        add_filter('podlove_twig_file_loader', function ($file_loader) {
            $file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'lib', 'modules', 'social', 'templates']), 'social');

            return $file_loader;
        });

        \Podlove\Modules\Contributors\Template\Contributor::add_accessor(
            'services',
            ['\Podlove\Modules\Social\TemplateExtensions', 'accessorContributorServices'],
            5
        );

        \Podlove\Template\Podcast::add_accessor(
            'services',
            ['\Podlove\Modules\Social\TemplateExtensions', 'accessorPodcastServices'],
            4
        );

        add_filter('podlove_cache_tainting_classes', [$this, 'cache_tainting_classes']);

        RepairSocial::init();
        Shortcodes::init();
    }

    public function cache_tainting_classes($classes)
    {
        return array_merge($classes, [
            Service::name(),
            ShowService::name(),
            ContributorService::name(),
        ]);
    }

    public function was_activated($module_name)
    {
        Service::build();
        ShowService::build();
        ContributorService::build();

        self::build_missing_services();
    }

    public static function services_config()
    {
        $file = implode(
            DIRECTORY_SEPARATOR,
            [\Podlove\PLUGIN_DIR, 'lib', 'modules', 'social', 'data', 'services.yml']
        );

        return Yaml::parse(file_get_contents($file));
    }

    public static function update_existing_services()
    {
        foreach (self::services_config() as $service_key => $service) {
            $s = Service::find_one_by_where(
                sprintf('`category` = "%s" AND `type` = "%s"', esc_sql($service['category']), esc_sql($service['name']))
            );

            if ($s) {
                $s->title = $service['title'];
                $s->description = $service['description'];
                $s->logo = $service['logo'];
                $s->url_scheme = $service['url_scheme'];
                $s->save();
            }
        }
    }

    public static function build_missing_services()
    {
        foreach (self::services_config() as $service_key => $service) {
            $service_exists = (bool) Service::find_one_by_where(
                sprintf('`category` = "%s" AND `type` = "%s"', $service['category'], $service['name'])
            );

            if (!$service_exists) {
                $s = new Service();
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

    public function save_contributor($contributor)
    {
        if (!isset($_POST['podlove_contributor'])) {
            return;
        }

        if (!isset($_POST['podlove_contributor']['services']) && !isset($_POST['podlove_contributor']['donations'])) {
            return;
        }

        $delete_service = function ($type) use ($contributor) {
            foreach (\Podlove\Modules\Social\Model\ContributorService::all('WHERE `contributor_id` = '.$contributor->id) as $ContributorService) {
                $service = \Podlove\Modules\Social\Model\Service::find_by_id($ContributorService->service_id);
                if ($service->category == $type) {
                    $ContributorService->delete();
                }
            }
        };

        foreach (['donations', 'services'] as $type) {
            $position = 0;

            if (isset($_POST['podlove_contributor'][$type])) {
                $delete_service(($type == 'donations' ? 'donation' : 'social'));
                foreach ($_POST['podlove_contributor'][$type] as $service_appearance) {
                    foreach ($service_appearance as $service_id => $service) {
                        $c = new \Podlove\Modules\Social\Model\ContributorService();
                        $c->position = $position;
                        $c->contributor_id = $contributor->id;
                        $c->service_id = $service_id;
                        $c->value = $service['value'];
                        $c->title = $service['title'];
                        $c->save();
                    }
                    ++$position;
                }
            }
        }
    }

    public function save_service_setting($old, $new, $form_key = 'services', $type = 'social')
    {
        foreach (\Podlove\Modules\Social\Model\ShowService::find_by_category($type) as $service) {
            $service->delete();
        }

        if (!isset($new[$form_key])) {
            return;
        }

        $services_appearances = $new[$form_key];

        $position = 0;
        foreach ($services_appearances as $service_appearance) {
            foreach ($service_appearance as $service_id => $service) {
                $c = new \Podlove\Modules\Social\Model\ShowService();
                $c->position = $position;
                $c->service_id = $service_id;
                $c->value = $service['value'];
                $c->title = $service['title'];
                $c->save();
            }
            ++$position;
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
        $tabs->addTab(new Settings\PodcastSettingsSocialTab('social', __('Social', 'podlove-podcasting-plugin-for-wordpress')));

        return $tabs;
    }

    public function podcast_settings_donation_tab($tabs)
    {
        $tabs->addTab(new Settings\PodcastSettingsDonationTab('donations', __('Donations', 'podlove-podcasting-plugin-for-wordpress')));

        return $tabs;
    }

    public function add_new_contributor_column($columns)
    {
        $keys = array_keys($columns);
        $insertIndex = array_search('gender', $keys) + 1; // after author column

        // insert contributors at that index
        return array_slice($columns, 0, $insertIndex, true) +
                   [
                       'social' => __('Social', 'podlove-podcasting-plugin-for-wordpress'),
                       'donation' => __('Donation', 'podlove-podcasting-plugin-for-wordpress'),
                   ] +
                   array_slice($columns, $insertIndex, count($columns) - 1, true);
    }

    public function register_contributor_sections($sections)
    {
        $sections['social'] = [
            'title' => __('Social', 'podlove-podcasting-plugin-for-wordpress'),
            'fields' => [
                'services_form_table' => [
                    'field_type' => 'callback',
                    'field_options' => [
                        'nolabel' => true,
                        'callback' => function () {
                            if (isset($_GET['contributor'])) {
                                $services = \Podlove\Modules\Social\Model\ContributorService::find_by_contributor_id_and_category($_GET['contributor'], 'social');
                            } else {
                                $services = [];
                            }

                            \Podlove\Modules\Social\Social::services_form_table($services, 'podlove_contributor[services]', 'social');
                        },
                    ],
                ],
            ],
        ];

        $sections['donation'] = [
            'title' => __('Donation', 'podlove-podcasting-plugin-for-wordpress'),
            'fields' => [
                'services_form_table' => [
                    'field_type' => 'callback',
                    'field_options' => [
                        'nolabel' => true,
                        'callback' => function () {
                            if (isset($_GET['contributor'])) {
                                $services = \Podlove\Modules\Social\Model\ContributorService::find_by_contributor_id_and_category($_GET['contributor'], 'donation');
                            } else {
                                $services = [];
                            }

                            \Podlove\Modules\Social\Social::services_form_table($services, 'podlove_contributor[donations]', 'donation');
                        },
                    ],
                ],
            ],
        ];

        return $sections;
    }

    public static function services_form_table($current_services = [], $form_base_name = 'podlove_contributor[services]', $category = 'social')
    {
        $cjson = [];
        $converted_services = [];
        $wrapper_id = "services-form-{$category}";

        foreach (\Podlove\Modules\Social\Model\Service::find_all_by_property('category', $category) as $service) {
            $cjson[$service->id] = [
                'id' => $service->id,
                'title' => $service->title,
                'description' => $service->description,
                'url_scheme' => $service->url_scheme,
            ];
        }

        foreach ($current_services as $current_service_key => $service) {
            $converted_services[$service->id] = [
                'id' => $service->service_id,
                'value' => $service->value,
                'title' => $service->title,
            ];
        } ?>
		<div id="<?php echo $wrapper_id; ?>" class="podlove_social_wrapper" data-category="<?php echo $category; ?>">
			<table class="podlove_alternating" border="0" cellspacing="0">
				<thead>
					<tr>
						
						<th><?php _e('Service', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
						<th><?php _e('Account/URL', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
						<th><?php _e('Title', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
						<th style="width: 60px"><?php _e('Remove', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody class="services_table_body" style="min-height: 50px;">
					<tr class="services_table_body_placeholder" style="display: none;">
						<td><em><?php _e('No Services were added yet.', 'podlove-podcasting-plugin-for-wordpress'); ?></em></td>
					</tr>
				</tbody>
			</table>

			<div id="add_new_contributor_wrapper">
				<input class="button" id="add_new_service_button-<?php echo $category; ?>" value="+" type="button" />
			</div>

			<script type="text/template" id="service-row-template-<?php echo $category; ?>">
			<tr class="media_file_row podlove-service-table" data-service-id="{{service-id}}">
				
				<td class="podlove-service-column">
					<select name="<?php echo $form_base_name; ?>[{{id}}][{{service-id}}][id]" class="chosen-image podlove-service-dropdown">
						<option value=""><?php echo __('Choose Service', 'podlove-podcasting-plugin-for-wordpress'); ?></option>
						<?php foreach (\Podlove\Modules\Social\Model\Service::all('WHERE `category` = \''.$category.'\' ORDER BY `title`') as $service) { ?>
							<option value="<?php echo $service->id; ?>" data-img-src="<?php echo $service->image()->setWidth(45)->url(); ?>"><?php echo $service->title; ?></option>
						<?php } ?>
					</select>
				</td>
				<td>
					<input type="text" name="<?php echo $form_base_name; ?>[{{id}}][{{service-id}}][value]" id="podlove_contributor_services_{{id}}_{{service-id}}_value" class="podlove-service-value podlove-check-input" /><span class="podlove-input-status" data-podlove-input-status-for="podlove_contributor_services_{{id}}_{{service-id}}_value"></span>
					<i class="podlove-icon-share podlove-service-link"></i>
				</td>
				<td>
					<input type="text" name="<?php echo $form_base_name; ?>[{{id}}][{{service-id}}][title]" class="podlove-service-title" />
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
				PODLOVE.Social.<?php echo $category; ?> = {
					existing_services: <?php echo json_encode($converted_services); ?>,
					services: <?php echo json_encode(array_values($cjson)); ?>,
					form_base_name: "<?php echo $form_base_name; ?>"
				};

			</script>
		</div>
		<?php
    }

    public function admin_print_styles()
    {
        if (!isset($_REQUEST['page'])) {
            return;
        }

        if (!in_array($_REQUEST['page'], ['podlove_contributor_settings', 'podlove_settings_podcast_handle'])) {
            return;
        }

        wp_register_style(
            'podlove_social_admin_style',
            $this->get_module_url().'/admin.css',
            false,
            \Podlove\get_plugin_header('Version')
        );
        wp_enqueue_style('podlove_social_admin_style');

        wp_register_script(
            'podlove_social_admin_script',
            $this->get_module_url().'/js/admin.js',
            ['jquery'],
            \Podlove\get_plugin_header('Version')
        );
        wp_enqueue_script('podlove_social_admin_script');
    }

    public function expandExportFile(\SimpleXMLElement $xml)
    {
        \Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'services', 'service', '\Podlove\Modules\Social\Model\Service');
        \Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributorServices', 'contributorService', '\Podlove\Modules\Social\Model\ContributorService');
        \Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'showServices', 'showService', '\Podlove\Modules\Social\Model\ShowService');
    }

    public function expandImport($jobs)
    {
        $jobs[] = '\Podlove\Modules\Social\Jobs\PodcastImportServicesJob';
        $jobs[] = '\Podlove\Modules\Social\Jobs\PodcastImportContributorServicesJob';
        $jobs[] = '\Podlove\Modules\Social\Jobs\PodcastImportShowServicesJob';

        return $jobs;
    }

    public function api_init()
    {
        $api_v1 = new REST_API();
        $api_v1->register_routes();
        $api_v2 = new REST_API_V2();
        $api_v2->register_routes();
    }
}
