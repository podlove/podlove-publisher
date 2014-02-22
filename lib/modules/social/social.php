<?php 
namespace Podlove\Modules\Social;

use \Podlove\Modules\Social\Model\Service;

use \Podlove\Modules\Social\Settings\PodcastSettingsTab;

class Social extends \Podlove\Modules\Base {

	protected $module_name = 'Social';
	protected $module_description = 'Manage social media accounts.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_module_was_activated_social', array( $this, 'was_activated' ) );
		add_action( 'podlove_podcast_settings_tabs', array( $this, 'podcast_settings_tab' ) );
	}

	public function was_activated( $module_name ) {
		Service::build();
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

	public static function services_form_table($current_services = array(), $form_base_name = 'episode_services') {
		$cjson = array();

		foreach (\Podlove\Modules\Social\Model\Service::all() as $service) {
			$cjson[$contributor->id] = array(
				'id'   => $contributor->id,
				
			);			
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

}