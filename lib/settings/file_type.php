<?php

namespace Podlove\Settings;

class FileType
{
    use \Podlove\HasPageDocumentationTrait;

    public function __construct($handle)
    {
        add_action('admin_init', [$this, 'process_form']);
    }

    public function process_form()
    {
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
			<?php
            $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;
        switch ($action) {
                case 'new':
                    $this->new_template();

                    break;
                case 'edit':
                    $this->edit_template();

                    break;
                case 'index':
                default:
                    $this->view_template();

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
        if (!isset($_REQUEST['file_type'])) {
            return;
        }

        $format = \Podlove\Model\FileType::find_by_id($_REQUEST['file_type']);

        if (!isset($_POST['podlove_file_type']) || !is_array($_POST['podlove_file_type'])) {
            return;
        }

        foreach ($_POST['podlove_file_type'] as $key => $value) {
            $value = trim($value);
            $value = $key === 'extension' ? trim($value, '.') : $value;
            $format->{$key} = $value;
        }

        $format->save();

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $format->id);
        } else {
            $this->redirect('index', $format->id);
        }
    }

    /**
     * Process form: create a format.
     */
    private function create()
    {
        global $wpdb;

        $format = new \Podlove\Model\FileType();

        if (!isset($_POST['podlove_file_type']) || !is_array($_POST['podlove_file_type'])) {
            return;
        }

        foreach ($_POST['podlove_file_type'] as $key => $value) {
            $format->{$key} = $value;
        }
        $format->save();

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $format->id);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Process form: delete a format.
     */
    private function delete()
    {
        if (!isset($_REQUEST['file_type'])) {
            return;
        }

        \Podlove\Model\FileType::find_by_id($_REQUEST['file_type'])->delete();

        $this->redirect('index');
    }

    /**
     * Helper method: redirect to a certain page.
     *
     * @param mixed      $action
     * @param null|mixed $format_id
     */
    private function redirect($action, $format_id = null)
    {
        $page = 'admin.php?page='.filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING).'&podlove_tab='.filter_var($_REQUEST['podlove_tab'], FILTER_SANITIZE_STRING);
        $show = ($format_id) ? '&file_type='.$format_id : '';
        $action = '&action='.$action;

        wp_redirect(admin_url($page.$show.$action));
        exit;
    }

    private function new_template()
    {
        $format = new \Podlove\Model\FileType(); ?>
		<h3><?php echo __('Add New File Type', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<?php
        $this->form_template($format, 'create', __('Add New Format', 'podlove-podcasting-plugin-for-wordpress'));
    }

    private function view_template()
    {
        ?>
		<h2>
			<a href="?page=<?php echo filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING); ?>&amp;podlove_tab=<?php echo filter_var($_REQUEST['podlove_tab'], FILTER_SANITIZE_STRING); ?>&amp;action=new" class="add-new-h2"><?php echo __('Add New', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
		</h2>
		<p>
			<?php echo __('This is a list of all file types Podlove Publisher knows about. If you would like to serve assets of an unknown file type, you must add it here before you can create the asset.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<?php
        $table = new \Podlove\File_Type_List_Table();
        $table->prepare_items();
        $table->display();
    }

    private function form_template($format, $action, $button_text = null)
    {
        $form_args = [
            'context' => 'podlove_file_type',
            'hidden' => [
                'file_type' => $format->id,
                'action' => $action,
                'podlove_tab' => filter_var($_REQUEST['podlove_tab'], FILTER_SANITIZE_STRING),
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

        \Podlove\Form\build_for($format, $form_args, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            $types = [];
            foreach (\Podlove\Model\FileType::get_types() as $type) {
                $types[$type] = $type;
            }

            $wrapper->string('name', [
                'label' => __('Name', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'podlove-check-input'],
                'description' => '', ]);

            $wrapper->select('type', [
                'label' => __('Document Type', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $types,
            ]);

            $wrapper->string('mime_type', [
                'label' => __('Format Mime Type', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'podlove-check-input'],
                'description' => __('Example: audio/mp4', 'podlove-podcasting-plugin-for-wordpress'), ]);

            $wrapper->string('extension', [
                'label' => __('Format Extension', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'podlove-check-input'],
                'description' => __('Example: m4a', 'podlove-podcasting-plugin-for-wordpress'), ]);
        });
    }

    private function edit_template()
    {
        $format = \Podlove\Model\FileType::find_by_id($_REQUEST['file_type']); ?>
		<h3><?php echo __('Edit File Type', 'podlove-podcasting-plugin-for-wordpress'); ?>: <?php echo $format->name; ?></h3>
		
		<?php $this->form_template($format, 'save'); ?>
		<?php
    }
}
