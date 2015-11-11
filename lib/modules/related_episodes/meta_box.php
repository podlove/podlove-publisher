<?php
namespace Podlove\Modules\RelatedEpisodes;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;

class MetaBox {

	public function __construct() {
		add_action('add_meta_boxes_podcast', [$this, 'add_meta_box']);
		add_action('save_post_podcast', [$this, 'save_post']);
	}

	public function add_meta_box() {
		add_meta_box(
			/* $id       */ 'podlove_podcast_related_episodes',
			/* $title    */ __('Related Episodes', 'podlove'),
			/* $callback */ [$this, 'meta_box_callback'],
			/* $page     */ 'podcast',
			/* $context  */ 'normal'
		);
	}

	public function meta_box_callback($post) {

		$post_id = $post->ID;

		$podcast = Podcast::get();
		$episode = Episode::find_or_create_by_post_id($post_id);

		$form_args = array(
			'context' => '_podlove_meta',
			'submit_button' => false,
			'form' => false,
			'is_table' => false
		);

		\Podlove\Form\build_for($episode, $form_args, function ($form) {
			$wrapper = new \Podlove\Form\Input\DivWrapper($form);

			$wrapper->callback('episode_relation_form_table', [
				'label'    => '',
				'callback' => array($this, 'episode_relation_form_callback')
			]);

		});
	}

	public function episode_relation_form_callback($form_base_name = '_podlove_meta') {
		$existing_episodes = array();
		foreach (Episode::find_all_by_time() as $episode ) {
			$existing_episodes[$episode->id] = get_the_title($episode->post_id);
		}
		$episode = Episode::find_one_by_post_id(get_the_ID());

		$existing_episode_relations = array_map( function ($episode) {
					return $episode->to_array();
				}, 
				EpisodeRelation::get_related_episodes($episode->id) 
			);
		?>
		<div id="episode-relation-form">
			<table class="podlove_alternating" border="0" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Episode', 'podlove'); ?></th>
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
		<tr class="podlove-episode-relation-table">
			<td>
				<select name="<?php echo $form_base_name ?>[related_episodes][{{id}}]" id="<?php echo $form_base_name ?>_related_episodes_{{id}}"  class="chosen-related-episodes podlove_episode_relation_episodes_dropdown">
				<?php foreach ($existing_episodes as $episode_id => $episode_title) : ?>
					<option value="<?php echo $episode_id; ?>"><?php echo $episode_title; ?></option>
				<?php endforeach; ?>
				</select>
			</td>
			<td>
				<span class="episode_relation_remove">
					<i class="clickable podlove-icon-remove"></i>
				</span>
			</td>
		</tr>
		</script>
		<script type="text/javascript">
			var PODLOVE = PODLOVE || {};
			
			PODLOVE.related_episodes_existing_episode_relations = <?php echo json_encode($existing_episode_relations); ?>;
		</script>
		<?php
	}

	public function save_post($post_id) {
		if ( !$post_id || ! isset($_POST['_podlove_meta']['related_episodes']) )
			return;

		$new_related_episodes = $_POST['_podlove_meta']['related_episodes'];
		$episode = Episode::find_one_by_post_id($post_id);

		foreach (EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id.' OR right_episode_id = '.$episode->id) as $episode_relation) {
			$episode_relation->delete();
		}

		foreach ($new_related_episodes as $episode_relation) {
			$e = new EpisodeRelation;
			$e->left_episode_id = $episode->id;
			$e->right_episode_id = $episode_relation;
			$e->save();
		}
	}
}