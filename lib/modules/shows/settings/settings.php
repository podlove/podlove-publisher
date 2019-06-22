<?php
namespace Podlove\Modules\Shows\Settings;

use \Podlove\Modules\Shows\Model\Show;

class Settings
{
    use \Podlove\HasPageDocumentationTrait;

    const MENU_SLUG = 'podlove_shows_settings';

    public function __construct($handle)
    {
        $pagehook = add_submenu_page(
            /* $parent_slug*/$handle,
            /* $page_title */__('Shows', 'podlove-podcasting-plugin-for-wordpress'),
            /* $menu_title */__('Shows', 'podlove-podcasting-plugin-for-wordpress'),
            /* $capability */'administrator',
            /* $menu_slug  */self::MENU_SLUG,
            /* $function   */[$this, 'page']
        );

        $this->init_page_documentation($pagehook);

        add_action('admin_init', array($this, 'process_form'));
        add_action("load-" . $pagehook, [$this, 'add_screen_options']);
    }

    public static function show_meta_data_fields()
    {
        return ['subtitle', 'language', 'image'];
    }

    public function add_screen_options()
    {
        add_screen_option('per_page', array(
            'label'   => __('Shows', 'podlove-podcasting-plugin-for-wordpress'),
            'default' => 10,
            'option'  => 'podlove_shows_per_page',
        ));

        $this->table = new ShowListTable;
    }

    public function process_form()
    {
        if (!isset($_REQUEST['show'])) {
            return;
        }

        $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;

        if ($action === 'save') {
            $this->save();
        } elseif ($action === 'create') {
            $this->create();
        } elseif ($action === 'delete') {
            $this->delete();
        }
    }

    /**
     * Helper method: redirect to a certain page.
     */
    private function redirect($action, $episode_asset_id = null, $params = [])
    {
        $page   = 'admin.php?page=' . self::MENU_SLUG;
        $show   = ($episode_asset_id) ? '&show=' . $episode_asset_id : '';
        $action = '&action=' . $action;

        array_walk($params, function (&$value, $key) {$value = "&$key=$value";});

        wp_redirect(admin_url($page . $show . $action . implode('', $params)));
        exit;
    }

    /**
     * Process form: save/update a show
     */
    private function save()
    {
        if (!isset($_REQUEST['show'])) {
            return;
        }

        $updated_term = wp_update_term(
            $_REQUEST['show'],
            'shows',
            array(
                'name'        => $_POST['podlove_show']['title'],
                'description' => $_POST['podlove_show']['summary'],
                'slug'        => $_POST['podlove_show']['slug'],
            )
        );

        // Add meta entries
        if (is_wp_error($updated_term)) {
            return;
        }

        foreach (self::show_meta_data_fields() as $meta_data) {
            update_term_meta($_REQUEST['show'], $meta_data, $_POST['podlove_show'][$meta_data]);
        }

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $_REQUEST['show']);
        } else {
            $this->redirect('index', $_REQUEST['show']);
        }
    }

    /**
     * Process form: create a show
     */
    private function create()
    {
        global $wpdb;

        if (!$_POST['podlove_show']) {
            return;
        }

        $show = new Show;

        // Create new term
        $new_term = wp_insert_term(
            $_POST['podlove_show']['title'],
            'shows',
            array(
                'description' => $_POST['podlove_show']['summary'],
                'slug'        => $_POST['podlove_show']['slug'],
            )
        );

        // Add meta entries
        if (is_wp_error($new_term)) {
            return;
        }

        foreach (self::show_meta_data_fields() as $meta_data) {
            add_term_meta($new_term['term_id'], $meta_data, $_POST['podlove_show'][$meta_data]);
        }

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $new_term['term_id']);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Process form: delete a format
     */
    private function delete()
    {
        if (!isset($_REQUEST['show'])) {
            return;
        }

        foreach (self::show_meta_data_fields() as $meta_data) {
            delete_term_meta($_REQUEST['show'], $meta_data);
        }

        wp_delete_term($_REQUEST['show'], 'shows');

        $this->redirect('index');
    }

    public function page()
    {
        ?>
		<div class="wrap">
			<h2><?php echo __('Shows', 'podlove'); ?><a href="#" data-podlove-help="podlove_help_shows"><sup>?</sup></a> <a href="?page=<?php echo self::MENU_SLUG; ?>&amp;action=new" class="add-new-h2"><?php echo __('Add New', 'podlove-podcasting-plugin-for-wordpress'); ?></a></h2>

			<?php
if (isset($_GET["action"]) && $_GET["action"] == 'confirm_delete'):
            $show = Show::find_by_id($_REQUEST['show']);
            ?>
					<div class="updated">
						<p>
							<strong>
								<?php echo sprintf(__('You selected to delete the show "%s". Please confirm this action.', 'podlove-podcasting-plugin-for-wordpress'), $show->title); ?>
							</strong>
						</p>
						<p>
							<?php echo self::get_action_link($show, __('Delete permanently', 'podlove-podcasting-plugin-for-wordpress', $show->id), 'delete', 'button') ?>
							<?php echo self::get_action_link($show, __('Don\'t change anything', 'podlove-podcasting-plugin-for-wordpress', $show->id), 'keep', 'button-primary') ?>
						</p>
					</div>
					<?php endif;

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        switch ($action) {
            case 'new':$this->new_template();
                break;
            case 'edit':$this->edit_template();
                break;
            case 'index':$this->view_template();
                break;
            default:$this->view_template();
                break;
        }
        ?>
		</div>
		<?php
}

    private function new_template()
    {
        $show = new Show;
        ?>
		<h3><?php echo __('Add New Show', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<?php
$this->form_template($show, 'create', __('Add New Show', 'podlove-podcasting-plugin-for-wordpress'));
    }

    private function view_template()
    {
        ?>
		<style type="text/css">
		.wp-list-table.shows .column-image    { width: 150px; }
		.wp-list-table.shows .column-title    { width: 250px; }
		.wp-list-table.shows .column-episodes { width: 90px; }
		</style>
		<?php
$this->table->prepare_items();
        $this->table->display();
    }

    private function form_template($show, $action, $button_text = null)
    {
        $form_args = array(
            'context'       => 'podlove_show',
            'hidden'        => array(
                'show'   => $show->id,
                'action' => $action,
            ),
            'submit_button' => false, // for custom control in form_end
            'form_end'      => function () {
                echo "<p>";
                submit_button(__('Save Changes'), 'primary', 'submit', false);
                echo " ";
                submit_button(__('Save Changes and Continue Editing', 'podlove-podcasting-plugin-for-wordpress'), 'secondary', 'submit_and_stay', false);
                echo "</p>";
            },
        );

        \Podlove\Form\build_for($show, $form_args, function ($form) {
            $wrapper      = new \Podlove\Form\Input\TableWrapper($form);
            $show         = $form->object;
            $generic_feed = \Podlove\Model\Feed::first();

            $podcast = \Podlove\Model\Podcast::get();

            $wrapper->string('title', [
                'label'       => __('Title', 'podlove-podcasting-plugin-for-wordpress'),
                'html'        => ['class' => 'regular-text podlove-check-input'],
                'description' => sprintf(
                    __('Title of your show as it appears in the feed. It is probably a good idea to include the name of your main podcast. For example, instead of "Outtakes", name the show "%s | Outtakes".', 'podlove-podcasting-plugin-for-wordpress'),
                    $podcast->title
                ),
            ]);

            $wrapper->string('slug', [
                'label'       => __('Slug', 'podlove-podcasting-plugin-for-wordpress') . \Podlove\get_help_link('podlove_help_shows_slug'),
                'html'        => ['class' => 'regular-text required podlove-check-input'],
                'description' => 'Feed identifier. <span id="feed_subscribe_url_preview" data-show-feed-base-url="' . get_site_url() . '" data-show-feed-slug="' . (isset($generic_feed) ? $generic_feed->slug : '') . '" data-show-preview-string="' . __('URL preview:', 'podlove-podcasting-plugin-for-wordpress') . '"></span>',
            ]);

            $wrapper->string('subtitle', [
                'label'       => __('Subtitle', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Subtitle of your show as it appears in the feed. Leave blank to default to your podcast\'s subtitle.', 'podlove-podcasting-plugin-for-wordpress'),
                'html'        => [
                    'class'       => 'regular-text podlove-check-input',
                    'placeholder' => $podcast->subtitle,
                ],
            ]);

            $wrapper->text('summary', [
                'label'       => __('Summary', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Summary of your show as it appears in the feed. Leave blank to default to your podcast\'s summary.', 'podlove-podcasting-plugin-for-wordpress'),
                'html'        => [
                    'rows'        => 3,
                    'cols'        => 40,
                    'placeholder' => $podcast->summary,
                    'class'       => 'podlove-check-input',
                ],
            ]);

            $wrapper->upload('image', [
                'label'             => __('Image', 'podlove-podcasting-plugin-for-wordpress'),
                'html'              => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
                'media_button_text' => __("Use Image for Show", 'podlove-podcasting-plugin-for-wordpress'),
            ]);

            $wrapper->select('language', array(
                'label'       => __('Language', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => '',
                'default'     => get_bloginfo('language'),
                'options'     => \Podlove\Locale\locales(),
            ));

        });
    }

    private function edit_template()
    {
        $show = Show::find_by_id($_REQUEST['show']);
        echo '<h3>' . sprintf(__('Edit Show: %s', 'podlove-podcasting-plugin-for-wordpress'), $show->title) . '</h3>';
        $this->form_template($show, 'save');
    }

    private static function get_action_link($show, $title, $action = 'edit', $class = 'link')
    {
        return sprintf(
            '<a href="?page=%s&amp;action=%s&amp;show=%s" class="%s">' . $title . '</a>',
            self::MENU_SLUG,
            $action,
            $show->id,
            $class
        );
    }
}
