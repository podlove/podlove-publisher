<?php
namespace Podlove\Modules\RelatedEpisodes;

use Podlove\Model;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use Podlove\Modules\RelatedEpisodes\TemplateExtensions;

class Related_Episodes extends \Podlove\Modules\Base {

		protected $module_name = 'Related Episodes';
		protected $module_description = 'Create related pairs of episodes.';
		protected $module_group = 'metadata';

		public function load() {
			add_action( 'podlove_module_was_activated_related_episodes', array( $this, 'was_activated' ) );
			add_filter( 'podlove_episode_form_data', array( $this, 'episode_relation_form' ), 10, 2 );
			add_action( 'save_post', array( $this, 'update_episode_relations' ), 10, 2 );

			\Podlove\Template\Episode::add_accessor(
				'relatedEpisodes', array('\Podlove\Modules\RelatedEpisodes\TemplateExtensions', 'accessorRelatedEpisodes'), 5
			);
		}

		public function was_activated( $module_name ) {
			EpisodeRelation::build();
		}

		public function update_episode_relations($post_id) {
			if ( !$post_id || ! isset($_POST['_podlove_meta']['related_episodes']) )
				return;

			$new_related_episodes = $_POST['_podlove_meta']['related_episodes'];
			$episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);


			foreach (\Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id.' OR right_episode_id = '.$episode->id) as $episode_relation) {
				$episode_relation->delete();
			}

			foreach ($new_related_episodes as $episode_relation) {
				$e = new \Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
				$e->left_episode_id = $episode->id;
				$e->right_episode_id = $episode_relation;
				$e->save();
			}
		}

		public function episode_relation_form($form_data) {
			$form_data[] = array(
				'type' => 'callback',
				'key'  => 'episode_relation_form_table',
				'options' => array(
					'label'    => __( 'Episode Relations', 'podlove' ),
					'callback' => array($this, 'episode_relation_form_callback')
				),
				'position' => 900
			);

			return $form_data;
		}

		public function episode_relation_form_callback($form_base_name = '_podlove_meta') {
			$existing_episodes = array();
			foreach (\Podlove\Model\Episode::find_all_by_time() as $episode ) {
				$existing_episodes[$episode->id] = get_the_title($episode->post_id);
			}
			$episode = \Podlove\Model\Episode::find_one_by_post_id(get_the_ID());

			$existing_episode_relations = \Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation::get_related_episodes($episode->id);
			?>
			<div id="episode-relation-form">
				<table class="podlove_alternating" border="0" cellspacing="0">
					<thead>
						<tr>
							<th><?php _e('Related Episode', 'podlove'); ?></th>
							<th><?php _e('Remove', 'podlove'); ?></th>
						</tr>
					</thead>
					<tbody id="episode_relation_table_body" style="min-height: 50px;">
						<tr class="episode_relation_table_body_placeholder" style="display: none;">
							<td><em><?php echo __('No episode relations were added yet.', 'podlove') ?></em></td>
						</tr>
					</tbody>
				</table>

				<div id="add_new_episode_relation_wrapper">
					<input class="button" id="add_new_episode_relation_button" value="+" type="button" />
				</div>
			</div>

			<script type="text/template" id="episode-relation-row-template">
			<tr class="media_file_row podlove-episode-relation-table">
				<td>
					<select name="<?php echo $form_base_name ?>[related_episodes][{{id}}]" id="<?php echo $form_base_name ?>_related_episodes_{{id}}"  class="chosen podlove_episode_relation_episodes_dropdown"></select>
				</td>
				<td>
					<span class="episode_relation_remove">
						<i class="clickable podlove-icon-remove"></i>
					</span>
				</td>
			</tr>
			</script>

			<script type="text/javascript">
				(function($) {
					var form_base_name = "<?php echo $form_base_name ?>";
					var existing_episodes = <?php echo json_encode($existing_episodes); ?>;
					var existing_episode_relations = <?php echo json_encode($existing_episode_relations); ?>;

					var populate_episode_dropdown = function () {
						$("select.podlove_episode_relation_episodes_dropdown").each( function () {
							$(this).empty();
							var that = $(this);

							$.each( existing_episodes, function (episode_id, title) {
								that.append( '<option value="'+episode_id+'">'+title+'</option>' );
							} );
						} );
					}

					function update_chosen() {
						$(".chosen").chosen({ width: '100%' });
						$(".chosen-image").chosenImage();
					}

					$(document).ready(function() {
						var i = 0;

						$("#episode-relation-form table").podloveDataTable({
							rowTemplate: "#episode-relation-row-template",
							data: existing_episode_relations,
							dataPresets: existing_episodes,
							addRowHandle: "#add_new_episode_relation_button",
							onRowLoad: function(o) {
								o.row = o.row.replace(/\{\{id\}\}/g, i);
								
								i++;
								populate_episode_dropdown();
							},
							onRowAdd: function(o, init) {
								var row = $("#episode_relation_table_body tr:last");

								populate_episode_dropdown();

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
			</script>
			<?php
		}

}