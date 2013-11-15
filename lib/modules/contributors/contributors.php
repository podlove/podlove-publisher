<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Model\Episode;
use \Podlove\Modules\Contributors\Model\Contributor;
use \Podlove\Modules\Contributors\Model\ContributorRole;
use \Podlove\Modules\Contributors\Model\EpisodeContribution;
use \Podlove\Modules\Contributors\Model\ShowContribution;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_module_was_activated_contributors', array( $this, 'was_activated' ) );
		add_action( 'podlove_episode_form_beginning', array( $this, 'contributors_form_for_episode' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_contributors' ), 10, 2 );
		add_action( 'podlove_podcast_form', array( $this, 'podcast_form_extension' ), 10, 2 );
		add_action( 'update_option_podlove_podcast', array( $this, 'save_setting' ), 10, 2 );
	
		// register shortcodes
		new Shortcodes;	

		// register settings page
		add_action('podlove_register_settings_pages', function($settings_parent) {
			new Settings\Contributors($settings_parent);
			new Settings\ContributorRoles($settings_parent);
		});
	}
	
	public function was_activated( $module_name ) {
		Contributor::build();
		ContributorRole::build();
		EpisodeContribution::build();
		ShowContribution::build();

		if (!ContributorRole::count()) {
			$default_contributors = array(
				'moderator' => 'Moderator',
				'comoderator' => 'Co-Moderator',
				'guest' => 'Guest',
				'shownotes' => 'Shownotes',
				'chatmod' => 'Chat Moderator'
			);
			foreach ($default_contributors as $slug => $title) {
				$c = new ContributorRole;
				$c->update_attributes(array('slug' => $slug, 'title' => $title));
				$c->save();
			}
		}
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
										"showpublic" => 1,
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
		foreach ($_POST["episode_contributor"] as $contributor_id => $contributor) {
			$c = new \Podlove\Modules\Contributors\Model\EpisodeContribution;
			$c->role_id = \Podlove\Modules\Contributors\Model\ContributorRole::find_one_by_slug($contributor['role'])->id;
			$c->episode_id = $episode->id;
			$c->contributor_id = $contributor_id;
			$c->position = $position++;
			$c->save();
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
					$permanent_contributors = \Podlove\Modules\Contributors\Model\Contributor::find_all_by_property("permanentcontributor", "1");
					foreach ($permanent_contributors as $permanent_contributor) {
						$contrib = new \Podlove\Modules\Contributors\EpisodeContribution;
						$contrib->contributor_id = $permanent_contributor->id;
						$contrib->role = \Podlove\Modules\Contributors\Model\ContributorRole::find_by_id($permanent_contributor->role_id);
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
	public function podcast_form_extension($wrapper, $podcast)
	{
		$wrapper->subheader(
			__( 'Contributors', 'podlove' ),
			__( 'You may define contributors for the whole podcast.', 'podlove' )
		);

    	$wrapper->callback( 'contributors', array(
			'label'    => __( 'Contributors', 'podlove' ),
			'callback' => array( $this, 'podcast_form_extension_form' )
		) );
	}

	public function podcast_form_extension_form()
	{
		$contributions = ShowContribution::all();
		self::contributors_form_table($contributions, 'podlove_podcast[contributor]');
	}

	public function save_setting($old, $new)
	{
		if (!isset($new['contributor']))
			return;

		$contributors = $new['contributor'];

		foreach (ShowContribution::all() as $contribution) {
			$contribution->delete();
		}

		$position = 0;
		foreach ($contributors as $contributor_id => $contributor) {
			$c = new ShowContribution;
			$c->role_id = ContributorRole::find_one_by_slug($contributor['role'])->id;
			$c->contributor_id = $contributor_id;
			$c->position = $position++;
			$c->save();
		}
	}

	public static function contributors_form_table($current_contributions = array(), $form_base_name = 'episode_contributor') {
		$contributors_roles = \Podlove\Modules\Contributors\Model\ContributorRole::selectOptions();

		$cjson = array();
		foreach (\Podlove\Modules\Contributors\Model\Contributor::all() as $contributor) {
			$cjson[$contributor->id] = array(
				'id'   => $contributor->id,
				'slug' => $contributor->slug,
				'role' => $contributor->role,
				'realname' => $contributor->realname,
				'permanentcontributor' => $contributor->permanentcontributor
			);
		}

		// override contributor roles with scoped roles
		foreach ($current_contributions as $current_contribution) {
			if ($role = $current_contribution->getRole()) {
				$cjson[$current_contribution->contributor_id]['role'] = $role->slug;
			}
		}
		?>
		<div id="contributors-form">
			<table class="podlove_alternating" style="margin-top: 1em;" border="0" cellspacing="0">
				<thead>
					<tr>
						<th>Contributor</th>
						<th>Role</th>
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
				<select id="add_new_contributor_selector" class="contributor-dropdown chosen">
					<option value="0"><?php echo __('Choose Contributor', 'podlove') ?></option>
					<?php foreach ( \Podlove\Modules\Contributors\Model\Contributor::all() as $contributor ): ?>
						<?php if (!in_array($contributor->id, array_map(function($c){ return $c->contributor_id; }, $current_contributions), true)): ?>
							<option value="<?php echo $contributor->id ?>" data-contributordefaultrole="<?php echo $contributor->role ?>"><?php echo $contributor->realname; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<input class="button" id="add_new_contributor_button" value="+" type="button" />
			</div>

			<script type="text/template" id="contributor-row-template">
			<tr class="media_file_row" data-contributor-id="{{contributor-id}}">
				<td>{{contributor-name}}</td>
				<td>
					<select name="<?php echo $form_base_name ?>[{{contributor-id}}][role]" class="chosen">
						<option value=""><?php echo __( '- none -', 'podlove' ) ?></option>
						<?php foreach ( $contributors_roles as $role_slug => $role_title ): ?>
							<option value="<?php echo $role_slug ?>"><?php echo $role_title ?></option>
						<?php endforeach; ?>
					</select>
				</td>
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
				var existing_contributions = [<?php echo implode(",", array_map(function($c){ return $c->contributor_id; }, $current_contributions)) ?>];

				PODLOVE.Contributors = <?php echo json_encode($cjson); ?>;

				(function($) {

					function determine_blank_slate_visibility() {
						var placeholder = $(".contributors_table_body_placeholder");

						if ($('#contributors_table_body tr').size() > 0) {
							placeholder.hide();
						} else {
							placeholder.show();
						}
					}

					function determine_contributor_selector_visibility() {
						var contributor_selector = $("#add_new_contributor_selector_chzn, #add_new_contributor_button");

						if ($('#add_new_contributor_selector option').size() == 0) {
							contributor_selector.hide();
						} else {
							contributor_selector.show();
						}
					}

					function update_contributor_list() {
						$(".chosen").chosen().trigger("liszt:updated");
						determine_blank_slate_visibility();
						determine_contributor_selector_visibility();
					}

					function add_contributor_row(contributor) {
						var row = '';

						// add contributor to table
						row = $("#contributor-row-template").html();
						row = row.replace(/\{\{contributor-name\}\}/g, contributor.realname);
						row = row.replace(/\{\{contributor-id\}\}/g, contributor.id);
						el = $("#contributors_table_body").append(row);
						
						var new_row = $("#contributors_table_body tr:last");

						// select default role
						new_row.find('select option[value="' + contributor.role + '"]').attr('selected',true);
					}

					$(document).on('click', "#add_new_contributor_button", function() {
						var selected_contributor = $("#add_new_contributor_selector :selected"),
							contributor_id = selected_contributor.val(),
							contributor = PODLOVE.Contributors[contributor_id];

						add_contributor_row(contributor);

						// remove contributor from select
						selected_contributor.remove();

						update_contributor_list();
					});

					$(document).on('click', '.contributor_remove',  function() {
						var contributor_id = $(this).closest("tr").data('contributor-id'),
							contributor = PODLOVE.Contributors[contributor_id];

						// remove this contributor row
						$(this).closest("tr").remove();

						// add to list of available contributors
						var option = '<option value="' + contributor_id + '">' + contributor.realname + '</option>';

						$("#add_new_contributor_selector").append(option);

						update_contributor_list();
					});

					$(document).ready(function() {

						$.each(existing_contributions, function(index, contributor_id) {
							add_contributor_row(PODLOVE.Contributors[contributor_id]);
						});
						
						update_contributor_list();

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
					});
				}(jQuery));

			</script>
		</div>
		<?php		
	}

}