<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Model\Episode;
use \Podlove\Modules;
use \Podlove\Modules\Contributors\Model\Contributor;
use \Podlove\Modules\Contributors\Model\ContributorRole;
use \Podlove\Modules\Contributors\Model\ContributorGroup;
use \Podlove\Modules\Contributors\Model\EpisodeContribution;
use \Podlove\Modules\Contributors\Model\ShowContribution;
use \Podlove\Modules\Contributors\Model\DefaultContribution;

use Podlove\DomDocumentFragment;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_uninstall_plugin', [$this, 'uninstall'] );
		add_action( 'podlove_module_was_activated_contributors', array( $this, 'was_activated' ) );
		add_filter( 'podlove_episode_form_data', array( $this, 'contributors_form_for_episode' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_contributors' ), 10, 2 );
		add_action( 'podlove_podcast_settings_tabs', array( $this, 'podcast_settings_tab' ) );
		add_action( 'update_option_podlove_podcast', array( $this, 'save_setting' ), 10, 2 );
		add_filter( 'parse_query', array($this, 'filter_by_contributor') );

		add_filter('manage_edit-podcast_columns', array( $this, 'add_new_podcast_columns' ) );
		add_action('manage_podcast_posts_custom_column', array( $this, 'manage_podcast_columns' ) );
	
		add_action('rss2_head', array($this, 'feed_head_contributors'));
		add_action('podlove_append_to_feed_entry', array($this, 'feed_item_contributors'), 10, 4);

		add_action('podlove_dashboard_meta_boxes', array($this, 'dashboard_gender_statistics'));
		add_filter('podlove_dashboard_statistics_network', array($this, 'dashboard_network_statistics_row'));

		add_action('podlove_xml_export', array($this, 'expandExportFile'));
		add_action('podlove_xml_import', array($this, 'expandImport'));
		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		add_action( 'wp_ajax_podlove-contributors-delete-podcast', array($this, 'delete_podcast_contributor') );
		add_action( 'wp_ajax_podlove-contributors-delete-default', array($this, 'delete_default_contributor') );
		add_action( 'wp_ajax_podlove-contributors-delete-episode', array($this, 'delete_episode_contributor') );

		add_action( 'podlove_feed_settings_bottom', array($this, 'feed_settings') );
		add_action( 'podlove_feed_process', array($this, 'feed_process'), 10, 2 );

		add_filter( 'podlove_adn_tags_description', array($this, 'adn_tags_description') );
		add_filter( 'podlove_adn_example_data', array($this, 'adn_example_data'), 10, 4 );
		add_filter( 'podlove_adn_tags', array($this, 'adn_tags'), 10, 4 );

		add_filter('podlove_twig_file_loader', function($file_loader) {
			$file_loader->addPath(implode(DIRECTORY_SEPARATOR, array(\Podlove\PLUGIN_DIR, 'lib', 'modules', 'contributors', 'templates')), 'contributors');
			return $file_loader;
		});

		add_filter('podlove_cache_tainting_classes', array($this, 'cache_tainting_classes'));

		add_action('podlove_network_admin_bar_podcast', array($this, 'add_to_admin_bar_podcast'), 10, 2);

		\Podlove\Template\Episode::add_accessor(
			'contributors', array('\Podlove\Modules\Contributors\TemplateExtensions', 'accessorEpisodeContributors'), 5
		);

		\Podlove\Template\Podcast::add_accessor(
			'contributors',	array('\Podlove\Modules\Contributors\TemplateExtensions', 'accessorPodcastContributors'), 4
		);

		// register shortcodes
		new Shortcodes;	

		// on settings screen, save per_page option
		add_filter( "set-screen-option", function($status, $option, $value) {
			if ($option == 'podlove_contributors_per_page')
				return $value;
			
			return $status;
		}, 10, 3 );

		// register settings page
		add_action('podlove_register_settings_pages', function($settings_parent) {
			new Settings\Contributors($settings_parent);
			new Settings\ContributorSettings( \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE );
		});

		// filter contributions in feeds
		add_filter('podlove_feed_contributions', array($this, 'must_have_uri'), 10, 2);
		add_filter('podlove_feed_contributions', array($this, 'must_match_feed_role_and_group'), 10, 2);

		ContributorRepair::init();
	}

	public function uninstall() {
		Contributor::destroy();
		ContributorRole::destroy();
		ContributorGroup::destroy();
		EpisodeContribution::destroy();
		ShowContribution::destroy();
		DefaultContribution::destroy();
	}

	public function add_to_admin_bar_podcast($wp_admin_bar, $podcast)
	{
		$podcast_toolbar_id = 'podlove_toolbar_' . $podcast;

		$args = array(
			'id'     => $podcast_toolbar_id . '_contributors',
			'title'  => __( 'Podlove Contributors', 'podlove' ),
			'parent' => "blog-" . $podcast,
			'href'   => get_admin_url(get_current_blog_id(), 'edit.php?post_type=podcast&page=podlove_contributors_settings_handle')
		);
		$wp_admin_bar->add_node( $args );
	}

	public function must_have_uri($contributions, $feed)
	{
		return array_filter($contributions, function($c) {
			return is_object($c['contributor']) && strlen($c['contributor']->guid) > 0;
		});
	}

	public function must_match_feed_role_and_group($contributions, $feed)
	{
		$option_name = 'podlove_feed_' . $feed->id . '_contributor_filter';
		$filter = get_option( $option_name );

		if (!$filter)
			return $contributions;

		return array_filter($contributions, function($c) use ($filter) {
			return (empty($filter['group']) || $c['contribution']->group_id == $filter['group'])
			    && (empty($filter['role'])  || $c['contribution']->role_id  == $filter['role']);
		});
	}

	public function cache_tainting_classes($classes) {
		return array_merge($classes, array(
			Contributor::name(),
			ContributorRole::name(),
			ContributorGroup::name(),
			EpisodeContribution::name(),
			ShowContribution::name(),
			DefaultContribution::name()
		));
	}

	/**
	 * Orders episode contributors by their 'orderby' and 'order' attribute.
	 *
	 * @param  array $contributions List of contributions
	 * @param  array $args          List of arguments. Keys: order, orderby
	 * @return Ordered list of cobtributions.
	 */
	public static function orderContributions($contributions, $args) {
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
	 * @param  array $contributions List of contributions
	 * @param  array $args          List of arguments. Keys: role, group, groupby
	 * @return array Return format depends on `groupby` option. If it is not set,
	 *               a list of contributors is returned. Otherwise, a list of 
	 *               hashes is returned. These hashes have one key `contributors`,
	 *               which contains the contributors. Depending on the `groupby`
	 *               setting there is also a `group` or `role` key, which contains
	 *               the expected object.
	 */
	public static function filterContributions($contributions, $args) {
		// Remove all contributions with missing contributors.
		$contributions = array_filter($contributions, function($c) {
			return (bool) $c->getContributor();
		});

		if (isset($args['id'])) {
			$contributions = array_filter($contributions, function($c) use ($args) {
				return $c->getContributor()->slug == $args['id'];
			});
		}

		// filter by role
		if (isset($args['role']) && $args['role'] != 'all') {
			$role = $args['role'];
			$contributions = array_filter($contributions, function($c) use ($role) {
				return $c->hasRole() && strtolower($role) == $c->getRole()->slug;
			});
		}

		// filter by group
		if (isset($args['group']) && $args['group'] != 'all') {
			$group = $args['group'];
			$contributions = array_filter($contributions, function($c) use ($group) {
				return $c->hasGroup() && strtolower($group) == $c->getGroup()->slug;
			});
		}

		// reset keys
		$contributions = array_values($contributions);

		if (isset($args['groupby']) && $args['groupby'] == 'group') {
			$groups = array();
			foreach ($contributions as $contribution) {
				$group = $contribution->getGroup();
				if (is_object($group)) {
					if (isset($groups[$group->id])) {
						$groups[$group->id]['contributors'][] = new Template\Contributor($contribution->getContributor(), $contribution);
					} else {
						$groups[$group->id] = array(
							'group'        => new Template\ContributorGroup($group, array($contribution)),
							'contributors' => array(new Template\Contributor($contribution->getContributor(), $contribution))
						);
					}
				} else { // handle contributors without a group
					if (isset($groups[0])) {
						$groups[0]['contributors'][] = new Template\Contributor($contribution->getContributor(), $contribution);
					} else {
						$groups[0] = array(
							'contributors' => array(new Template\Contributor($contribution->getContributor(), $contribution))
						);
					}
				}
			}
			return $groups;
		} else {
			$contributors = array_map(function($contribution) {
				return new Template\Contributor($contribution->getContributor(), $contribution);
			}, $contributions);

			// for convenience, return only one contributor if id parameter is used
			if (isset($args['id']) && count($contributors)) {
				return $contributors[0];
			} else {
				return $contributors;
			}
		}
	}

	/**
	 * Expands "Import/Export" module: export logic
	 */
	public function expandExportFile(\SimpleXMLElement $xml) {
		Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributors', 'contributor', '\Podlove\Modules\Contributors\Model\Contributor');
		Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-groups', 'contributor-group', '\Podlove\Modules\Contributors\Model\ContributorGroup');
		Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-roles', 'contributor-role', '\Podlove\Modules\Contributors\Model\ContributorRole');
		Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-episode-contributions', 'contributor-episode-contribution', '\Podlove\Modules\Contributors\Model\EpisodeContribution');
		Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'contributor-show-contributions', 'contributor-show-contribution', '\Podlove\Modules\Contributors\Model\ShowContribution');
	}

	/**
	 * Expands "Import/Export" module: import logic
	 */
	public function expandImport($xml) {
		Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'contributor', '\Podlove\Modules\Contributors\Model\Contributor');
		Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'contributor-group', '\Podlove\Modules\Contributors\Model\ContributorGroup');
		Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'contributor-role', '\Podlove\Modules\Contributors\Model\ContributorRole');
		Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'contributor-episode-contribution', '\Podlove\Modules\Contributors\Model\EpisodeContribution');
		Modules\ImportExport\Import\PodcastImporter::importTable($xml, 'contributor-show-contribution', '\Podlove\Modules\Contributors\Model\ShowContribution');
	}

	private function fetch_contributors_for_dashboard_statistics() {
		return \Podlove\cache_for('podlove_dashboard_stats_contributors', function() {
			return (new Model\ContributionGenderStatistics)->get();
		}, 3600);
	}

	public function dashboard_gender_statistics() {
		add_meta_box(
			\Podlove\Settings\Dashboard::$pagehook . '_gender',
			__( 'Gender Statistics', 'podlove' ),
			[$this, 'dashboard_gender_statistics_widget'],
			\Podlove\Settings\Dashboard::$pagehook,
			'normal', 
			'default'
		);
	}

	public function dashboard_gender_statistics_widget($post) {

		if (EpisodeContribution::count() === 0) {
			?>
			<p>
				<?php echo __('Gender statistics will be available once you start assigning contributors to episodes.', 'podlove') ?>
			</p>
			<?php
			return;
		}

		$gender_distribution = $this->fetch_contributors_for_dashboard_statistics();
		?>
		<div class="podlove_gender_widget_column">
			<h4><?php _e('Total', 'podlove'); ?></h4>
			<table cellspacing="0" cellspadding="0">
				<thead>
					<tr>
						<th><?php _e('Female', 'podlove'); ?></th>
						<th><?php _e('Male', 'podlove'); ?></th>
						<th><?php _e('Not Attributed', 'podlove'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo self::get_percentage($gender_distribution['global']['by_gender']['female'], $gender_distribution['global']['total']) ?>%</td>
						<td><?php echo self::get_percentage($gender_distribution['global']['by_gender']['male'], $gender_distribution['global']['total']) ?>%</td>
						<td><?php echo self::get_percentage($gender_distribution['global']['by_gender']['none'], $gender_distribution['global']['total']) ?>%</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="podlove_gender_widget_column">
			<h4><?php _e('By Group', 'podlove'); ?></h4>
			<?php self::group_or_role_stats_table('group', $gender_distribution['by_group']); ?>
		</div>
		<div class="podlove_gender_widget_column">
			<h4><?php _e('By Role', 'podlove'); ?></h4>
			<?php self::group_or_role_stats_table('role', $gender_distribution['by_role']); ?>
		</div>
		<?php
	}

	private static function get_percentage($value, $relative_to) {

		if ($relative_to === 0)
			return "—";

		return round($value / $relative_to * 100);
	}

	private static function group_or_role_stats_table($context, $numbers) {
		?>
		<table cellspacing="0" cellspadding="0">
			<thead>
				<tr>
					<th><?php echo ( $context == 'group' ? __('Group', 'podlove') : __('Role', 'podlove') ); ?></th>
					<th><?php _e('Female', 'podlove'); ?></th>
					<th><?php _e('Male', 'podlove'); ?></th>
					<th><?php _e('Not Attributed', 'podlove'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($numbers as $group_or_role_id => $group_or_role_numbers) {
				$group_or_role = ( $context == 'group' ? ContributorGroup::find_one_by_id($group_or_role_id) : ContributorRole::find_one_by_id($group_or_role_id) ); // This return either a group or a role object	

				if ( !$group_or_role )
					continue;
				?>
					<tr>
						<td><?php echo $group_or_role->title; ?></td>
						<td><?php echo self::get_percentage($group_or_role_numbers['by_gender']['female'], $group_or_role_numbers['total']); ?>%</td>
						<td><?php echo self::get_percentage($group_or_role_numbers['by_gender']['male'],   $group_or_role_numbers['total']); ?>%</td>
						<td><?php echo self::get_percentage($group_or_role_numbers['by_gender']['none'],   $group_or_role_numbers['total']); ?>%</td>
					</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}

	public function dashboard_network_statistics_row( $genders ) {
		$podcasts = \Podlove\Modules\Networks\Model\Network::podcast_blog_ids();
		$podcasts_with_contributors_active = 0;
		$relative_gender_numbers = array( 
			'male'   => 0,
			'female' => 0,
			'none'   => 0
		);

		foreach ( $podcasts as $podcast ) {
			switch_to_blog( $podcast );
			if ( \Podlove\Modules\Base::is_active('contributors') ) {
				$global_gender_numbers = $this->fetch_contributors_for_dashboard_statistics();
				if ($global_gender_numbers['global']['total'] > 0) {
					foreach ( $global_gender_numbers['global']['by_gender'] as $gender => $number_of_contributions ) {
						 $relative_gender_numbers[$gender] += $number_of_contributions / $global_gender_numbers['global']['total'] * 100;
					}
				}
				$podcasts_with_contributors_active++;
			}
			restore_current_blog();
		}
		?>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo __('Genders', 'podlove') ?>
			</td>
			<td>
				<?php
				echo implode(', ', array_map(function($percent, $gender) use ( $podcasts_with_contributors_active ) {
					return round($percent/$podcasts_with_contributors_active) . "% " . ( $gender == 'none' ? 'not attributed' : $gender );
				}, $relative_gender_numbers, array_keys($relative_gender_numbers)));
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Prepare contributions for output in feed.
	 *
	 *	- applies various filters
	 *	- generates and returns feed-compatible xml
	 * 
	 * @param  array  $raw_contributions
	 * @param  object $feed
	 * @return string
	 */
	private function prepare_contributions_for_feed($raw_contributions, $feed)
	{
		$contributions = array();
		foreach ($raw_contributions as $contribution) {
			$contributions[] = array(
				'contributor'  => $contribution->getContributor(),
				'contribution' => $contribution
			);
		}

		$contributions = apply_filters( 'podlove_feed_contributions', $contributions, $feed );

		$contributor_xml = '';
		foreach ($contributions as $contribution) {
			$contributor_xml .= $this->getContributorXML( $contribution['contributor'] );
		}

		return $contributor_xml;
	}

	function feed_head_contributors() {
		global $wp_query;

		$feed = \Podlove\Model\Feed::find_one_by_slug( $wp_query->query_vars['feed'] );

		if (!$feed)
			return;

		$contributor_xml = $this->prepare_contributions_for_feed(
			\Podlove\Modules\Contributors\Model\ShowContribution::all(),
			$feed
		);

		echo apply_filters( 'podlove_feed_head_contributors', $contributor_xml );
	}

	function feed_item_contributors($podcast, $episode, $feed, $format)
	{
		$contributor_xml = $this->prepare_contributions_for_feed(
			\Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id),
			$feed
		);

		echo apply_filters( 'podlove_feed_contributors', $contributor_xml );
	}

	private function getContributorXML($contributor)
	{
		$contributor_xml = '';

		if ($contributor->visibility == 1) {

			$dom = new \Podlove\DomDocumentFragment;

			$xml = $dom->createElement('atom:contributor');
			
			// add the empty name tag
			$name = $dom->createElement('atom:name');
			$xml->appendChild($name);

			// fill name tag with escaped content
			$name_text = $dom->createTextNode($contributor->getName());
			$name->appendChild($name_text);

			if ($contributor->guid)
				$xml->appendChild($dom->createElement('atom:uri', $contributor->guid));

			$dom->appendChild($xml);

			$contributor_xml .= (string) $dom;
		}

		return $contributor_xml;
	}

	/**
	 * Allow to filter post list by contributor slug.
	 */
	function filter_by_contributor( $query )
	{
		if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'podcast')
			return;

		if (!isset($_GET['contributor']) || empty($_GET['contributor']))
			return;

		if (!$contributor = Contributor::find_one_by_id($_GET['contributor']))
			return;

		$contributions = $contributor->getContributions();
		$query->query_vars['post__in'] = array_map(function($c) {
			return is_object( $c->getEpisode() ) ? $c->getEpisode()->post_id : 0;
		}, $contributions);
	}
	
	public function was_activated( $module_name ) {
		Contributor::build();
		ContributorRole::build();
		ContributorGroup::build();
		EpisodeContribution::build();
		ShowContribution::build();
		DefaultContribution::build();
	}

	public function migrate_contributors( $module_name ) {

		$episodes = \Podlove\Episode::all();
		$posted_contributors = array();

		$args = array(
			'hierarchical'  => false,
			'labels'        => array(),
			'show_ui'       => true,
			'show_tagcloud' => true,
			'query_var'     => true,
			'rewrite'       => array( 'slug' => 'contributor' ),
		);

		register_taxonomy( 'podlove-contributors', 'podcast', $args );

		foreach(get_terms('podlove-contributors', 'orderby=count&hide_empty=0') as $contributorid => $contributor) {
			$settings = $this->get_additional_settings_for_migration($contributor->term_id);

			if (isset($settings["contributor_email"])) {
				$privateemail = $settings["contributor_email"];
			} else {
				$privateemail = "";
			}

			$contributor_infos = array( "realname" => $contributor->name,
										"publicname" => $contributor->name,
										"slug" => $contributor->slug,
										"id" => $contributor->term_id,
										"visibility" => 1,
										"privateemail" => $privateemail);

			$contributor_entry = new \Podlove\Modules\Contributors\Contributor;
			$contributor_entry->update_attributes($contributor_infos);
		}

		foreach($episodes as $episode_id => $episode_details) {
			$terms = get_the_terms($episode_details->post_id, 'podlove-contributors');
			if (isset($terms) AND !empty($terms)) {
				foreach($terms as $term_id => $term_details) {
					$posted_contributors[] = array('id' => $term_details->term_id, 'slug' => $term_details->slug);
				}
			}
			if (!empty($posted_contributors)) {
				update_post_meta( $episode_details->post_id, '_podlove_episode_contributors', json_encode($posted_contributors));
			}
		}
	}

	public static function get_additional_settings_for_migration( $term_id ) {
		$all_contributor_settings = get_option( 'podlove_contributors', array() );		
		if ( ! isset( $all_contributor_settings[ $term_id ] ) )
			$all_contributor_settings[ $term_id ] = array();
		return $all_contributor_settings[ $term_id ];
	}

	public function update_contributors($post_id)
	{
		if (!$post_id || !isset($_POST["episode_contributor"]))
			return;
		
		$episode = Episode::find_one_by_post_id($post_id);

		if (!$episode)
			return;

		foreach (\Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id) as $contribution) {
			$contribution->delete();
		}

		$position = 0;

		foreach ($_POST["episode_contributor"] as $contributor_appearance) {
			foreach ($contributor_appearance as $contributor_id => $contributor) {

				if (!$contributor_id)
					continue;

				$c = new \Podlove\Modules\Contributors\Model\EpisodeContribution;
				if( !empty( $contributor['role'] ) )
					$c->role_id = \Podlove\Modules\Contributors\Model\ContributorRole::find_one_by_slug($contributor['role'])->id;
				if( !empty( $contributor['group'] ) )
					$c->group_id = \Podlove\Modules\Contributors\Model\ContributorGroup::find_one_by_slug($contributor['group'])->id;
				$c->episode_id = $episode->id;
				$c->contributor_id = $contributor_id;
				$c->comment = $contributor['comment'];
				$c->position = $position++;
				$c->save();		
			}
		}
	}

	public function contributors_form_for_episode( $form_data )
	{
		$form_data[] = array(
			'type' => 'callback',
			'key'  => 'contributors_form_table',
			'options' => array(
				'label'    => __( 'Contributors', 'podlove' ),
				'callback' => array($this, 'contributors_form_for_episode_callback')
			),
			'position' => 850
		);

		return $form_data;
	}

	public function contributors_form_for_episode_callback() {

		$current_page = get_current_screen();
		$episode = Episode::find_one_by_post_id(get_the_ID());
		
		// determine existing contributions
		$contributions = array();
		if ($current_page->action == "add") {
			$i = 0;
			$permanent_contributors = array();
			foreach ( DefaultContribution::all() as $contribution_key => $contribution ) {
				$permanent_contributors[$contribution_key]['contributor'] = $contribution->getContributor();
				$permanent_contributors[$contribution_key]['role'] = $contribution->getRole();
				$permanent_contributors[$contribution_key]['group'] = $contribution->getGroup();
				$permanent_contributors[$contribution_key]['comment'] = $contribution->comment;
			}

			foreach ($permanent_contributors as $permanent_contributor) {
					$contrib = new \Podlove\Modules\Contributors\Model\EpisodeContribution;
					$contrib->contributor_id = $permanent_contributor['contributor']->id;

					if (isset($permanent_contributor['role'])) {
						$contrib->role = ContributorRole::find_by_id( $permanent_contributor['role']->id );
					}
					
					if (isset($permanent_contributor['group'])) {
						$contrib->group = ContributorGroup::find_by_id( $permanent_contributor['group']->id );
					}

					if (isset($permanent_contributor['comment'])) {
						$contrib->comment = $permanent_contributor['comment'];
					}

					$contributions[] = $contrib;						
			}

			// map indices to IDs
			$map = array();
			$i = 0;
			foreach ($contributions as $c) {
				$map["default" . $c->contributor_id . "_" . $i] = $c;
				$i++;
			}

		} else {
			$contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all("WHERE `episode_id` = " . $episode->id . " ORDER BY `position` ASC");

			// map indices to IDs
			$map = array();
			foreach ($contributions as $c) {
				$map[$c->id] = $c;
			}
		}

		\Podlove\Modules\Contributors\Contributors::contributors_form_table($map);
	}

	/**
	 * Contributors extension for podcast settings screen.
	 * 
	 * @param  TableWrapper $wrapper form wrapper
	 * @param  Podcast      $podcast podcast model
	 */
	public function podcast_settings_tab($tabs)
	{
		$tabs->addTab( new Settings\PodcastContributorsSettingsTab( __( 'Contributors', 'podlove' ) ) );
		$tabs->addTab( new Settings\PodcastFlattrSettingsTab( __( 'Flattr', 'podlove' ) ) );
		return $tabs;
	}

	/**
	 * @todo  this save logic belongs into the tab class
	 */
	public function save_setting($old, $new)
	{
		if (!isset($new['contributor']))
			return;

		$contributor_appearances = $new['contributor'];

		foreach (\Podlove\Modules\Contributors\Model\ShowContribution::all() as $contribution) {
			$contribution->delete();
		}

		$position = 0;
		foreach ($contributor_appearances as $contributor_appearance) {
			foreach ($contributor_appearance as $contributor_id => $contributor) {
				$c = new \Podlove\Modules\Contributors\Model\ShowContribution;

				if (isset($contributor['role'])) {
					if ($role = ContributorRole::find_one_by_slug( $contributor['role'] )) {
						$c->role_id = $role->id;
					}
				}

				if (isset($contributor['group'])) {
					if ($group = ContributorGroup::find_one_by_slug( $contributor['group'] )) {
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

	public static function contributors_form_table($current_contributions = array(), $form_base_name = 'episode_contributor') {
		$contributors_roles = \Podlove\Modules\Contributors\Model\ContributorRole::selectOptions();
		$contributors_groups = \Podlove\Modules\Contributors\Model\ContributorGroup::selectOptions();
		$cjson = array();

		// only valid contributions
		$current_contributions = array_filter($current_contributions, function($c) { return $c->contributor_id > 0; });

		$has_roles  = count( $contributors_roles ) > 0;
		$has_groups = count( $contributors_groups ) > 0;
		$can_be_commented = $form_base_name == 'podlove_contributor_defaults[contributor]' ? 0 : 1;

		foreach (\Podlove\Modules\Contributors\Model\Contributor::all() as $contributor) {
			$show_contributions = \Podlove\Modules\Contributors\Model\ShowContribution::all( "WHERE `contributor_id` = " . $contributor->id );
			if( empty( $show_contributions ) ) { 
				$cjson[$contributor->id] = array(
					'id'   => $contributor->id,
					'slug' => $contributor->slug,
					'role' => '',
					'group' => '',
					'realname' => $contributor->realname,
					'avatar' => $contributor->avatar()->setWidth(45)->image()
				);
			} else {
				foreach($show_contributions as $show_contribution) {
					$role_data = \Podlove\Modules\Contributors\Model\ContributorRole::find_one_by_id($show_contribution->role_id);
						($role_data == "" ? $role = '' : $role = $role_data->id );
					$group_data = \Podlove\Modules\Contributors\Model\ContributorGroup::find_one_by_id($show_contribution->group_id);
						($group_data == "" ? $group = '' : $group = $group_data->id );
					$cjson[$contributor->id] = array(
						'id'   => $contributor->id,
						'slug' => $contributor->slug,
						'role' => $role,
						'group' => $group,
						'realname' => $contributor->realname,
						'avatar' => $contributor->avatar()->setWidth(45)->image()
					);
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
		?>
		<div id="contributors-form">
			<table class="podlove_alternating" border="0" cellspacing="0">
				<thead>
					<tr>
						<th class="podlove-avatar-column" colspand="2">Contributor</th>
						<th></th>
						<?php echo ( $has_groups ? '<th>Group</th>'  : '' ); ?>
						<?php echo ( $has_roles ? '<th>Role</th>'  : '' ); ?>
						<?php echo ( $can_be_commented ? '<th>Public Comment</th>'  : '' ); ?>
						<th style="width: 60px">Remove</th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody id="contributors_table_body" style="min-height: 50px;">
					<tr class="contributors_table_body_placeholder" style="display: none;">
						<td><em><?php echo __('No contributors were added yet.', 'podlove') ?></em></td>
					</tr>
				</tbody>
			</table>

			<div id="add_new_contributor_wrapper">
				<input class="button" id="add_new_contributor_button" value="+" type="button" />
			</div>

			<script type="text/template" id="contributor-row-template">
			<tr class="media_file_row podlove-contributor-table" data-contributor-id="{{contributor-id}}" data-row-number="{{id}}">
				<td class="podlove-avatar-column"></td>
				<td class="podlove-contributor-column">
					<div style="min-width: 205px">
					<select name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][id]" class="chosen-image podlove-contributor-dropdown">
						<option value=""><?php echo __('Choose Contributor', 'podlove') ?></option>
						<option value="create"><?php echo __('Add New Contributor', 'podlove') ?></option>
						<?php foreach ( \Podlove\Modules\Contributors\Model\Contributor::all() as $contributor ): ?>
							<option value="<?php echo $contributor->id ?>" data-img-src="<?php echo $contributor->avatar()->setWidth(45)->url() ?>" data-contributordefaultrole="<?php echo $contributor->role ?>"><?php echo $contributor->getName(); ?></option>
						<?php endforeach; ?>
					</select>
					<a class="clickable podlove-icon-edit podlove-contributor-edit" href="<?php echo site_url(); ?>/wp-admin/edit.php?post_type=podcast&amp;page=podlove_contributors_settings_handle&amp;action=edit&contributor={{contributor-id}}"></a>
					<a class="clickable podlove-icon-plus podlove-contributor-create" href="<?php echo admin_url('edit.php?post_type=podcast&page=podlove_contributors_settings_handle&action=new') ?>"></a>
					</div>
				</td>
				<?php if( $has_groups ) : ?>
				<td>
					<select name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][group]" class="chosen podlove-group">
						<option value="">&nbsp;</option>
						<?php foreach ( $contributors_groups as $group_slug => $group_title ): ?>
							<option value="<?php echo $group_slug ?>"><?php echo $group_title ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<?php endif; ?>
				<?php if( $has_roles ) : ?>
				<td>
					<select name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][role]" class="chosen podlove-role">
						<option value="">&nbsp;</option>
						<?php foreach ( $contributors_roles as $role_slug => $role_title ): ?>
							<option value="<?php echo $role_slug ?>"><?php echo $role_title ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<?php endif; ?>
				<?php if( $can_be_commented ) : ?>
				<td>
					<input type="text" name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][comment]" class="podlove-comment" />
				</td>
				<?php endif; ?>
				<td>
					<span class="contributor_remove">
						<i class="clickable podlove-icon-remove"></i>
					</span>
				</td>
				<td class="move column-move"><i class="reorder-handle podlove-icon-reorder"></i></td>
			</tr>
			</script>

			<script type="text/javascript">
				var PODLOVE = PODLOVE || {};
				var i = 0;
				var existing_contributions = <?php
				echo json_encode(array_filter(array_map(function($c){
					// Set default role
					$role_data = \Podlove\Modules\Contributors\Model\ContributorRole::find_by_id( $c->role_id );
					if ( isset( $role_data ) ) {
						$role = $role_data->slug;
					} else {
						if ( empty( $c->role ) ) {
							$role = '';
						} else {
							$role = $c->role->slug;
						}						
					}

					// Set default group
					$group_data = \Podlove\Modules\Contributors\Model\ContributorGroup::find_by_id( $c->group_id );
					if ( isset( $group_data ) ) {
						$group = $group_data->slug;
					} else {
						if ( empty( $c->group ) ) {
							$group = '';
						} else {
							$group = $c->group->slug;
						}
					}

					if( is_object( \Podlove\Modules\Contributors\Model\Contributor::find_by_id( $c->contributor_id ) ) )
						return array( 'id' => $c->contributor_id, 'role' => $role, 'group' => $group, 'comment' => $c->comment );

					return '';

				}, $current_contributions))); ?>;

				PODLOVE.Contributors = <?php echo json_encode(array_values($cjson)); ?>;
				PODLOVE.Contributors_form_base_name = "<?php echo $form_base_name ?>";

				(function($) {
					var form_base_name = "<?php echo $form_base_name ?>";

					function update_chosen() {
						$(".chosen").chosen();
						$(".chosen-image").chosenImage();
					}

					function fetch_contributor(contributor_id) {
						contributor_id = parseInt(contributor_id, 10);

						return $.grep(PODLOVE.Contributors, function(contributor, index) {
							return parseInt(contributor.id, 10) === contributor_id;
						})[0]; // Using [0] as the returned element has multiple indexes
					}

					function contributor_dropdown_handler() {
						$('table').on('change', 'select.podlove-contributor-dropdown', function() {
							var i;
							var contributor = fetch_contributor(this.value);
							var row = $(this).closest("tr");
							var edit_button   = row.find(".podlove-contributor-edit");
							var create_button = row.find(".podlove-contributor-create");

							if (this.value == "create") {
								var create_url = $(this).parent().find(".podlove-contributor-create").attr("href");
								// show create button, just in case redirect does not work
								create_button.show();
								edit_button.hide();
								// redirect
								window.location = create_url;
								return;
							} else {
								create_button.hide();
							}

							// Check for empty contributors / for new field
							if( typeof contributor === 'undefined' ) {
								row.find(".podlove-avatar-column").html(""); // Empty avatar column and hide edit button
								row.find(".podlove-contributor-edit").hide();
								return;
							}

							i = row.data("row-number");

							// Setting data attribute and avatar field
							row.data("contributor-id", contributor.id);
							row.find(".podlove-avatar-column").html( contributor.avatar );
							// Renaming all corresponding elements after the contributor has changed 
							row.find(".podlove-contributor-dropdown").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[id]");
							row.find(".podlove-group").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[group]");
							row.find(".podlove-role").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[role]");
							row.find(".podlove-comment").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[comment]");
							edit_button.attr("href", "<?php echo site_url(); ?>/wp-admin/edit.php?post_type=podcast&page=podlove_contributors_settings_handle&action=edit&contributor=" + contributor.id);
							edit_button.show(); // Show Edit Button
						});
					}

					$(document).ready(function() {
						var i = 0;

						contributor_dropdown_handler();

						$("#contributors-form table").podloveDataTable({
							rowTemplate: "#contributor-row-template",
							data: existing_contributions,
							dataPresets: PODLOVE.Contributors,
							sortableHandle: ".reorder-handle",
							addRowHandle: "#add_new_contributor_button",
							deleteHandle: ".contributor_remove",
							onRowLoad: function(o) {
								o.row = o.row.replace(/\{\{contributor-id\}\}/g, o.object.id);
								o.row = o.row.replace(/\{\{id\}\}/g, i);
								i++;
							},
							onRowAdd: function(o, init) {
								var row = $("#contributors_table_body tr:last");

								row.find('td.podlove-avatar-column').html(o.object.avatar);
								// select contributor in contributor-dropdown
								row.find('select.podlove-contributor-dropdown option[value="' + o.object.id + '"]').attr('selected',true);
								// select default role
								row.find('select.podlove-role option[value="' + o.entry.role + '"]').attr('selected',true);
								// select default group
								row.find('select.podlove-group option[value="' + o.entry.group + '"]').attr('selected',true);
								// set comment
								row.find('input.podlove-comment').val(o.entry.comment);

								// Update Chosen before we focus on the new contributor
								update_chosen();
								var new_row_id = row.find('select.podlove-contributor-dropdown').last().attr('id');	
								
								// Focus new contributor
								if (!init) {
									$("#" + new_row_id + "_chzn").find("a").focus();
								}
							},
							onRowDelete: function(tr) {
								var object_id = tr.data("object-id"),
								    ajax_action = "podlove-contributors-delete-";

								switch (form_base_name) {
									case "podlove_podcast[contributor]":
										ajax_action += "podcast";
										break;
									case "podlove_contributor_defaults[contributor]":
										ajax_action += "default";
										break;
									case "episode_contributor":
										ajax_action += "episode";
										break;
									default:
										console.log("Error when deleting social/donation entry: unknows form type '" + form_base_name + "'");
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

	public function add_new_podcast_columns($columns)
	{
			$keys = array_keys($columns);
		    $insertIndex = array_search('author', $keys) + 1; // after author column

		    // insert contributors at that index
		    $columns = array_slice($columns, 0, $insertIndex, true) +
		           array("contributors" => __('Contributors', 'podlove')) +
			       array_slice($columns, $insertIndex, count($columns) - 1, true);

		    return $columns;
	}

	function manage_podcast_columns($column_name) {
	    switch ($column_name) {
	    	case 'contributors':
	    		$episode = \Podlove\Model\Episode::find_one_by_post_id(get_the_ID());

	    		if (!$episode)
	    			return;

	        	$contributors = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id);
	        	$contributor_list = "";
	        	
	        	foreach ($contributors as $contributor_id => $contributor) {
	        		$contributor_details = $contributor->getContributor();

	        		if( is_object( $contributor_details ) )
	        			$contributor_list = $contributor_list."<a href=\"".site_url()."/wp-admin/edit.php?post_type=podcast&contributor=".$contributor_details->id."\">".$contributor_details->getName()."</a>, ";
	        	}

	        	echo substr($contributor_list, 0, -2);

	    	break;
	    }
	}

	public function admin_print_styles() {
		wp_register_script(
			'podlove_contributor_jquery_visible',
			$this->get_module_url() . '/js/jquery.visible.min.js',
			array( 'jquery', 'jquery-ui-tabs' ),
			\Podlove\get_plugin_header( 'Version' )
		);
		wp_enqueue_script('podlove_contributor_jquery_visible');
	}

	public function delete_podcast_contributor() {
		$object_id = (int) $_REQUEST['object_id'];

		if (!$object_id)
			return;

		if ($service = ShowContribution::find_by_id($object_id))
			$service->delete();
	}

	public function delete_default_contributor() {
		$object_id = (int) $_REQUEST['object_id'];

		if (!$object_id)
			return;

		if ($service = DefaultContribution::find_by_id($object_id))
			$service->delete();
	}

	public function delete_episode_contributor() {
		$object_id = (int) $_REQUEST['object_id'];

		if (!$object_id)
			return;

		if ($service = EpisodeContribution::find_by_id($object_id))
			$service->delete();
	}

	public function feed_settings( $wrapper ) {
		$contributors_roles = \Podlove\Modules\Contributors\Model\ContributorRole::all();
		$contributors_groups = \Podlove\Modules\Contributors\Model\ContributorGroup::all();
		$option_name = 'podlove_feed_' . $_REQUEST['feed'] . '_contributor_filter';

		$selected_filter = get_option( $option_name );

		if ( !$selected_filter ) {
			$selected_filter = array(
					'group' => NULL,
					'role' => NULL
				);
		}

		$wrapper->subheader( __( 'Contributors', 'podlove' ) );
		$wrapper->callback( 'services_form_table', array(
			'label' => __( 'Contributor Filter', 'podlove' ),
			'callback' => function() use ( $contributors_roles, $contributors_groups, $selected_filter ) {
				?>
					<select name="podlove_feed[contributor_filter][group]" id="">
						<option value=""></option>
						<?php
							foreach ($contributors_groups as $group) {
								echo "<option value='" . $group->id . "' " . ( $group->id == $selected_filter['group'] ? 'selected' : '' ) . ">" . $group->title . "</option>";
							}
						?>
					</select>
					<?php echo __('Group', 'podlove') ?>

					<select name="podlove_feed[contributor_filter][role]" id="">
						<option value=""></option>
						<?php
							foreach ($contributors_roles as $role) {
								echo "<option value='" . $role->id . "' " . ( $role->id == $selected_filter['role'] ? 'selected' : '' ) . ">" . $role->title . "</option>";
							}
						?>
					</select>
					<?php echo __('Role', 'podlove') ?>
					<p>
						<span class="description"><?php echo __('Limit contributors to the given group and/or role.', 'podlove') ?></span>
					</p>
				<?php
			}		
		) );

		return $wrapper;
	}

	public function feed_process( $feed_id, $action ) {
		if ( !$_POST )
			return;

		$group = $_POST['podlove_feed']['contributor_filter']['group'];
		$role = $_POST['podlove_feed']['contributor_filter']['role'];
		$option_name = 'podlove_feed_' . $feed_id . '_contributor_filter';

		update_option( $option_name , array( 'group' => $group, 'role' => $role ) );
	}

	public function adn_tags_description( $description ) {
		return apply_filters( 'podlove_adn_tags_description_contributors', $description );
	}

	public function adn_example_data( $data, $post_id, $selected_role, $selected_group ) {
		return apply_filters( 'podlove_adn_example_data_contributors', $data, $post_id, $selected_role, $selected_group );
	}

	public function adn_tags( $text, $post_id, $selected_role, $selected_group ) {
		return apply_filters( 'podlove_adn_tags_contributors_contributors', $text, $post_id, $selected_role, $selected_group );
	}
}
