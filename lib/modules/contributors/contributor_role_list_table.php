<?php

namespace Podlove\Modules\Contributors;

class Contributor_Role_List_Table extends \Podlove\List_Table
{
    public function __construct()
    {
        global $status, $page;

        // Set parent defaults
        parent::__construct([
            'singular' => 'contributor role',   // singular name of the listed records
            'plural' => 'contributor roles',  // plural name of the listed records
            'ajax' => false,       // does this table support ajax?
        ]);
    }

    public function column_title($role)
    {
        $actions = [
            'edit' => Settings\GenericEntitySettings::get_action_link('role', $role->id, __('Edit', 'podlove-podcasting-plugin-for-wordpress')),
            'delete' => Settings\GenericEntitySettings::get_action_link('role', $role->id, __('Delete', 'podlove-podcasting-plugin-for-wordpress'), 'confirm_delete'),
        ];

        return sprintf(
            '%1$s %2$s',
            Settings\GenericEntitySettings::get_action_link('role', $role->id, $role->title),
            $this->row_actions($actions)
        ).'<input type="hidden" class="role_id" value="'.$role->id.'">';
    }

    public function column_slug($role)
    {
        return $role->slug;
    }

    public function get_columns()
    {
        return [
            'title' => __('Role Title', 'podlove-podcasting-plugin-for-wordpress'),
            'slug' => __('Role Slug', 'podlove-podcasting-plugin-for-wordpress'),
        ];
    }

    public function prepare_items()
    {
        // number of items per page
        $per_page = 10;

        // define column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // retrieve data
        $data = \Podlove\Modules\Contributors\Model\ContributorRole::all('ORDER BY title ASC');

        // get current page
        $current_page = $this->get_pagenum();
        // get total items
        $total_items = count($data);
        // extrage page for current page only
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        // add items to table
        $this->items = $data;

        // register pagination options & calculations
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }
}
