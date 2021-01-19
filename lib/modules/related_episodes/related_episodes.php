<?php

namespace Podlove\Modules\RelatedEpisodes;

use Podlove\Model;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;

class Related_Episodes extends \Podlove\Modules\Base
{
    protected $module_name = 'Related Episodes';
    protected $module_description = 'Create related pairs of episodes. Display with shortcode <code>[podlove-related-episodes]</code>';
    protected $module_group = 'metadata';

    public function load()
    {
        add_action('podlove_module_was_activated_related_episodes', [$this, 'was_activated']);
        add_filter('podlove_episode_form_data', [$this, 'episode_relation_form'], 10, 2);
        add_action('save_post', [$this, 'update_episode_relations'], 10, 2);

        add_action('admin_print_styles', [$this, 'admin_print_styles']);

        \Podlove\Template\Episode::add_accessor(
            'relatedEpisodes',
            ['\Podlove\Modules\RelatedEpisodes\TemplateExtensions', 'accessorRelatedEpisodes'],
            5
        );

        add_filter('podlove_twig_file_loader', function ($file_loader) {
            $file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'lib', 'modules', 'related_episodes', 'templates']), 'related-episodes');

            return $file_loader;
        });

        Shortcodes::init();
    }

    public function was_activated($module_name)
    {
        EpisodeRelation::build();
    }

    public function update_episode_relations($post_id)
    {
        // skip if the save does not come from the episode form
        if (!isset($_POST['_podlove_meta'])) {
            return;
        }

        $episode = Model\Episode::find_one_by_post_id($post_id);

        if (!$episode) {
            return;
        }

        $relations = EpisodeRelation::find_all_by_where('left_episode_id = '.$episode->id.' OR right_episode_id = '.$episode->id);
        foreach ($relations as $episode_relation) {
            $episode_relation->delete();
        }

        if (!isset($_POST['_podlove_meta']['related_episodes'])) {
            return;
        }

        foreach ($_POST['_podlove_meta']['related_episodes'] as $episode_relation) {
            $e = new EpisodeRelation();
            $e->left_episode_id = $episode->id;
            $e->right_episode_id = $episode_relation;
            $e->save();
        }
    }

    public function episode_relation_form($form_data)
    {
        $form_data[] = [
            'type' => 'callback',
            'key' => 'episode_relation_form_table',
            'options' => [
                'label' => __('Related Episodes', 'podlove-podcasting-plugin-for-wordpress'),
                'callback' => [$this, 'episode_relation_form_callback'],
            ],
            'position' => 870,
        ];

        return $form_data;
    }

    public function episode_relation_form_callback($form_base_name = '_podlove_meta')
    {
        $existing_episodes = [];
        foreach (Model\Episode::find_all_by_time() as $episode) {
            $existing_episodes[$episode->id] = get_the_title($episode->post_id);
        }
        $episode = Model\Episode::find_one_by_post_id(get_the_ID());

        $existing_episode_relations = array_map(
            function ($episode) {
                return $episode->to_array();
            },
            EpisodeRelation::get_related_episodes($episode->id)
        ); ?>
			<div id="episode-relation-form">
				<table class="podlove_alternating" border="0" cellspacing="0">
					<thead>
						<tr>
							<th><?php _e('Episode', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
							<th><?php _e('Remove', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
						</tr>
					</thead>
					<tbody id="episode_relation_table_body" style="min-height: 50px;">
						<tr class="episode_relation_table_body_placeholder" style="display: none;">
							<td><em><?php echo __('No episode relations were added yet.', 'podlove-podcasting-plugin-for-wordpress'); ?></em></td>
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
					<select name="<?php echo $form_base_name; ?>[related_episodes][{{id}}]" id="<?php echo $form_base_name; ?>_related_episodes_{{id}}"  class="chosen-related-episodes podlove_episode_relation_episodes_dropdown">
					<?php foreach ($existing_episodes as $episode_id => $episode_title) { ?>
						<option value="<?php echo $episode_id; ?>"><?php echo $episode_title; ?></option>
					<?php } ?>
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

    public function admin_print_styles()
    {
        if (!\Podlove\is_episode_edit_screen()) {
            return;
        }

        wp_register_script(
            'podlove_related_episodes',
            $this->get_module_url().'/js/admin.js',
            ['jquery', 'podlove_admin'],
            \Podlove\get_plugin_header('Version')
        );
        wp_enqueue_script('podlove_related_episodes');
    }
}
