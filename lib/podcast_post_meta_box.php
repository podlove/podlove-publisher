<?php

namespace Podlove;

/**
 * Meta Box for Podcase Settings in Post Edit Screen.
 */
class Podcast_Post_Meta_Box
{
    private static $nonce = 'update_episode_meta';

    public function __construct()
    {
        add_action('save_post', [$this, 'save_postdata'], 10, 2);
        add_action('save_post_podcast', function ($post_id, $post, $_) {
            if ($episode = Model\Episode::find_one_by_where('post_id = '.intval($post_id))) {
                do_action('podlove_episode_content_has_changed', $episode->id);
            }
        }, 10, 3);
    }

    public static function add_meta_box()
    {
        add_meta_box(
            // $id
            'podlove_podcast',
            // $title
            __('Podcast Episode', 'podlove-podcasting-plugin-for-wordpress'),
            // $callback
            '\Podlove\Podcast_Post_Meta_Box::post_type_meta_box_callback',
            // $page
            'podcast',
            // $context
            'normal',
            // $priority
            'high'
        );
    }

    /**
     * Meta Box Template.
     *
     * @param mixed $post
     */
    public static function post_type_meta_box_callback($post)
    {
        $post_id = $post->ID;

        $podcast = Model\Podcast::get();
        $episode = Model\Episode::find_or_create_by_post_id($post_id);

        ?>

		<?php do_action('podlove_episode_meta_box_start'); ?>

		<input type="hidden" name="show-media-file-base-uri" value="<?php echo $podcast->get_media_file_base_uri(); ?>" />
		<div class="podlove-div-wrapper-form">
			<?php
            $form_args = [
                'context' => '_podlove_meta',
                'submit_button' => false,
                'form' => false,
                'is_table' => false,
                'nonce' => self::$nonce
            ];

        $form_data = self::get_form_data($episode);

        \Podlove\Form\build_for($episode, $form_args, function ($form) use ($form_data) {
            $wrapper = new \Podlove\Form\Input\DivWrapper($form);

            foreach ($form_data as $entry) {
                $wrapper->{$entry['type']}($entry['key'], $entry['options']);
            }
        }); ?>
		</div>

		<?php do_action('podlove_episode_meta_box_end'); ?>

		<?php
    }

    public static function compare_by_position($a, $b)
    {
        $pos_a = isset($a['position']) ? (int) $a['position'] : 0;
        $pos_b = isset($b['position']) ? (int) $b['position'] : 0;

        if ($a == $b || $pos_a == $pos_b) {
            return 0;
        }

        return ($pos_a < $pos_b) ? 1 : -1;
    }

    /**
     * Save post data on WordPress callback.
     *
     * @param int $post_id
     */
    public function save_postdata($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if ('podcast' !== $post->post_type || !current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['_podlove_meta']) || !is_array($_POST['_podlove_meta'])) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'], self::$nonce)) {
            return;
        }

        do_action('podlove_save_episode', $post_id, $_POST['_podlove_meta']);

        // sanitize data
        $episode_data = filter_input_array(INPUT_POST, [
            '_podlove_meta' => ['flags' => FILTER_REQUIRE_ARRAY],
        ]);
        $episode_data = $episode_data['_podlove_meta'];

        $episode_data_filter = [
            'slug' => FILTER_SANITIZE_STRING,
            'duration' => FILTER_SANITIZE_STRING,
            'episode_assets' => ['flags' => FILTER_REQUIRE_ARRAY, 'filter' => FILTER_SANITIZE_STRING],
            'guid' => FILTER_SANITIZE_STRING,
        ];
        $episode_data_filter = apply_filters('podlove_episode_data_filter', $episode_data_filter);

        $episode_data = filter_var_array($episode_data, $episode_data_filter);

        $episode_data = apply_filters('podlove_episode_data_before_save', $episode_data);

        // save changes
        $episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id);
        $episode_slug_has_changed = isset($episode_data['slug']) && $episode_data['slug'] != $episode->slug;
        $episode->update_attributes($episode_data);

        if ($episode_slug_has_changed) {
            $episode->refetch_files();
        }

        if (isset($episode_data['episode_assets'])) {
            $this->save_episode_assets($episode, $episode_data['episode_assets']);
        } else {
            $this->save_episode_assets($episode, []);
        }
    }

    /**
     * Save episode assets based on checkbox data.
     *
     * @param \Podlove\Model\Episode $episode
     * @param array                  $checkbox_data Raw form data for checkboxes.
     *                                              Contains 'on' for checked boxes and no entry at all for unchecked ones.
     */
    public function save_episode_assets($episode, $checkbox_data)
    {
        // create array where the keys are asset_ids and values false
        $assets = array_map(
            function ($_) {
                return false;
            },
            array_flip(
                array_map(
                    function ($l) {
                        return $l->id;
                    },
                    Model\EpisodeAsset::all()
                )
            )
        );

        // set those assets to true where the checkbox is set
        foreach ($assets as $id => $_) {
            if (isset($checkbox_data[$id]) && $checkbox_data[$id] === 'on') {
                $assets[$id] = true;
            }
        }

        // create new ones, delete unchecked ones
        foreach ($assets as $episode_asset_id => $episode_asset_value) {
            $file = Model\MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $episode_asset_id);

            if ($file === null && $episode_asset_value) {
                $file = new Model\MediaFile();
                $file->episode_id = $episode->id;
                $file->episode_asset_id = $episode_asset_id;
                $file->save();
            } elseif ($file !== null && !$episode_asset_value) {
                $file->delete();
            }
        }
    }

    private static function get_form_data($episode)
    {
        $form_data = [
          [
              'type' => 'callback',
              'key' => 'episode_assets',
              'options' => [
                  'callback' => function () {
                  ?>
                    <div data-client="podlove" style="margin: 15px 0;">
                      <podlove-media-files></podlove-media-files>
                    </div>
                  <?php
                  }
              ],
              'position' => 300,
          ], [
              'type' => 'callback',
              'key' => 'descriptions',
              'options' => [
                  'callback' => function () {
                  ?>
                    <div data-client="podlove" style="margin: 15px 0;">
                      <podlove-description></podlove-description>
                    </div>
                  <?php
                  }
              ],
              'position' => 900,
          ]
        ];

        // allow modules to add / change the form
        $form_data = apply_filters('podlove_episode_form_data', $form_data, $episode);

        // sort entities by position
        // TODO first sanitize position attribute, then I don't have to check on each comparison
        usort($form_data, [__CLASS__, 'compare_by_position']);

        return $form_data;
    }
}
