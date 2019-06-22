<?php
namespace Podlove\Modules\Shows\Settings;

use \Podlove\Modules\Shows\Model\Show;

class ShowListTable extends \Podlove\List_Table
{
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'show', // singular name of the listed records
            'plural'   => 'shows', // plural name of the listed records
            'ajax'     => false, // does this table support ajax?
        ));
    }

    public function column_title($show)
    {

        $link = function ($title, $action = 'edit') use ($show) {
            return sprintf(
                '<a href="?page=%s&action=%s&show=%s">' . $title . '</a>',
                Settings::MENU_SLUG,
                $action,
                $show->id
            );
        };

        $actions = [
            'edit'   => $link(__('Edit', 'podlove-podcasting-plugin-for-wordpress')),
            'delete' => $link(__('Delete', 'podlove-podcasting-plugin-for-wordpress'), 'confirm_delete'),
        ];

        return sprintf(
            '%1$s %2$s',
            $link($show->title),
            $this->row_actions($actions)
        );
    }

    public function column_image($show)
    {
        if ($show->image) {
            return $show->image()->setWidth(64)->setHeight(64)->image();
        } else {
            return '';
        }
    }

    public function column_episodes($show)
    {
        if ($term = get_term($show->id)) {
            return $term->count;
        }

    }

    public function column_show_feeds($show)
    {
        ?> <ul> <?php
foreach (\Podlove\Model\Feed::find_all_by_discoverable(1) as $feed) {
            printf(
                '<li><a href="%1$s">%1$s</a></li>',
                $feed->get_subscribe_url("shows", $show->id)
            );
        }
        ?> </ul> <?php
}

    public function get_columns()
    {
        return array(
            'title'      => __('Show', 'podlove-podcasting-plugin-for-wordpress'),
            'image'      => __('Image', 'podlove-podcasting-plugin-for-wordpress'),
            'episodes'   => __('Episodes', 'podlove-podcasting-plugin-for-wordpress'),
            'show_feeds' => __('Subscribe URLs', 'podlove-podcasting-plugin-for-wordpress'),
        );
    }

    public function prepare_items()
    {
        // number of items per page
        $per_page = get_user_meta(get_current_user_id(), 'podlove_shows_per_page', true);
        if (empty($per_page)) {
            $per_page = 10;
        }

        // define column headers
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // retrieve data
        $data = Show::all();

        // get current page
        $current_page = $this->get_pagenum();
        // get total items
        $total_items = count($data);
        // extrage page for current page only
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        // add items to table
        $this->items = $data;

        // register pagination options & calculations
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }

}
