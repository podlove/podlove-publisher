<?php

namespace Podlove\Modules\Contributors;

use Podlove\Api\Episodes\WP_REST_PodloveEpisodeContributions_Controller;
use Podlove\Model\Episode;
use Podlove\Modules;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\DefaultContribution;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Modules\Contributors\Model\ShowContribution;

class Contributors extends \Podlove\Modules\Base
{
    protected $module_name = 'Contributors';
    protected $module_description = 'Manage contributors for each episode.';
    protected $module_group = 'metadata';

    public function load()
    {
        add_action('podlove_uninstall_plugin', [$this, 'uninstall']);
        add_action('podlove_module_was_activated_contributors', [$this, 'was_activated']);
        add_filter('podlove_episode_form_data', [$this, 'contributors_form_for_episode'], 10, 2);
        add_action('save_post', [$this, 'update_contributors'], 10, 2);
        add_action('podlove_podcast_settings_tabs', [$this, 'podcast_settings_tab']);
        add_action('update_option_podlove_podcast', [$this, 'save_setting'], 10, 2);
        add_action('rest_api_init', [$this, 'api_init']);
        add_filter('parse_query', [$this, 'filter_by_contributor']);

        add_filter('manage_edit-podcast_columns', [$this, 'add_new_podcast_columns']);
        add_action('manage_podcast_posts_custom_column', [$this, 'manage_podcast_columns']);

        add_action('rss2_head', [$this, 'feed_head_contributors']);
        add_action('podlove_append_to_feed_entry', [$this, 'feed_item_contributors'], 10, 4);

        add_action('podlove_xml_export', [$this, 'expandExportFile']);
        add_filter('podlove_import_jobs', [$this, 'expandImport']);

        add_action('wp_ajax_podlove-contributors-delete-podcast', [$this, 'delete_podcast_contributor']);
        add_action('wp_ajax_podlove-contributors-delete-default', [$this, 'delete_default_contributor']);
        add_action('wp_ajax_podlove-contributors-delete-episode', [$this, 'delete_episode_contributor']);

        add_action('podlove_feed_settings_bottom', [$this, 'feed_settings']);
        add_action('podlove_feed_process', [$this, 'feed_process'], 10, 2);

        add_filter('podlove_twig_file_loader', function ($file_loader) {
            $file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'lib', 'modules', 'contributors', 'templates']), 'contributors');

            return $file_loader;
        });

        add_filter('podlove_cache_tainting_classes', [$this, 'cache_tainting_classes']);

        add_action('podlove_network_admin_bar_podcast', [$this, 'add_to_admin_bar_podcast'], 10, 2);

        \Podlove\Template\Episode::add_accessor(
            'contributors',
            ['\Podlove\Modules\Contributors\TemplateExtensions', 'accessorEpisodeContributors'],
            5
        );

        \Podlove\Template\Podcast::add_accessor(
            'contributors',
            ['\Podlove\Modules\Contributors\TemplateExtensions', 'accessorPodcastContributors'],
            4
        );

        \Podlove\Template\Podcast::add_accessor(
            'contributor',
            ['\Podlove\Modules\Contributors\TemplateExtensions', 'accessorPodcastContributor'],
            4
        );

        // register shortcodes
        new Shortcodes();

        // on settings screen, save per_page option
        add_filter('set-screen-option', function ($status, $option, $value) {
            if ($option == 'podlove_contributors_per_page') {
                return $value;
            }

            return $status;
        }, 10, 3);

        // register settings page
        add_action('podlove_register_settings_pages', function ($settings_parent) {
            new Settings\ContributorSettings(\Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE);
        });

        // filter contributions in feeds
        // add_filter('podlove_feed_contributions', array($this, 'must_have_uri'), 10, 2);
        add_filter('podlove_feed_contributions', [$this, 'must_match_feed_role_and_group'], 10, 2);

        ContributorRepair::init();
        GenderStats::init();
    }

    public function uninstall()
    {
        Contributor::destroy();
        ContributorRole::destroy();
        ContributorGroup::destroy();
        EpisodeContribution::destroy();
        ShowContribution::destroy();
        DefaultContribution::destroy();
    }

    public static function get_index_contributors_url()
    {
        return get_admin_url(get_current_blog_id(), 'admin.php?page=podlove_contributor_settings&podlove_tab=contributors');
    }

    public static function get_create_contributor_url()
    {
        return get_admin_url(get_current_blog_id(), 'admin.php?page=podlove_contributor_settings&podlove_tab=contributors&action=new');
    }

    public static function get_edit_contributor_url($contributor_id)
    {
        return get_admin_url(get_current_blog_id(), 'admin.php?page=podlove_contributor_settings&podlove_tab=contributors&action=edit&contributor='.$contributor_id);
    }

    public function add_to_admin_bar_podcast($wp_admin_bar, $podcast)
    {
        $podcast_toolbar_id = 'podlove_toolbar_'.$podcast;

        $args = [
            'id' => $podcast_toolbar_id.'_contributors',
            'title' => __('Podlove Contributors', 'podlove-podcasting-plugin-for-wordpress'),
            'parent' => 'blog-'.$podcast,
            'href' => self::get_index_contributors_url(),
        ];
        $wp_admin_bar->add_node($args);
    }

    public function must_have_uri($contributions, $feed)
    {
        return array_filter($contributions, function ($c) {
            return is_object($c['contributor']) && strlen($c['contributor']->guid) > 0;
        });
    }

    public function must_match_feed_role_and_group($contributions, $feed)
    {
        $option_name = 'podlove_feed_'.$feed->id.'_contributor_filter';
        $filter = get_option($option_name);

        if (!$filter) {
            return $contributions;
        }

        return array_filter($contributions, function ($c) use ($filter) {
            return (empty($filter['group']) || $c['contribution']->group_id == $filter['group'])
                && (empty($filter['role']) || $c['contribution']->role_id == $filter['role']);
        });
    }

    public function cache_tainting_classes($classes)
    {
        return array_merge($classes, [
            Contributor::name(),
            ContributorRole::name(),
            ContributorGroup::name(),
            EpisodeContribution::name(),
            ShowContribution::name(),
            DefaultContribution::name(),
        ]);
    }

    /**
     * Orders episode contributors by their 'orderby' and 'order' attribute.
     *
     * @param array $contributions List of contributions
     * @param array $args          List of arguments. Keys: order, orderby
     *
     * @return Ordered list of cobtributions
     */
    public static function orderContributions($contributions, $args)
    {
        // Order by via attribute comperator
        if (isset($args['orderby'])) {
            $comperareFunc = null;

            switch (strtoupper($args['orderby'])) {
                case 'COMMENT':
                    $comperareFunc = 'Podlove\\Modules\\Contributors\\Model\\EpisodeContribution::sortByComment';

                    break;
                case 'POSITION':
                    $comperareFunc = 'Podlove\\Modules\\Contributors\\Model\\EpisodeContribution::sortByPosition';

                    break;
            }

            $comperareFunc = apply_filters('podlove_order_contributions_compare_func', $comperareFunc, $args);

            if ($comperareFunc && is_callable($comperareFunc)) {
                usort($contributions, $comperareFunc);
            }
        }

        // ASC or DESC order
        if (!isset($args['order']) || strtoupper($args['order']) == 'DESC') {
            $contributions = array_reverse($contributions);
        }

        return $contributions;
    }

    /**
     * Filter contributions.
     *
     * @fixme {groupby: "role"} is missing
     *
     * @param array $contributions List of contributions
     * @param array $args          List of arguments. Keys: role, group, groupby
     *
     * @return array Return format depends on `groupby` option. If it is not set,
     *               a list of contributors is returned. Otherwise, a list of
     *               hashes is returned. These hashes have one key `contributors`,
     *               which contains the contributors. Depending on the `groupby`
     *               setting there is also a `group` or `role` key, which contains
     *               the expected object.
     */
    public static function filterContributions($contributions, $args)
    {
        // Remove all contributions with missing contributors.
        $contributions = array_filter($contributions, function ($c) {
            return (bool) $c->getContributor();
        });

        if (isset($args['id'])) {
            $contributions = array_filter($contributions, function ($c) use ($args) {
                return $c->getContributor()->identifier == $args['id'];
            });
        }

        // filter by role
        if (isset($args['role']) && $args['role'] != 'all') {
            $role = $args['role'];
            $contributions = array_filter($contributions, function ($c) use ($role) {
                return $c->hasRole() && strtolower($role) == $c->getRole()->slug;
            });
        }

        // filter by group
        if (isset($args['group']) && $args['group'] != 'all') {
            $group = $args['group'];
            $contributions = array_filter($contributions, function ($c) use ($group) {
                return $c->hasGroup() && strtolower($group) == $c->getGroup()->slug;
            });
        }

        // reset keys
        $contributions = array_values($contributions);

        if (isset($args['groupby']) && $args['groupby'] == 'group') {
            $groups = [];
            foreach ($contributions as $contribution) {
                $group = $contribution->getGroup();
                if (is_object($group)) {
                    if (isset($groups[$group->id])) {
                        $groups[$group->id]['contributors'][] = new Template\Contributor($contribution->getContributor(), $contribution);
                    } else {
                        $groups[$group->id] = [
                            'group' => new Template\ContributorGroup($group, [$contribution]),
                            'contributors' => [new Template\Contributor($contribution->getContributor(), $contribution)],
                        ];
                    }
                } else { // handle contributors without a group
                    if (isset($groups[0])) {
                        $groups[0]['contributors'][] = new Template\Contributor($contribution->getContributor(), $contribution);
                    } else {
                        $groups[0] = [
                            'contributors' => [new Template\Contributor($contribution->getContributor(), $contribution)],
                        ];
                    }
                }
            }

            return $groups;
        }
        $contributors = array_map(function ($contribution) {
            return new Template\Contributor($contribution->getContributor(), $contribution);
        }, $contributions);

        // for convenience, return only one contributor if id parameter is used
        if (isset($args['id']) && count($contributors)) {
            return $contributors[0];
        }

        return $contributors;
    }

    /**
     * Expands "Import/Export" module: export logic.
     */
    public function expandExportFile(\SimpleXMLElement $xml)
    {
        Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributors', 'contributor', '\Podlove\Modules\Contributors\Model\Contributor');
        Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-groups', 'contributor-group', '\Podlove\Modules\Contributors\Model\ContributorGroup');
        Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-roles', 'contributor-role', '\Podlove\Modules\Contributors\Model\ContributorRole');
        Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-episode-contributions', 'contributor-episode-contribution', '\Podlove\Modules\Contributors\Model\EpisodeContribution');
        Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-show-contributions', 'contributor-show-contribution', '\Podlove\Modules\Contributors\Model\ShowContribution');
    }

    /**
     * Expands "Import/Export" module: import logic.
     *
     * @param mixed $jobs
     */
    public function expandImport($jobs)
    {
        $jobs[] = '\Podlove\Modules\Contributors\Jobs\PodcastImportContributorsJob';
        $jobs[] = '\Podlove\Modules\Contributors\Jobs\PodcastImportContributorGroupsJob';
        $jobs[] = '\Podlove\Modules\Contributors\Jobs\PodcastImportContributorRolesJob';
        $jobs[] = '\Podlove\Modules\Contributors\Jobs\PodcastImportContributorEpisodeContributionsJob';
        $jobs[] = '\Podlove\Modules\Contributors\Jobs\PodcastImportContributorShowContributionsJob';

        return $jobs;
    }

    public function feed_head_contributors()
    {
        global $wp_query;

        $feed = \Podlove\Model\Feed::find_one_by_slug($wp_query->query_vars['feed']);

        if (!$feed) {
            return;
        }

        $contributor_xml = $this->prepare_contributions_for_feed(
            \Podlove\Modules\Contributors\Model\ShowContribution::all(),
            $feed
        );

        echo apply_filters('podlove_feed_head_contributors', $contributor_xml);
    }

    public function feed_item_contributors($podcast, $episode, $feed, $format)
    {
        $contributor_xml = $this->prepare_contributions_for_feed(
            \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id),
            $feed
        );

        echo apply_filters('podlove_feed_contributors', $contributor_xml);
    }

    /**
     * Allow to filter post list by contributor identifier.
     *
     * @param mixed $query
     */
    public function filter_by_contributor($query)
    {
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'podcast') {
            return;
        }

        if (!isset($_GET['contributor']) || empty($_GET['contributor'])) {
            return;
        }

        if (!$contributor = Contributor::find_one_by_id($_GET['contributor'])) {
            return;
        }

        $contributions = $contributor->getContributions();
        $query->query_vars['post__in'] = array_map(function ($c) {
            return is_object($c->getEpisode()) ? $c->getEpisode()->post_id : 0;
        }, $contributions);
    }

    public function was_activated($module_name)
    {
        Contributor::build();
        ContributorRole::build();
        ContributorGroup::build();
        EpisodeContribution::build();
        ShowContribution::build();
        DefaultContribution::build();
    }

    public function migrate_contributors($module_name)
    {
        $episodes = \Podlove\Episode::all();
        $posted_contributors = [];

        $args = [
            'hierarchical' => false,
            'labels' => [],
            'show_ui' => true,
            'show_tagcloud' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'contributor'],
        ];

        register_taxonomy('podlove-contributors', 'podcast', $args);

        foreach (get_terms('podlove-contributors', 'orderby=count&hide_empty=0') as $contributorid => $contributor) {
            $settings = $this->get_additional_settings_for_migration($contributor->term_id);

            if (isset($settings['contributor_email'])) {
                $privateemail = $settings['contributor_email'];
            } else {
                $privateemail = '';
            }

            $contributor_infos = ['realname' => $contributor->name,
                'publicname' => $contributor->name,
                'identifier' => $contributor->identifier,
                'id' => $contributor->term_id,
                'visibility' => 1,
                'privateemail' => $privateemail, ];

            $contributor_entry = new \Podlove\Modules\Contributors\Contributor();
            $contributor_entry->update_attributes($contributor_infos);
        }

        foreach ($episodes as $episode_id => $episode_details) {
            $terms = get_the_terms($episode_details->post_id, 'podlove-contributors');
            if (isset($terms) and !empty($terms)) {
                foreach ($terms as $term_id => $term_details) {
                    $posted_contributors[] = ['id' => $term_details->term_id, 'slug' => $term_details->slug];
                }
            }
            if (!empty($posted_contributors)) {
                update_post_meta($episode_details->post_id, '_podlove_episode_contributors', json_encode($posted_contributors));
            }
        }
    }

    public static function get_additional_settings_for_migration($term_id)
    {
        $all_contributor_settings = get_option('podlove_contributors', []);
        if (!isset($all_contributor_settings[$term_id])) {
            $all_contributor_settings[$term_id] = [];
        }

        return $all_contributor_settings[$term_id];
    }

    public function update_contributors($post_id)
    {
        if (!$post_id || !isset($_POST['episode_contributor'])) {
            return;
        }

        $episode = Episode::find_one_by_post_id($post_id);

        if (!$episode) {
            return;
        }

        foreach (\Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id) as $contribution) {
            $contribution->delete();
        }

        $position = 0;

        foreach ($_POST['episode_contributor'] as $contributor_appearance) {
            foreach ($contributor_appearance as $contributor_id => $contributor) {
                if (!$contributor_id) {
                    continue;
                }

                $c = new \Podlove\Modules\Contributors\Model\EpisodeContribution();

                if (!empty($contributor['role'])) {
                    $c->role_id = \Podlove\Modules\Contributors\Model\ContributorRole::find_one_by_slug($contributor['role'])->id;
                }

                if (!empty($contributor['group'])) {
                    $c->group_id = \Podlove\Modules\Contributors\Model\ContributorGroup::find_one_by_slug($contributor['group'])->id;
                }

                $c->episode_id = $episode->id;
                $c->contributor_id = $contributor_id;
                $c->comment = stripslashes($contributor['comment']);
                $c->position = $position++;
                $c->save();
            }
        }
    }

    public function contributors_form_for_episode($form_data)
    {
        $form_data[] = [
            'type' => 'callback',
            'key' => 'contributors_form_table',
            'options' => [
                'label' => __('Contributors', 'podlove-podcasting-plugin-for-wordpress'),
                'callback' => [$this, 'contributors_form_for_episode_callback'],
            ],
            'position' => 850,
        ];

        return $form_data;
    }

    public function contributors_form_for_episode_callback()
    {
        $current_page = get_current_screen();
        $episode = Episode::find_one_by_post_id(get_the_ID());

        // determine existing contributions
        $contributions = [];
        if ($current_page->action == 'add') {
            $i = 0;
            $permanent_contributors = [];
            foreach (DefaultContribution::all() as $contribution_key => $contribution) {
                $permanent_contributors[$contribution_key]['contributor'] = $contribution->getContributor();
                $permanent_contributors[$contribution_key]['role'] = $contribution->getRole();
                $permanent_contributors[$contribution_key]['group'] = $contribution->getGroup();
                $permanent_contributors[$contribution_key]['comment'] = $contribution->comment;
            }

            foreach ($permanent_contributors as $permanent_contributor) {
                $contrib = new \Podlove\Modules\Contributors\Model\EpisodeContribution();
                $contrib->contributor_id = $permanent_contributor['contributor']->id;

                if (isset($permanent_contributor['role'])) {
                    $contrib->role = ContributorRole::find_by_id($permanent_contributor['role']->id);
                }

                if (isset($permanent_contributor['group'])) {
                    $contrib->group = ContributorGroup::find_by_id($permanent_contributor['group']->id);
                }

                if (isset($permanent_contributor['comment'])) {
                    $contrib->comment = $permanent_contributor['comment'];
                }

                $contributions[] = $contrib;
            }

            // map indices to IDs
            $map = [];
            $i = 0;
            foreach ($contributions as $c) {
                $map['default'.$c->contributor_id.'_'.$i] = $c;
                ++$i;
            }
        } else {
            $contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('WHERE `episode_id` = '.$episode->id.' ORDER BY `position` ASC');

            // map indices to IDs
            $map = [];
            foreach ($contributions as $c) {
                $map[$c->id] = $c;
            }
        }

        \Podlove\Modules\Contributors\Contributors::contributors_form_table($map);
    }

    /**
     * Contributors extension for podcast settings screen.
     *
     * @param TableWrapper $wrapper form wrapper
     * @param Podcast      $podcast podcast model
     * @param mixed        $tabs
     */
    public function podcast_settings_tab($tabs)
    {
        $tabs->addTab(new Settings\PodcastContributorsSettingsTab('Contributors', __('Contributors', 'podlove-podcasting-plugin-for-wordpress')));

        return $tabs;
    }

    /**
     * @todo  this save logic belongs into the tab class
     *
     * @param mixed $old
     * @param mixed $new
     */
    public function save_setting($old, $new)
    {
        if (!isset($new['contributor'])) {
            return;
        }

        $contributor_appearances = $new['contributor'];

        foreach (\Podlove\Modules\Contributors\Model\ShowContribution::all() as $contribution) {
            $contribution->delete();
        }

        $position = 0;
        foreach ($contributor_appearances as $contributor_appearance) {
            foreach ($contributor_appearance as $contributor_id => $contributor) {
                $c = new \Podlove\Modules\Contributors\Model\ShowContribution();

                if (isset($contributor['role'])) {
                    if ($role = ContributorRole::find_one_by_slug($contributor['role'])) {
                        $c->role_id = $role->id;
                    }
                }

                if (isset($contributor['group'])) {
                    if ($group = ContributorGroup::find_one_by_slug($contributor['group'])) {
                        $c->group_id = $group->id;
                    }
                }

                if (isset($contributor['comment'])) {
                    $c->comment = $contributor['comment'];
                }

                $c->contributor_id = $contributor_id;
                $c->position = $position++;
                $c->save();
            }
        }
    }

    public static function contributors_form_table($current_contributions = [], $form_base_name = 'episode_contributor')
    {
        $contributors_roles = \Podlove\Modules\Contributors\Model\ContributorRole::selectOptions();
        $contributors_groups = \Podlove\Modules\Contributors\Model\ContributorGroup::selectOptions();
        $cjson = [];

        // only valid contributions
        $current_contributions = array_filter($current_contributions, function ($c) {
            return $c->contributor_id > 0;
        });

        $has_roles = count($contributors_roles) > 0;
        $has_groups = count($contributors_groups) > 0;
        $can_be_commented = $form_base_name == 'podlove_contributor_defaults[contributor]' ? 0 : 1;

        foreach (\Podlove\Modules\Contributors\Model\Contributor::all() as $contributor) {
            $show_contributions = \Podlove\Modules\Contributors\Model\ShowContribution::all('WHERE `contributor_id` = '.$contributor->id);
            if (empty($show_contributions)) {
                $cjson[$contributor->id] = [
                    'id' => $contributor->id,
                    'slug' => $contributor->identifier,
                    'role' => '',
                    'group' => '',
                    'realname' => $contributor->realname,
                    'avatar' => $contributor->avatar()->setWidth(45)->image(),
                ];
            } else {
                foreach ($show_contributions as $show_contribution) {
                    $role_data = \Podlove\Modules\Contributors\Model\ContributorRole::find_one_by_id($show_contribution->role_id);
                    ($role_data == '' ? $role = '' : $role = $role_data->id);
                    $group_data = \Podlove\Modules\Contributors\Model\ContributorGroup::find_one_by_id($show_contribution->group_id);
                    ($group_data == '' ? $group = '' : $group = $group_data->id);
                    $cjson[$contributor->id] = [
                        'id' => $contributor->id,
                        'slug' => $contributor->identifier,
                        'role' => $role,
                        'group' => $group,
                        'realname' => $contributor->realname,
                        'avatar' => $contributor->avatar()->setWidth(45)->image(),
                    ];
                }
            }
        }

        // override contributor roles and groups with scoped roles
        foreach ($current_contributions as $contribution_key => $current_contribution) {
            if ($role = $current_contribution->getRole()) {
                $cjson[$current_contribution->contributor_id]['role'] = $role->slug;
            }
            if ($group = $current_contribution->getGroup()) {
                $cjson[$current_contribution->contributor_id]['group'] = $group->slug;
            }
        }

        $contributors = \Podlove\Modules\Contributors\Model\Contributor::all();

        $existing_contributions = array_filter(array_map(function ($c) {
            // Set default role
            $role_data = \Podlove\Modules\Contributors\Model\ContributorRole::find_by_id($c->role_id);
            if (isset($role_data)) {
                $role = $role_data->slug;
            } else {
                if (empty($c->role)) {
                    $role = '';
                } else {
                    $role = $c->role->slug;
                }
            }

            // Set default group
            $group_data = \Podlove\Modules\Contributors\Model\ContributorGroup::find_by_id($c->group_id);
            if (isset($group_data)) {
                $group = $group_data->slug;
            } else {
                if (empty($c->group)) {
                    $group = '';
                } else {
                    $group = $c->group->slug;
                }
            }

            if (is_object(\Podlove\Modules\Contributors\Model\Contributor::find_by_id($c->contributor_id))) {
                return ['id' => $c->contributor_id, 'role' => $role, 'group' => $group, 'comment' => $c->comment];
            }

            return '';
        }, $current_contributions));

        \Podlove\load_template(
            'lib/modules/contributors/views/form_table',
            compact(
                'has_groups',
                'has_roles',
                'can_be_commented',
                'form_base_name',
                'existing_contributions',
                'cjson',
                'contributors',
                'contributors_groups',
                'contributors_roles'
            )
        );
    }

    public function add_new_podcast_columns($columns)
    {
        $keys = array_keys($columns);
        $insertIndex = array_search('author', $keys) + 1; // after author column

        // insert contributors at that index
        return array_slice($columns, 0, $insertIndex, true) +
                   ['contributors' => __('Contributors', 'podlove-podcasting-plugin-for-wordpress')] +
                   array_slice($columns, $insertIndex, count($columns) - 1, true);
    }

    public function manage_podcast_columns($column_name)
    {
        switch ($column_name) {
            case 'contributors':
                $episode = \Podlove\Model\Episode::find_one_by_post_id(get_the_ID());

                if (!$episode) {
                    return;
                }

                $contributors = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id);
                $contributor_list = '';

                foreach ($contributors as $contributor_id => $contributor) {
                    $contributor_details = $contributor->getContributor();

                    if (is_object($contributor_details)) {
                        $contributor_list = $contributor_list.'<a href="'.site_url().'/wp-admin/edit.php?post_type=podcast&contributor='.$contributor_details->id.'">'.$contributor_details->getName().'</a>, ';
                    }
                }

                echo substr($contributor_list, 0, -2);

            break;
        }
    }

    public function delete_podcast_contributor()
    {
        $object_id = (int) $_REQUEST['object_id'];

        if (!$object_id) {
            return;
        }

        if ($service = ShowContribution::find_by_id($object_id)) {
            $service->delete();
        }
    }

    public function delete_default_contributor()
    {
        $object_id = (int) $_REQUEST['object_id'];

        if (!$object_id) {
            return;
        }

        if ($service = DefaultContribution::find_by_id($object_id)) {
            $service->delete();
        }
    }

    public function delete_episode_contributor()
    {
        $object_id = (int) $_REQUEST['object_id'];

        if (!$object_id) {
            return;
        }

        if ($service = EpisodeContribution::find_by_id($object_id)) {
            $service->delete();
        }
    }

    public function feed_settings($wrapper)
    {
        $contributors_roles = \Podlove\Modules\Contributors\Model\ContributorRole::all();
        $contributors_groups = \Podlove\Modules\Contributors\Model\ContributorGroup::all();
        $option_name = 'podlove_feed_'.$_REQUEST['feed'].'_contributor_filter';

        $selected_filter = get_option($option_name);

        if (!$selected_filter) {
            $selected_filter = [
                'group' => null,
                'role' => null,
            ];
        }

        $wrapper->subheader(__('Contributors', 'podlove-podcasting-plugin-for-wordpress'));
        $wrapper->callback('services_form_table', [
            'label' => __('Contributor Filter', 'podlove-podcasting-plugin-for-wordpress'),
            'callback' => function () use ($contributors_roles, $contributors_groups, $selected_filter) {
                ?>
					<select name="podlove_feed[contributor_filter][group]" id="">
						<option value=""></option>
						<?php
                            foreach ($contributors_groups as $group) {
                                echo "<option value='".$group->id."' ".($group->id == $selected_filter['group'] ? 'selected' : '').'>'.$group->title.'</option>';
                            } ?>
					</select>
					<?php echo __('Group', 'podlove-podcasting-plugin-for-wordpress'); ?>

					<select name="podlove_feed[contributor_filter][role]" id="">
						<option value=""></option>
						<?php
                            foreach ($contributors_roles as $role) {
                                echo "<option value='".$role->id."' ".($role->id == $selected_filter['role'] ? 'selected' : '').'>'.$role->title.'</option>';
                            } ?>
					</select>
					<?php echo __('Role', 'podlove-podcasting-plugin-for-wordpress'); ?>
					<p>
						<span class="description"><?php echo __('Limit contributors to the given group and/or role.', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
					</p>
				<?php
            },
        ]);

        return $wrapper;
    }

    public function feed_process($feed_id, $action)
    {
        if (!$_POST) {
            return;
        }

        $group = $_POST['podlove_feed']['contributor_filter']['group'];
        $role = $_POST['podlove_feed']['contributor_filter']['role'];
        $option_name = 'podlove_feed_'.$feed_id.'_contributor_filter';

        update_option($option_name, ['group' => $group, 'role' => $role]);
    }

    public function api_init()
    {
        $api_v1 = new REST_API();
        $api_v1->register_routes();
        $api_v2 = new WP_REST_PodloveContributors_Controller();
        $api_v2->register_routes();
        $api_episode_contributor = new WP_REST_PodloveEpisodeContributions_Controller();
        $api_episode_contributor->register_routes();
    }

    /**
     * Prepare contributions for output in feed.
     *
     *	- applies various filters
     *	- generates and returns feed-compatible xml
     *
     * @param array  $raw_contributions
     * @param object $feed
     *
     * @return string
     */
    private function prepare_contributions_for_feed($raw_contributions, $feed)
    {
        $contributions = [];
        foreach ($raw_contributions as $contribution) {
            $contributions[] = [
                'contributor' => $contribution->getContributor(),
                'contribution' => $contribution,
            ];
        }

        $contributions = apply_filters('podlove_feed_contributions', $contributions, $feed);

        $contributor_xml = '';
        // atom:contributor
        foreach ($contributions as $contribution) {
            $contributor_xml .= $this->getContributorXML($contribution['contributor']);
        }
        // podcast:person
        foreach ($contributions as $contribution) {
            $contributor_xml .= $this->getPodcastindexContributorXML($contribution['contributor'], $contribution['contribution']);
        }

        return $contributor_xml;
    }

    private function getContributorXML($contributor)
    {
        $contributor_xml = '';

        if ($contributor->visibility == 1) {
            $dom = new \Podlove\DomDocumentFragment();

            $xml = $dom->createElement('atom:contributor');

            // add the empty name tag
            $name = $dom->createElement('atom:name');
            $xml->appendChild($name);

            // fill name tag with escaped content
            $name_text = $dom->createTextNode($contributor->getName());
            $name->appendChild($name_text);

            if ($contributor->guid) {
                $xml->appendChild($dom->createElement('atom:uri', $contributor->guid));
            }

            $dom->appendChild($xml);

            $contributor_xml .= (string) $dom;
        }

        return $contributor_xml;
    }

    /**
     * get contributor xml in podcastindex "person" format.
     *
     * @todo take first social URL and add it as href attribute
     *
     * @see https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#person
     *
     * @param mixed $contributor
     * @param mixed $contribution
     */
    private function getPodcastindexContributorXML($contributor, $contribution)
    {
        $contributor_xml = '';

        if ($contributor->visibility == 1) {
            $dom = new \Podlove\DomDocumentFragment();

            $xml = $dom->createElement('podcast:person');

            $name_text = $dom->createTextNode($contributor->getName());
            $xml->appendChild($name_text);

            $dom->appendChild($xml);

            $xml->setAttribute('img', $contributor->avatar()->url());

            // proof of concept for roles/groups
            // next implementation should fully support all options
            // @see https://github.com/Podcastindex-org/podcast-namespace/blob/main/taxonomy.json
            if ($role = $contribution->getRole()) {
                $role_title = strtolower($role->title);

                switch ($role_title) {
                    case 'guest':
                    case 'gast':
                        $matching_role = 'guest';

                        break;
                    case 'host':
                    case 'team':
                        $matching_role = 'host';

                        break;
                    case 'sponsor':
                        $matching_role = 'sponsor';

                        break;

                    default:
                        $matching_role = null;

                        break;
                }

                if ($matching_role) {
                    $xml->setAttribute('role', $matching_role);
                }
            }

            $contributor_xml .= (string) $dom;
        }

        return $contributor_xml;
    }
}
