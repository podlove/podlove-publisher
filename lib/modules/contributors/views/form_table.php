<?php
use \Podlove\Modules\Contributors\Contributors;
?>
<div id="contributors-form">
	<table class="podlove_alternating" border="0" cellspacing="0">
		<thead>
			<tr>
				<th class="podlove-avatar-column" colspand="2"><?php echo __('Contributor', 'podlove') ?></th>
				<th></th>
				<?php echo $has_groups       ? '<th>' . __('Group', 'podlove') . '</th>'           : ''; ?>
				<?php echo $has_roles        ? '<th>' . __('Role', 'podlove') . '</th>'            : ''; ?>
				<?php echo $can_be_commented ? '<th>' . __('Public Comment', 'podlove') . '</th>'  : ''; ?>
				<th style="width: 60px"><?php echo __('Remove', 'podlove') ?></th>
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
				<?php foreach ( $contributors as $contributor ): ?>
					<option value="<?php echo $contributor->id ?>" data-img-src="<?php echo $contributor->avatar()->setWidth(10)->url() ?>" data-contributordefaultrole="<?php echo $contributor->role ?>"><?php echo $contributor->getName(); ?></option>
				<?php endforeach; ?>
			</select>
			<a class="clickable podlove-icon-edit podlove-contributor-edit"   href="<?php echo Contributors::get_edit_contributor_url("{{contributor-id}}"); ?>"></a>
			<a class="clickable podlove-icon-plus podlove-contributor-create" href="<?php echo Contributors::get_create_contributor_url() ?>"></a>
			</div>
		</td>
		<?php if ($has_groups) : ?>
		<td>
			<select name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][group]" class="chosen podlove-group">
				<option value="">&nbsp;</option>
				<?php foreach ( $contributors_groups as $group_slug => $group_title ): ?>
					<option value="<?php echo $group_slug ?>"><?php echo $group_title ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<?php endif; ?>
		<?php if ($has_roles) : ?>
		<td>
			<select name="<?php echo $form_base_name ?>[{{id}}][{{contributor-id}}][role]" class="chosen podlove-role">
				<option value="">&nbsp;</option>
				<?php foreach ( $contributors_roles as $role_slug => $role_title ): ?>
					<option value="<?php echo $role_slug ?>"><?php echo $role_title ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<?php endif; ?>
		<?php if ($can_be_commented) : ?>
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
		var existing_contributions = <?php echo json_encode($existing_contributions); ?>;

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
					edit_button.attr("href", "<?php echo site_url(); ?>/wp-admin/admin.php?page=podlove_contributor_settings&action=edit&contributor=" + contributor.id);
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