<?php

namespace Podlove\Settings;

use Podlove\Model;

class EpisodeAsset
{
    use \Podlove\HasPageDocumentationTrait;
    const MENU_SLUG = 'podlove_episode_assets_settings_handle';

    public static $pagehook;

    public function __construct($handle)
    {
        self::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Episode Assets', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Episode Assets', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            self::MENU_SLUG,
            // $function
            [$this, 'page']
        );

        $this->init_page_documentation(self::$pagehook);

        add_action('admin_init', [$this, 'process_form']);

        register_setting(EpisodeAsset::$pagehook, 'podlove_asset_assignment');
    }

    public function batch_enable()
    {
        if (!isset($_REQUEST['episode_asset'])) {
            return;
        }

        $podcast = Model\Podcast::get();
        $asset = Model\EpisodeAsset::find_by_id($_REQUEST['episode_asset']);

        $episodes = Model\Episode::all();
        foreach ($episodes as $episode) {
            $post_id = $episode->post_id;
            $post = get_post($post_id);

            // skip deleted podcasts
            if (!in_array($post->post_status, ['pending', 'draft', 'publish', 'future'])) {
                continue;
            }

            // skip versions
            if ($post->post_type != 'podcast') {
                continue;
            }

            $file = Model\MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset->id);

            if ($file === null) {
                $file = new Model\MediaFile();
                $file->episode_id = $episode->id;
                $file->episode_asset_id = $asset->id;
                $file->save();
            }

            do_action('podlove_media_file_content_has_changed', $file->id);
        }

        $this->redirect('index', null, ['message' => 'media_file_batch_enabled_notice']);
    }

    public function process_form()
    {
        if (!isset($_REQUEST['episode_asset'])) {
            return;
        }

        $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;

        if ($action === 'save') {
            $this->save();
        } elseif ($action === 'create') {
            $this->create();
        } elseif ($action === 'delete') {
            $this->delete();
        } elseif ($action === 'batch_enable') {
            $this->batch_enable();
        }
    }

    public function page()
    {
        if (isset($_REQUEST['message'])) {
            if ($_REQUEST['message'] == 'media_file_batch_enabled_notice') {
                ?>
				<div class="updated">
					<p><?php _e('<strong>Media Files enabled.</strong> These Media Files have been enabled for all existing episodes.', 'podlove-podcasting-plugin-for-wordpress'); ?></p>
				</div>
				<?php
            }
            if ($_REQUEST['message'] == 'media_file_relation_warning') {
                $asset = Model\EpisodeAsset::find_one_by_id((int) $_REQUEST['deleted_id']); ?>
				<div class="error">
					<p>
						<strong><?php _e('The asset has not been deleted. Are you aware that the asset is still in use?', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
						<ul class="ul-disc">
							<?php if ($asset->has_active_media_files()) { ?>
								<li>
									<?php echo sprintf(__('There are %s connected media files.', 'podlove-podcasting-plugin-for-wordpress'), count($asset->active_media_files())); ?>
								</li>
							<?php } ?>
							<?php if ($asset->has_asset_assignments()) { ?>
								<li>
									<?php _e('This asset is assigned to episode images or episode chapters.', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</li>
							<?php } ?>
							<?php if ($asset->is_connected_to_feed()) { ?>
								<li>
									<?php _e('A feed uses this asset.', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</li>
							<?php } ?>
							<?php if ($asset->is_connected_to_web_player()) { ?>
								<li>
									<?php _e('The web player uses this asset.', 'podlove-podcasting-plugin-for-wordpress'); ?>
								</li>
							<?php } ?>
						</ul>
						<a href="?page=<?php echo self::MENU_SLUG; ?>&amp;action=delete&amp;episode_asset=<?php echo $asset->id; ?>&amp;force=1">
							<?php _e('delete anyway', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</a>
					</p>
				</div>
				<?php
            }
        } ?>
		<div class="wrap">
			<h2><?php _e('Episode Assets', 'podlove-podcasting-plugin-for-wordpress'); ?> <a href="?page=<?php echo self::MENU_SLUG; ?>&amp;action=new" class="add-new-h2"><?php _e('Add New', 'podlove-podcasting-plugin-for-wordpress'); ?></a></h2>
			<?php
            $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        switch ($action) {
                case 'new':   $this->new_template();

break;
                case 'edit':  $this->edit_template();

break;
                case 'index': $this->view_template();

break;

                default:      $this->view_template();

break;
            } ?>
		</div>
		<?php
    }

    /**
     * Process form: save/update a format.
     */
    private function save()
    {
        if (!isset($_REQUEST['episode_asset'])) {
            return;
        }

        $episode_asset = \Podlove\Model\EpisodeAsset::find_by_id($_REQUEST['episode_asset']);
        $episode_asset->update_attributes($_POST['podlove_episode_asset']);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $episode_asset->id);
        } else {
            $this->redirect('index', $episode_asset->id);
        }
    }

    /**
     * Process form: create a format.
     */
    private function create()
    {
        global $wpdb;

        $episode_asset = new \Podlove\Model\EpisodeAsset();
        $episode_asset->update_attributes($_POST['podlove_episode_asset']);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $episode_asset->id);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Process form: delete a format.
     */
    private function delete()
    {
        if (!isset($_REQUEST['episode_asset'])) {
            return;
        }

        $podcast = Model\Podcast::get();
        $asset = Model\EpisodeAsset::find_by_id($_REQUEST['episode_asset']);

        if (isset($_REQUEST['force']) && $_REQUEST['force'] || $asset->is_deletable()) {
            $asset->delete();
            $this->redirect('index');
        } else {
            $this->redirect('index', null, ['message' => 'media_file_relation_warning', 'deleted_id' => $asset->id]);
        }
    }

    /**
     * Helper method: redirect to a certain page.
     *
     * @param mixed      $action
     * @param null|mixed $episode_asset_id
     * @param mixed      $params
     */
    private function redirect($action, $episode_asset_id = null, $params = [])
    {
        $page = 'admin.php?page='.self::MENU_SLUG;
        $show = ($episode_asset_id) ? '&episode_asset='.$episode_asset_id : '';
        $action = '&action='.$action;

        array_walk($params, function (&$value, $key) {
            $value = "&{$key}={$value}";
        });

        wp_redirect(admin_url($page.$show.$action.implode('', $params)));
        exit;
    }

    private function new_template()
    {
        $episode_asset = new \Podlove\Model\EpisodeAsset(); ?>
		<h3><?php _e('Add New Episode Asset', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<?php
        $this->form_template($episode_asset, 'create', __('Add New Episode Asset', 'podlove-podcasting-plugin-for-wordpress'));
    }

    private function view_template()
    {
        $table = new \Podlove\Episode_Asset_List_Table();
        $table->prepare_items();
        $table->display(); ?>
		<h3><?php _e('Assign Assets', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<form method="post" action="options.php">
			<?php settings_fields(EpisodeAsset::$pagehook);
        $asset_assignment = Model\AssetAssignment::get_instance();

        $form_attributes = [
            'context' => 'podlove_asset_assignment',
            'form' => false,
        ];

        \Podlove\Form\build_for($asset_assignment, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);
            $asset_assignment = $form->object;
            $artwork_options = [
                '0' => __('Use Podcast Cover', 'podlove-podcasting-plugin-for-wordpress'),
                'post-thumbnail' => __('Post Thumbnail', 'podlove-podcasting-plugin-for-wordpress'),
                'manual' => __('Manual URL Entry per Episode', 'podlove-podcasting-plugin-for-wordpress'),
            ];
            $episode_assets = Model\EpisodeAsset::all();
            foreach ($episode_assets as $episode_asset) {
                $file_type = $episode_asset->file_type();
                if ($file_type && $file_type->type === 'image') {
                    $artwork_options[$episode_asset->id] = sprintf(__('Asset: %s', 'podlove-podcasting-plugin-for-wordpress'), $episode_asset->title);
                }
            }

            $wrapper->select('image', [
                'label' => __('Episode Image', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $artwork_options,
            ]);

            $chapter_file_options = [
                '0' => __('None', 'podlove-podcasting-plugin-for-wordpress'),
                'manual' => __('Manual Entry', 'podlove-podcasting-plugin-for-wordpress'),
            ];
            $episode_assets = Model\EpisodeAsset::all();
            foreach ($episode_assets as $episode_asset) {
                $file_type = $episode_asset->file_type();
                if ($file_type && $file_type->type === 'chapters') {
                    $chapter_file_options[$episode_asset->id] = sprintf(__('Asset: %s', 'podlove-podcasting-plugin-for-wordpress'), $episode_asset->title);
                }
            }
            $wrapper->select('chapters', [
                'label' => __('Episode Chapters', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $chapter_file_options,
            ]);

            do_action('podlove_asset_assignment_form', $wrapper, $asset_assignment);
        }); ?>
		</form>
		<?php
    }

    private function form_template($episode_asset, $action, $button_text = null)
    {
        $raw_formats = \Podlove\Model\FileType::all();
        $formats = [];
        foreach ($raw_formats as $format) {
            $formats[$format->id] = [
                'title' => $format->title(),
                'name' => $format->name,
                'extension' => $format->extension,
                'type' => $format->type,
            ];
        }

        $format_optionlist = array_map(function ($f) {
            return [
                'value' => $f['title'],
                'attributes' => 'data-type="'.$f['type'].'" data-extension="'.$f['extension'].'" data-name="'.$f['name'].'"',
            ];
        }, $formats);

        $form_args = [
            'context' => 'podlove_episode_asset',
            'hidden' => [
                'episode_asset' => $episode_asset->id,
                'action' => $action,
            ],
            'attributes' => [
                'id' => 'podlove_episode_assets',
            ],
            'submit_button' => false, // for custom control in form_end
            'form_end' => function () {
                echo '<p>';
                submit_button(__('Save Changes'), 'primary', 'submit', false);
                echo ' ';
                submit_button(__('Save Changes and Continue Editing', 'podlove-podcasting-plugin-for-wordpress'), 'secondary', 'submit_and_stay', false);
                echo '</p>';
            },
        ];

        \Podlove\Form\build_for($episode_asset, $form_args, function ($form) use ($format_optionlist) {
            $f = new \Podlove\Form\Input\TableWrapper($form);
            if ($form->object->file_type_id) {
                $current_file_type = Model\FileType::find_by_id($form->object->file_type_id)->type;
            } else {
                $current_file_type = '';
            } ?>
			<tr class="row_podlove_episode_asset_type">
				<th scope="row" valign="top">
					<label for="podlove_episode_asset_type"><?php _e('Asset Type', 'podlove-podcasting-plugin-for-wordpress'); ?></label>
				</th>
				<td>
					<select name="podlove_episode_asset_type" id="podlove_episode_asset_type">
						<option><?php _e('Please choose ...', 'podlove-podcasting-plugin-for-wordpress'); ?></option>
						<?php foreach (Model\FileType::get_types() as $type) { ?>
							<option value="<?php echo $type; ?>" <?php selected($type, $current_file_type); ?>><?php echo $type; ?></option>
						<?php } ?>
					</select>
					<div id="option_storage" style="display:none"></div>
				</td>
			</tr>
			<?php

            $f->select('file_type_id', [
                'label' => __('File Format', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => '',
                'options' => $format_optionlist,
            ]);

            $f->string('title', [
                'label' => __('Title', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Description to identify the media file type to the user in download buttons.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text required podlove-check-input'],
            ]);

            $f->string('identifier', [
                'label' => __('Template Identifier', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => sprintf(
                    __('Used in templates to access the file for this asset from an episode: %s', 'podlove-podcasting-plugin-for-wordpress'),
                    '<code>episode.file("template-identifier")</code>'
                ),
                'html' => ['class' => 'regular-text podlove-check-input'],
            ]);

            $f->checkbox('downloadable', [
                'label' => __('Downloadable', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Include in download interfaces.', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => true,
            ]); ?>
			<tr>
				<th colspan="2">
					<h3><?php _e('Asset File Name', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
				</th>
			</tr>
			<?php
            $f->string('suffix', [
                'label' => __('File Name Suffix', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Optional. Is appended to file name after episode slug.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text required podlove-check-input'],
            ]); ?>
			<tr class="row_podlove_asset_url_preview">
				<th>
					<?php _e('URL Preview', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</th>
				<td>
					<div id="url_preview" style="font-size: 1.5em"></div>
					<div id="url_template" style="display: none;"><?php echo Model\Podcast::get()->get_url_template(); ?></div>
				</td>
			</tr>
			<?php
        });

        // hidden fields for JavaScript?>
		<input type="hidden" id="podlove_show_media_file_base_uri" value="<?php echo Model\Podcast::get()->get_media_file_base_uri(); ?>">
		<?php
    }

    private function edit_template()
    {
        $episode_asset = \Podlove\Model\EpisodeAsset::find_by_id($_REQUEST['episode_asset']);
        echo '<h3>'.sprintf(__('Edit Episode Asset: %s', 'podlove-podcasting-plugin-for-wordpress'), $episode_asset->title).'</h3>';
        $this->form_template($episode_asset, 'save');
    }
}
