var PODLOVE = PODLOVE || {};

(function($) {
	function update_chosen() {
		$(".chosen-related-episodes").chosen({ width: '100%' , search_contains: true});
	}

	$(document).ready(function() {
		var i = 0;

		$("#episode-relation-form table").podloveDataTable({
			rowTemplate: "#episode-relation-row-template",
			data: PODLOVE.related_episodes_existing_episode_relations,
			dataPresets: PODLOVE.related_episodes_existing_episodes,
			addRowHandle: "#add_new_episode_relation_button",
			deleteHandle: ".episode_relation_remove",
			onRowLoad: function(o) {
				o.row = o.row.replace(/\{\{id\}\}/g, i);
				
				i++;
			},
			onRowAdd: function(o, init) {
				var row = $("#episode_relation_table_body tr:last");

				var new_row_id = row.find('select.podlove_episode_relation_episodes_dropdown').last().attr('id');
				row.find('select.podlove_episode_relation_episodes_dropdown option[value="' + o.entry.id + '"]').attr('selected',true);

				update_chosen();

				// Focus new contributor
				if (!init) {
					$("#" + new_row_id + "_chzn").find("a").focus();
				}
			},
			onRowDelete: function(tr) {
				
			}
		});
	});
}(jQuery));