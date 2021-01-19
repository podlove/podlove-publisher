<?php

namespace Podlove\Modules\Contributors\Settings;

/**
 * Provide a standard settings page for an entity with:.
 *
 * 1) list table view
 * 2) edit form per item
 */
class GenericEntitySettings
{
    private $entity_slug;
    private $entity_class;
    private $form_callback;
    private $labels = [];

    private $is_tab = false;
    private $tab_slug = '';

    public function __construct($entity_slug, $entity_class)
    {
        $this->entity_slug = $entity_slug;
        $this->entity_class = $entity_class;

        $default_labels = [
            'delete_confirm' => __('You selected to delete the entity "%s". Please confirm this action.', 'podlove-podcasting-plugin-for-wordpress'),
            'delete_button_delete' => __('Delete permanently', 'podlove-podcasting-plugin-for-wordpress'),
            'delete_button_keep' => __('Don\'t change anything', 'podlove-podcasting-plugin-for-wordpress'),
            'add_new' => __('Add New', 'podlove-podcasting-plugin-for-wordpress'),
            'edit' => __('Edit', 'podlove-podcasting-plugin-for-wordpress'),
        ];

        $this->labels = $default_labels;

        add_action('admin_init', [$this, 'process_form']);
    }

    public function enable_tabs($tab_slug)
    {
        $this->is_tab = true;
        $this->tab_slug = $tab_slug;
    }

    public function set_labels($labels)
    {
        $this->labels = wp_parse_args($labels, $this->labels);
    }

    public function set_form($form_callback)
    {
        $this->form_callback = $form_callback;
    }

    public function process_form()
    {
        if (!isset($_REQUEST[$this->get_entity_slug()])) {
            return;
        }

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

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
        if (isset($_GET['action']) and $_GET['action'] == 'confirm_delete' and isset($_REQUEST[$this->get_entity_slug()])) {
            $class = $this->get_entity_class();
            $entity = $class::find_by_id($_REQUEST[$this->get_entity_slug()]);

            $title = $entity->title;
            if (!$title && method_exists($entity, 'getName')) {
                $title = $entity->getName();
            } ?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf($this->labels['delete_confirm'], $title); ?>
					</strong>
				</p>
				<p>
					<?php echo self::get_action_link($this->get_entity_slug(), $entity->id, $this->labels['delete_button_delete'], 'delete', 'button'); ?>
					<?php echo self::get_action_link($this->get_entity_slug(), $entity->id, $this->labels['delete_button_keep'], 'keep', 'button-primary'); ?>
				</p>
			</div>
			<?php
        } ?>
		<div class="wrap">
			<?php
                do_action('podlove_settings_'.$this->entity_slug.'_before');

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
        }

        do_action('podlove_settings_'.$this->entity_slug); ?>
		</div>	
		<?php
    }

    public static function get_action_link($entity_slug, $id, $title, $action = 'edit', $class = 'link')
    {
        $podlove_tab = filter_input(INPUT_GET, 'podlove_tab', FILTER_SANITIZE_STRING);
        $request = $podlove_tab ? '&amp;podlove_tab='.$podlove_tab : '';
        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);

        return sprintf(
            '<a href="?page=%s%s&amp;action=%s&amp;%s=%s" class="%s">'.$title.'</a>',
            $page,
            $request,
            $action,
            $entity_slug,
            $id,
            $class
        );
    }

    /**
     * Process form: save/update entity.
     */
    protected function save()
    {
        $slug = $this->get_entity_slug();

        if (!isset($_REQUEST[$slug])) {
            return;
        }

        $class = $this->get_entity_class();

        $entity = $class::find_by_id($_REQUEST[$slug]);

        $attributes = $_POST['podlove_'.$slug];
        $attributes = apply_filters('podlove_generic_entity_attributes', $attributes);
        $attributes = apply_filters('podlove_generic_entity_attributes_'.$slug, $attributes);

        $entity->update_attributes($attributes);

        do_action('podlove_update_entity_'.$slug, $entity);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $entity->id);
        } else {
            $this->redirect('index', $entity->id);
        }
    }

    /**
     * Process form: create entity.
     */
    protected function create()
    {
        global $wpdb;

        $class = $this->get_entity_class();

        $entity = new $class();
        $entity->update_attributes($_POST['podlove_'.$this->get_entity_slug()]);

        do_action('podlove_create_entity_'.$this->get_entity_slug(), $entity);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $entity->id);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Process form: delete a contributor.
     */
    protected function delete()
    {
        if (!isset($_REQUEST[$this->get_entity_slug()])) {
            return;
        }

        $class = $this->get_entity_class();
        $class::find_by_id($_REQUEST[$this->get_entity_slug()])->delete();

        $this->redirect('index');
    }

    protected function new_template()
    {
        $class = $this->get_entity_class();
        $entity = new $class();

        echo '<h3>'.$this->labels['add_new'].'</h3>';
        do_action('podlove_settings_'.$this->entity_slug.'_new_before');
        $this->form_template($entity, 'create');
        do_action('podlove_settings_'.$this->entity_slug.'_new');
    }

    protected function edit_template()
    {
        $class = $this->get_entity_class();
        $entity = $class::find_by_id($_REQUEST[$this->get_entity_slug()]);
        echo '<h3>'.$this->labels['edit'].'</h3>';
        do_action('podlove_settings_'.$this->entity_slug.'_edit_before');
        $this->form_template($entity, 'save');
        do_action('podlove_settings_'.$this->entity_slug.'_edit');
    }

    protected function view_template()
    {
        $tab = $this->is_tab ? '&amp;podlove_tab='.$this->tab_slug : '';
        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING); ?>
		<h2>
			<a href="?page=<?php echo $page.$tab; ?>&amp;action=new" class="add-new-h2"><?php echo $this->labels['add_new']; ?></a>
		</h2>
		<?php
        do_action('podlove_settings_'.$this->entity_slug.'_view');
    }

    /**
     * Helper method: redirect to a certain page.
     *
     * @param mixed      $action
     * @param null|mixed $entity_id
     */
    protected function redirect($action, $entity_id = null)
    {
        $page = 'admin.php?page='.filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
        $show = $entity_id ? '&'.$this->get_entity_slug().'='.$entity_id : '';
        $action = '&action='.$action;
        $tab = $this->is_tab ? '&podlove_tab='.$this->tab_slug : '';

        wp_redirect(admin_url($page.$show.$action.$tab));
        exit;
    }

    private function get_entity_slug()
    {
        return $this->entity_slug;
    }

    private function get_entity_class()
    {
        return $this->entity_class;
    }

    private function form_template($entity, $action)
    {
        $form_args = [
            'context' => 'podlove_'.$this->get_entity_slug(),
            'hidden' => ['action' => $action],
            'submit_button' => false, // for custom control in form_end
            'form_end' => function () {
                echo '<p>';
                submit_button(__('Save Changes', 'podlove-podcasting-plugin-for-wordpress'), 'primary', 'submit', false);
                echo ' ';
                submit_button(__('Save Changes and Continue Editing', 'podlove-podcasting-plugin-for-wordpress'), 'secondary', 'submit_and_stay', false);
                echo '</p>';
            },
        ];

        $form_args['hidden'][$this->get_entity_slug()] = $entity->id;

        $cb = $this->form_callback;
        $cb($form_args, $entity, $action);
    }
}
