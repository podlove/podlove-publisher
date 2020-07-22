<?php

namespace Podlove\Modules\Seasons\Settings;

use Podlove\Model\Podcast;
use Podlove\Modules\Seasons\Model\Season;
use Podlove\Modules\Seasons\Model\SeasonsValidator;

class Settings
{
    use \Podlove\HasPageDocumentationTrait;

    const MENU_SLUG = 'podlove_seasons_settings';

    public function __construct($handle)
    {
        $pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Seasons', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Seasons', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            self::MENU_SLUG,
            // $function
            [$this, 'page']
        );

        $this->init_page_documentation($pagehook);

        add_action('admin_init', [$this, 'process_form']);
        add_action('load-'.$pagehook, [$this, 'add_screen_options']);
    }

    public function add_screen_options()
    {
        add_screen_option('per_page', [
            'label' => 'Seasons',
            'default' => 10,
            'option' => 'podlove_seasons_per_page',
        ]);

        $this->table = new SeasonListTable();
    }

    public function process_form()
    {
        if (!isset($_REQUEST['season'])) {
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

    public function page()
    {
        ?>
		<div class="wrap">
			<h2><?php echo __('Seasons', 'podlove-podcasting-plugin-for-wordpress'); ?> <a href="?page=<?php echo self::MENU_SLUG; ?>&amp;action=new" class="add-new-h2"><?php echo __('Add New', 'podlove-podcasting-plugin-for-wordpress'); ?></a></h2>
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
        if (!isset($_REQUEST['season'])) {
            return;
        }

        $season = Season::find_by_id($_REQUEST['season']);
        $season->update_attributes($_POST['podlove_season']);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $season->id);
        } else {
            $this->redirect('index', $season->id);
        }
    }

    /**
     * Process form: create a format.
     */
    private function create()
    {
        global $wpdb;

        $season = new Season();
        $season->update_attributes($_POST['podlove_season']);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $season->id);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Process form: delete a format.
     */
    private function delete()
    {
        if (!isset($_REQUEST['season'])) {
            return;
        }

        if ($season = Season::find_by_id($_REQUEST['season'])) {
            $season->delete();
        }

        $this->redirect('index');
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
        $show = ($episode_asset_id) ? '&season='.$episode_asset_id : '';
        $action = '&action='.$action;

        array_walk($params, function (&$value, $key) {
            $value = "&{$key}={$value}";
        });

        wp_redirect(admin_url($page.$show.$action.implode('', $params)));
        exit;
    }

    private function new_template()
    {
        $season = new Season(); ?>
		<h3><?php echo __('Add New Season', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<?php
        $this->form_template($season, 'create', __('Add New Season', 'podlove-podcasting-plugin-for-wordpress'));
    }

    private function view_template()
    {
        $validator = new SeasonsValidator();
        $validator->validate();
        $issues = $validator->issues();
        foreach ($validator->issues() as $issue) {
            ?>
			<div class="error">
				<p>
					<strong><?php echo __('Warning', 'podlove-podcasting-plugin-for-wordpress').': '; ?></strong>
					<?php echo $issue->message(); ?>
				</p>
			</div>
			<?php
        }

        $this->table->prepare_items();
        $this->table->display();
    }

    private function form_template($season, $action, $button_text = null)
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');

        $form_args = [
            'context' => 'podlove_season',
            'hidden' => [
                'season' => $season->id,
                'action' => $action,
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

        \Podlove\Form\build_for($season, $form_args, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);
            $season = $form->object;
            $podcast = Podcast::get();

            $wrapper->string('title', [
                'label' => __('Title', 'podlove-podcasting-plugin-for-wordpress').\Podlove\get_help_link('podlove_help_seasons_title'),
                'description' => __('', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => [
                    'class' => 'regular-text podlove-check-input',
                    'placeholder' => $podcast->title,
                ],
            ]);

            $wrapper->string('subtitle', [
                'label' => __('Subtitle', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => [
                    'class' => 'regular-text podlove-check-input',
                    'placeholder' => $podcast->subtitle,
                ],
            ]);

            $wrapper->text('summary', [
                'label' => __('Summary', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => [
                    'rows' => 3,
                    'cols' => 40,
                    'class' => 'podlove-check-input',
                    'placeholder' => $podcast->summary,
                ],
            ]);

            $wrapper->string('start_date', [
                'label' => __('Start Date', 'podlove-podcasting-plugin-for-wordpress').\Podlove\get_help_link('podlove_help_seasons_date'),
                'html' => ['class' => 'regular-text podlove-check-input', 'readonly' => 'readonly'],
            ]);

            $wrapper->upload('image', [
                'label' => __('Image', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
                'media_button_text' => __('Use Image for Season', 'podlove-podcasting-plugin-for-wordpress'),
            ]);
        });
    }

    private function edit_template()
    {
        $season = Season::find_by_id($_REQUEST['season']);
        echo '<h3>'.sprintf(__('Edit Season: %s', 'podlove-podcasting-plugin-for-wordpress'), $season->title).'</h3>';
        $this->form_template($season, 'save');
    }
}
