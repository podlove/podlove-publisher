<?php

namespace Podlove\Settings;

class FileType
{
    use \Podlove\HasPageDocumentationTrait;

    private static $nonce = 'update_file_type';

    public function __construct() {}

    public function page()
    {
        ?>
		<div class="wrap">
			<?php $this->view_template(); ?>
		</div>
		<?php
    }

    /**
     * Helper method: redirect to a certain page.
     *
     * @param mixed      $action
     * @param null|mixed $format_id
     */
    private function redirect($action, $format_id = null)
    {
        $page = 'admin.php?page='.htmlspecialchars($_REQUEST['page'] ?? '').'&podlove_tab='.htmlspecialchars($_REQUEST['podlove_tab'] ?? '');
        $show = ($format_id) ? '&file_type='.$format_id : '';
        $action = '&action='.$action;

        wp_redirect(admin_url($page.$show.$action));
        exit;
    }

    private function view_template()
    {
        ?>
		<p>
			<?php echo __('This is a list of all file types Podlove Publisher knows about.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</p>
		<?php
        $table = new \Podlove\File_Type_List_Table();
        $table->prepare_items();
        $table->display();
    }
}
