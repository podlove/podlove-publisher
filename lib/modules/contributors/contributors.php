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
		add_action( 'podlove_module_was_activated_contributors', array( $this, 'was_activated' ) );
		add_action( 'podlove_episode_form_beginning', array( $this, 'contributors_form_for_episode' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_contributors' ), 10, 2 );
		add_action( 'podlove_podcast_settings_tabs', array( $this, 'podcast_settings_tab' ) );
		add_action( 'update_option_podlove_podcast', array( $this, 'save_setting' ), 10, 2 );
		add_filter( 'parse_query', array($this, 'filter_by_contributor') );

		add_filter('manage_edit-podcast_columns', array( $this, 'add_new_podcast_columns' ) );
		add_action('manage_podcast_posts_custom_column', array( $this, 'manage_podcast_columns' ) );
	
		add_action('rss2_head', array($this, 'feed_head_contributors'));
		add_action('podlove_append_to_feed_entry', array($this, 'feed_item_contributors'), 10, 4);

		add_action('podlove_dashboard_statistics', array($this, 'dashboard_statistics_row'));

		add_action('podlove_xml_export', array($this, 'expandExportFile'));
		add_action('podlove_xml_import', array($this, 'expandImport'));
		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		add_filter('podlove_twig_file_loader', function($file_loader) {
			$file_loader->addPath(implode(DIRECTORY_SEPARATOR, array(\Podlove\PLUGIN_DIR, 'lib', 'modules', 'contributors', 'templates')), 'contributors');
			return $file_loader;
		});

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
			new Settings\ContributorSettings($settings_parent);
		});
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

		// filter by role
		if (isset($args['role']) && $args['role'] != 'all') {
			$role = $args['role'];
			$contributions = array_filter($contributions, function($c) use ($role) {
				return strtolower($role) == $c->getRole()->slug;
			});
		}

		// filter by group
		if (isset($args['group']) && $args['group'] != 'all') {
			$group = $args['group'];
			$contributions = array_filter($contributions, function($c) use ($group) {
				return strtolower($group) == $c->getGroup()->slug;
			});
		}

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
				}
			}
			return $groups;
		} else {
			return array_map(function($contribution) {
				return new Template\Contributor($contribution->getContributor(), $contribution);
			}, $contributions);
		}
	}

	/**
	 * Expands "Import/Export" module: export logic
	 */
	public function expandExportFile(\SimpleXMLElement $xml) {
		Modules\ImportExport\Exporter::exportTable($xml, 'contributors', 'contributor', '\Podlove\Modules\Contributors\Model\Contributor');
		Modules\ImportExport\Exporter::exportTable($xml, 'contributor-groups', 'contributor-group', '\Podlove\Modules\Contributors\Model\ContributorGroup');
		Modules\ImportExport\Exporter::exportTable($xml, 'contributor-roles', 'contributor-role', '\Podlove\Modules\Contributors\Model\ContributorRole');
		Modules\ImportExport\Exporter::exportTable($xml, 'contributor-episode-contributions', 'contributor-episode-contribution', '\Podlove\Modules\Contributors\Model\EpisodeContribution');
		Modules\ImportExport\Exporter::exportTable($xml, 'contributor-show-contributions', 'contributor-show-contribution', '\Podlove\Modules\Contributors\Model\ShowContribution');
	}

	/**
	 * Expands "Import/Export" module: import logic
	 */
	public function expandImport($xml) {
		Modules\ImportExport\Importer::importTable($xml, 'contributor', '\Podlove\Modules\Contributors\Model\Contributor');
		Modules\ImportExport\Importer::importTable($xml, 'contributor-group', '\Podlove\Modules\Contributors\Model\ContributorGroup');
		Modules\ImportExport\Importer::importTable($xml, 'contributor-role', '\Podlove\Modules\Contributors\Model\ContributorRole');
		Modules\ImportExport\Importer::importTable($xml, 'contributor-episode-contribution', '\Podlove\Modules\Contributors\Model\EpisodeContribution');
		Modules\ImportExport\Importer::importTable($xml, 'contributor-show-contribution', '\Podlove\Modules\Contributors\Model\ShowContribution');
	}

	public function dashboard_statistics_row() {
		$contributions = EpisodeContribution::all();
		$contributions_count = count($contributions);

		$absolute_gender_numbers = array(
			'female' => count(array_filter($contributions, function($c) { return (is_object($c->getContributor()) && $c->getContributor()->gender == 'female'); })),
			'male'   => count(array_filter($contributions, function($c) { return (is_object($c->getContributor()) && $c->getContributor()->gender == 'male'); }))
		);

		$relative_gender_numbers = array_map(function($abs) use ($contributions_count) {
			return $contributions_count > 0 ? $abs / $contributions_count * 100 : 0;
		}, $absolute_gender_numbers);

		// sort by percentage (high to low)
		arsort( $relative_gender_numbers );
		?>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo __('Genders', 'podlove') ?>
			</td>
			<td>
				<?php
				echo implode(', ', array_map(function($percent, $gender) {
					return round($percent) . "% " . $gender;
				}, $relative_gender_numbers, array_keys($relative_gender_numbers)));
				?>
			</td>
		</tr>
		<?php
	}

	function feed_head_contributors() {
		$contributor_xml = '';
		foreach (\Podlove\Modules\Contributors\Model\ShowContribution::all() as $contribution) {
			$contributor = $contribution->getContributor();

			if( !is_object( $contributor ) )
				return;

			$contributor_xml .= $this->getContributorXML( $contributor );
		}	
		echo apply_filters( 'podlove_feed_head_contributors', $contributor_xml );	
	}

	function feed_item_contributors($podcast, $episode, $feed, $format) {
		$contributor_xml = '';
		foreach (\Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id) as $contribution) {
			$contributor = $contribution->getContributor();

			if( !is_object( $contributor ) )
				return;

			$contributor_xml .= $this->getContributorXML( $contributor );
		}
		echo apply_filters( 'podlove_feed_contributors', $contributor_xml );
	}

	private function getContributorXML($contributor)
	{
		$contributor_xml = '';

		if ($contributor->visibility == 1) {
			$contributor_xml .= "<atom:contributor>\n";
			$contributor_xml .= "	<atom:name>" . $contributor->getName() . "</atom:name>\n";

			if ($contributor->guid)
				$contributor_xml .= "	<atom:uri>" . $contributor->guid . "</atom:uri>\n";

			$contributor_xml .= "</atom:contributor>\n";
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

	public function contributors_form_for_episode( $wrapper ) {
		$wrapper->callback( 'contributors_form_table', array(
			'label'    => __( 'Contributors', 'podlove' ),
			'callback' => function() {

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

				} else {
					$contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all("WHERE `episode_id` = " . $episode->id . " ORDER BY `position` ASC");
				}

				echo '</table>';
				\Podlove\Modules\Contributors\Contributors::contributors_form_table($contributions);
				echo '<table class="form-table">';
			}
		) );		
	}

	/**
	 * Contributors extension for podcast settings screen.
	 * 
	 * @param  TableWrapper $wrapper form wrapper
	 * @param  Podcast      $podcast podcast model
	 */
	public function podcast_settings_tab($tabs)
	{
		$tabs->addTab( new Settings\PodcastSettingsTab( __( 'Contributors', 'podlove' ) ) );
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

				$c->contributor_id = $contributor_id;
				$c->comment = $contributor['comment'];
				$c->position = $position++;
				$c->save();
			}
		}
	}

	public static function contributors_form_table($current_contributions = array(), $form_base_name = 'episode_contributor') {
		$contributors_roles = \Podlove\Modules\Contributors\Model\ContributorRole::selectOptions();
		$contributors_groups = \Podlove\Modules\Contributors\Model\ContributorGroup::selectOptions();
		$cjson = array();

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
					'avatar' => $contributor->getAvatar("35px")
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
						'avatar' => $contributor->getAvatar("35px")
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
			<tr class="media_file_row podlove-contributor-table" data-contributor-id="{{contributor-id}}">
				<td class="podlove-avatar-column"></td>
				<td class="podlove-contributor-column">
					<select name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][id]" class="chosen-image podlove-contributor-dropdown">
						<option value=""><?php echo __('Choose Contributor', 'podlove') ?></option>
						<?php foreach ( \Podlove\Modules\Contributors\Model\Contributor::all() as $contributor ): ?>
							<option value="<?php echo $contributor->id ?>" data-img-src="<?php echo $contributor->getAvatarUrl("10px") ?>" data-contributordefaultrole="<?php echo $contributor->role ?>"><?php echo $contributor->getName(); ?></option>
						<?php endforeach; ?>
					</select>
					<a class="clickable podlove-icon-edit podlove-contributor-edit" href="<?php echo site_url(); ?>/wp-admin/edit.php?post_type=podcast&amp;page=podlove_contributors_settings_handle&amp;action=edit&contributor={{contributor-id}}"></a>
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
				echo json_encode(array_map(function($c){
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

				}, $current_contributions)); ?>;

				PODLOVE.Contributors = <?php echo json_encode(array_values($cjson)); ?>;
				PODLOVE.Contributors_form_base_name = "<?php echo $form_base_name ?>";

				(function($) {

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

					function add_new_contributor() {
						var row = '';
						row = $("#contributor-row-template").html();
						var new_row = $("#contributors_table_body");
						new_row.append(row);
						
						// Update Chosen before we focus on the new contributor
						update_chosen();
						var new_row_id = new_row.find('select.podlove-contributor-dropdown').last().attr('id');	
						contributor_dropdown_handler();
						
						// Focus new contributor
						$("#" + new_row_id + "_chzn").find("a").focus();
					}

					function add_contributor_row(contributor, role, group, comment) {
						var row = '';

						// add contributor to table
						row = $("#contributor-row-template").html();
						row = row.replace(/\{\{contributor-id\}\}/g, contributor.id);
						row = row.replace(/\{\{id\}\}/g, i);
						$("#contributors_table_body").append(row);
						i++;
						
						var new_row = $("#contributors_table_body tr:last");

						new_row.find('td.podlove-avatar-column').html(contributor.avatar);
						// select contributor in contributor-dropdown
						new_row.find('select.podlove-contributor-dropdown option[value="' + contributor.id + '"]').attr('selected',true);
						// select default role
						new_row.find('select.podlove-role option[value="' + role + '"]').attr('selected',true);
						// select default group
						new_row.find('select.podlove-group option[value="' + group + '"]').attr('selected',true);
						// set comment
						new_row.find('input.podlove-comment').val(comment);
					}

					function contributor_dropdown_handler() {
						$('select.podlove-contributor-dropdown').change(function() {
							contributor = fetch_contributor(this.value);
							row = $(this).parent().parent();

							// Check for empty contributors / for new field
							if( typeof contributor === 'undefined' ) {
								row.find(".podlove-avatar-column").html(""); // Empty avatar column and hide edit button
								row.find(".podlove-contributor-edit").hide();
								return;
							}

							// Setting data attribute and avatar field
							row.data("contributor-id", contributor.id);
							row.find(".podlove-avatar-column").html( contributor.avatar );
							// Renaming all corresponding elements after the contributor has changed 
							row.find(".podlove-contributor-dropdown").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[id]");
							row.find(".podlove-group").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[group]");
							row.find(".podlove-role").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[role]");
							row.find(".podlove-comment").attr("name", PODLOVE.Contributors_form_base_name + "[" + i + "]" + "[" + contributor.id + "]" + "[comment]");
							row.find(".podlove-contributor-edit").attr("href", "<?php echo site_url(); ?>/wp-admin/edit.php?post_type=podcast&page=podlove_contributors_settings_handle&action=edit&contributor=" + contributor.id);
							row.find(".podlove-contributor-edit").show(); // Show Edit Button
							i++; // continue using "i" which was already used to add the existing contributions
						});
					}

					function add_contribution( contributor ) {
						add_contributor_row(fetch_contributor(contributor.id), contributor.role, contributor.group, contributor.comment);

					}

					$(document).on('click', "#add_new_contributor_button", function() {
						add_new_contributor();
					});

					$(document).on('click', '.contributor_remove',  function() {
						$(this).closest("tr").remove();
					});	

					$("#podlove_podcast").on('click', 'h3.hndle',  function() {
						$("#contributors_table_body").empty();
						$.each(existing_contributions, function(index, contributor) {
							add_contribution(contributor);
						});
						update_chosen();
					});	

					$(document).ready(function() {

						$.each(existing_contributions, function(index, contributor) {
							add_contribution(contributor);
						});

						$("#contributors_table_body td").each(function(){
						    $(this).css('width', $(this).width() +'px');
						});

						$("#contributors_table_body").sortable({
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

						contributor_dropdown_handler();
						update_chosen();
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
}