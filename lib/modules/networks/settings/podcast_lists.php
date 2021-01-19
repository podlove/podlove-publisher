<?php

namespace Podlove\Modules\Networks\Settings;

use Podlove\Modules\Networks\Model\Network;
use Podlove\Modules\Networks\Model\PodcastList;

class PodcastLists
{
    const MENU_SLUG = 'podlove_settings_list_handle';
    public static $pagehook;

    public function __construct($handle)
    {
        PodcastLists::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            'Lists',
            // $menu_title
            'Lists',
            // $capability
            'administrator',
            // $menu_slug
            self::MENU_SLUG,
            // $function
            [$this, 'page']
        );

        add_action('admin_init', [$this, 'process_form']);
    }

    public function process_form()
    {
        if (!isset($_REQUEST['list'])) {
            return;
        }

        $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;

        set_transient('podlove_needs_to_flush_rewrite_rules', true);

        if ($action === 'save') {
            $this->save();
        } elseif ($action === 'create') {
            $this->create();
        } elseif ($action === 'delete') {
            $this->delete();
        }
    }

    public static function get_action_link($list, $title, $action = 'edit', $class = 'link')
    {
        return sprintf(
            '<a href="?page=%s&amp;action=%s&amp;list=%s" class="%s">'.$title.'</a>',
            self::MENU_SLUG,
            $action,
            $list->id,
            $class
        );
    }

    public function page()
    {
        if (isset($_GET['action']) and $_GET['action'] == 'confirm_delete' and isset($_REQUEST['list'])) {
            PodcastList::activate_network_scope();
            $list = PodcastList::find_by_id($_REQUEST['list']);
            PodcastList::deactivate_network_scope(); ?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf(__('You selected to delete the list "%s". Please confirm this action.', 'podlove-podcasting-plugin-for-wordpress'), $list->title); ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link($list, __('Delete list permanently', 'podlove-podcasting-plugin-for-wordpress'), 'delete', 'button'); ?>
					<?php echo self::get_action_link($list, __('Don\'t change anything', 'podlove-podcasting-plugin-for-wordpress'), 'keep', 'button-primary'); ?>
				</p>
			</div>
			<?php
        } ?>
		<div class="wrap">
			<h2><?php echo __('Lists', 'podlove-podcasting-plugin-for-wordpress'); ?> <a href="?page=<?php echo self::MENU_SLUG; ?>&amp;action=new" class="add-new-h2"><?php echo __('Add New', 'podlove-podcasting-plugin-for-wordpress'); ?></a></h2>
			<?php
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'new':   $this->new_template();

break;
                        case 'edit':  $this->edit_template();

break;
                        default:      $this->view_template();

break;
                    }
                } else {
                    $this->view_template();
                } ?>
		</div>	
		<?php
    }

    /**
     * Process form: save/update a list.
     */
    private function save()
    {
        if (!isset($_REQUEST['list'])) {
            return;
        }

        $podcasts = [];
        foreach ($_POST['podlove_list']['podcasts'] as $podcast) {
            $podcasts[] = $podcast;
        }

        $_POST['podlove_list']['podcasts'] = json_encode($podcasts);

        PodcastList::activate_network_scope();
        $list = PodcastList::find_by_id($_REQUEST['list']);
        $list->update_attributes($_POST['podlove_list']);
        PodcastList::deactivate_network_scope();

        $this->redirect('index', $list->id);
    }

    /**
     * Process form: create a list.
     */
    private function create()
    {
        global $wpdb;

        $podcasts = [];
        foreach ($_POST['podlove_list']['podcasts'] as $podcast) {
            $podcasts[] = $podcast;
        }

        $_POST['podlove_list']['podcasts'] = json_encode($podcasts);

        PodcastList::activate_network_scope();
        $list = new PodcastList();
        $list->update_attributes($_POST['podlove_list']);
        PodcastList::deactivate_network_scope();

        $this->redirect('index');
    }

    /**
     * Process form: delete a list.
     */
    private function delete()
    {
        if (!isset($_REQUEST['list'])) {
            return;
        }

        PodcastList::activate_network_scope();
        PodcastList::find_by_id($_REQUEST['list'])->delete();
        PodcastList::deactivate_network_scope();

        $this->redirect('index');
    }

    /**
     * Helper method: redirect to a certain page.
     *
     * @param mixed      $action
     * @param null|mixed $list_id
     */
    private function redirect($action, $list_id = null)
    {
        $page = 'network/admin.php?page='.self::MENU_SLUG;
        $show = ($list_id) ? '&list='.$list_id : '';
        $action = '&action='.$action;

        wp_redirect(admin_url($page.$show.$action));
        exit;
    }

    private function view_template()
    {
        echo __('If you have configured a <a href="http://codex.wordpress.org/Create_A_Network">
				WordPress Network</a>, Podlove allows you to configure Podcast lists.', 'podlove-podcasting-plugin-for-wordpress');
        $table = new \Podlove\Modules\Networks\PodcastList_List_Table();
        $table->prepare_items();
        $table->display();
    }

    private function new_template()
    {
        PodcastList::activate_network_scope();
        $list = new PodcastList(); ?>
		<h3><?php echo __('Add New list', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<?php
        $this->form_template($list, 'create', __('Add New list', 'podlove-podcasting-plugin-for-wordpress'));
        PodcastList::deactivate_network_scope();
    }

    private function edit_template()
    {
        PodcastList::activate_network_scope();
        $list = PodcastList::find_by_id($_REQUEST['list']);
        echo '<h3>'.sprintf(__('Edit list: %s', 'podlove-podcasting-plugin-for-wordpress'), $list->title).'</h3>';
        $this->form_template($list, 'save');
        PodcastList::deactivate_network_scope();
    }

    private function form_template($list, $action, $button_text = null)
    {
        $form_args = [
            'context' => 'podlove_list',
            'hidden' => [
                'list' => $list->id,
                'action' => $action,
            ],
        ];

        \Podlove\Form\build_for($list, $form_args, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            $list = $form->object;

            $wrapper->string('slug', [
                'label' => __('ID', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text required'],
                'description' => sprintf(__('For referencing in templates: %s', 'podlove-podcasting-plugin-for-wordpress'), '<code>{{ network.lists({id: "example"}).title }}</code>'),
            ]);

            $wrapper->string('title', [
                'label' => __('Title', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text required'],
            ]);

            $wrapper->string('subtitle', [
                'label' => __('Subtitle', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text'],
            ]);

            $wrapper->text('description', [
                'label' => __('Summary', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['rows' => 3, 'cols' => 40],
            ]);

            $wrapper->image('logo', [
                'label' => __('Logo', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('JPEG or PNG.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text'],
                'image_width' => 300,
                'image_height' => 300,
            ]);

            $wrapper->string('url', [
                'label' => __('List URL', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text'],
            ]);

            $wrapper->callback('podcasts', [
                'label' => __('Podcasts', 'podlove-podcasting-plugin-for-wordpress'),
                'callback' => function () use ($list) {
                    $form_base_name = 'podlove_list'; ?>
					<div id="podcast_lists">
						<table class="podlove_alternating" border="0" cellspacing="0">
							<thead>
								<tr>
									<th><?php echo __('Source', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
									<th><?php echo __('Podcast/URL', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
									<th style="width: 60px"><?php echo __('Remove', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
									<th style="width: 30px"></th>
								</tr>
							</thead>
							<tbody class="podcasts_table_body" style="min-height: 50px;">
								<tr class="podcasts_table_body_placeholder" style="display: none;">
									<td><em><?php echo __('No Podcasts were added yet.', 'podlove-podcasting-plugin-for-wordpress'); ?></em></td>
								</tr>
							</tbody>
						</table>

						<div id="add_new_podcasts_wrapper">
							<input class="button" id="add_new_podcast" value="+" type="button" />
						</div>

						<script type="text/template" id="podcast-row-template">
						<tr class="media_file_row podlove-podcast-table" data-id="{{id}}">
							<td class="podlove-podcast-column">
								<select name="<?php echo $form_base_name; ?>[podcasts][{{id}}][type]" class="podlove-podcast-dropdown">
									<option value="wplist" selected><?php echo __('WordPress Network', 'podlove-podcasting-plugin-for-wordpress'); ?></option>
								</select>
							</td>
							<td class="podlove-podcast-value"></td>
							<td>
								<span class="podcast_remove">
									<i class="clickable podlove-icon-remove"></i>
								</span>
							</td>
							<td class="move column-move"><i class="reorder-handle podlove-icon-reorder"></i></td>
						</tr>
						</script>
						<script type="text/template" id="podcast-select-type-wplist">
						<select name="<?php echo $form_base_name; ?>[podcasts][{{id}}][podcast]" class="podlove-podcast chosen-image">
							<option>— <?php echo __('Select Podcast', 'podlove-podcasting-plugin-for-wordpress'); ?> —</option>
							<?php
                                foreach (Network::podcasts() as $blog_id => $podcast) {
                                    if ($podcast->title) {
                                        printf("<option value='%s' data-img-src='%s'>%s</option>\n", $blog_id, $podcast->cover_art()->setWidth(45)->url(), $podcast->title);
                                    }
                                } ?>
						</select>
						</script>
					</div>

					<script type="text/javascript">

						var PODLOVE = PODLOVE || {};

						(function($) {
							var i = 0;
							var existing_podcasts = <?php echo is_null($list->podcasts) ? '[]' : $list->podcasts; ?>;
							var podcasts = [];

							function update_chosen() {
								$(".chosen").chosen();
								$(".chosen-image").chosenImage();
							}

							function podcast_dropdown_handler() {
								$('select.podlove-podcast-dropdown').change(function() {
									row = $(this).closest("tr");
									podcast_source = $(this).val();

									// Check for empty podcast / for new field
									if (podcast_source === '') {
										row.find(".podlove-podcast-value").html(""); // Empty podcast column and hide edit button
										row.find(".podlove-podcast-edit").hide();
										return;
									}

									if (!row.find(".podlove-podcast").length) {
										template_id = "#podcast-select-type-" + podcast_source;
										template = $( template_id ).html();
										template = template.replace(/\{\{id\}\}/g, row.data('id') );

										row.find(".podlove-podcast-value").html( template );
										update_chosen();

										i++; // continue using "i" which was already used to add the existing contributions
									}

								});
							}

							$(document).ready(function() {
								$("#podcast_lists table").podloveDataTable({
									rowTemplate: "#podcast-row-template",
									deleteHandle: ".podcast_remove",
									sortableHandle: ".reorder-handle",
									addRowHandle: "#add_new_podcast",
									data: existing_podcasts,
									dataPresets: podcasts,
									onRowLoad: function(o) {
										template_id = "#podcast-select-type-" + o.entry.type;
										template = $( template_id ).html();
										row_as_object = $(o.row)
										
										row_as_object.find(".podlove-podcast-value").html( template );
										row_as_object.find('select.podlove-podcast-dropdown option[value="' + o.entry.type + '"]').attr('selected', 'selected');

										switch ( o.entry.type ) {
											default: case 'wplist':
												row_as_object.find('select.podlove-podcast option[value="' + o.entry.podcast + '"]').attr('selected', true);
											break;
										}

										o.row = row_as_object[0].outerHTML.replace(/\{\{id\}\}/g, i);

										i++;
									},
									onRowAdd: function(o) {
										o.row = o.row.replace(/\{\{id\}\}/g, i);

										
										row = $(".podcasts_table_body tr:last .podlove-podcast-dropdown").focus();

										podcast_dropdown_handler();
										update_chosen();
										row.change();
									},
									onRowDelete: function(tr) {
										
									}
								});
							});

						}(jQuery));

					</script>
					<?php
                },
            ]);
        });
    }
}
