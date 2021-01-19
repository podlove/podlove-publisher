<?php

namespace Podlove;

use Podlove\Modules\Plus\FeedProxy;

class Feed_List_Table extends \Podlove\List_Table
{
    public function __construct()
    {
        global $status, $page;

        // Set parent defaults
        parent::__construct([
            'singular' => 'feed',   // singular name of the listed records
            'plural' => 'feeds',  // plural name of the listed records
            'ajax' => false,       // does this table support ajax?
        ]);
    }

    public function column_name($feed)
    {
        $actions = [
            'edit' => Settings\Feed::get_action_link($feed, __('Edit', 'podlove-podcasting-plugin-for-wordpress')),
            'delete' => Settings\Feed::get_action_link($feed, __('Delete', 'podlove-podcasting-plugin-for-wordpress'), 'confirm_delete'),
        ];

        return sprintf(
            '%1$s %2$s',
            Settings\Feed::get_action_link($feed, $feed->name),
            $this->row_actions($actions)
        ).'<input type="hidden" class="position" value="'.$feed->position.'">'
          .'<input type="hidden" class="feed_id" value="'.$feed->id.'">';
    }

    public function column_limit($feed)
    {
        $podlove_feed_limit = \Podlove\Model\Podcast::get()->limit_items;
        switch ($feed->limit_items) {
            case '0':
                return get_option('posts_per_rss').' (WordPress default)';

                break;
            case '-1':
                return 'unlimited';

                break;
            case '-2':
                return ($podlove_feed_limit == '-1' ? 'unlimited' : ($podlove_feed_limit == '0' ? get_option('posts_per_rss').' (WordPress default)' : $podlove_feed_limit))
                       .' (global default)';

                break;
            default:
                return $feed->limit_items;

                break;
        }
    }

    public function column_discoverable($feed)
    {
        return $feed->discoverable ? '✓' : '×';
    }

    public function column_protected($feed)
    {
        return $feed->protected ? '✓' : '×';
    }

    public function column_url($feed)
    {
        $link = $feed->get_subscribe_link();
        $podcast = \Podlove\Model\Podcast::get();

        if (!FeedProxy::is_enabled()) {
            if ($feed->redirect_http_status > 0 && strlen($feed->redirect_url)) {
                $link .= "<br><span title=\"redirects to\">&#8618;</span>&nbsp;<a target=\"_blank\" href=\"{$feed->redirect_url}\">{$feed->redirect_url}</a>";
            }
        } else {
            $link = apply_filters('podlove_feed_table_url', $link, $feed);
        }

        return $link;
    }

    public function column_media($feed)
    {
        $episode_asset = $feed->episode_asset();

        return ($episode_asset) ? $episode_asset->title() : __('not set', 'podlove-podcasting-plugin-for-wordpress');
    }

    public function column_move($feed)
    {
        return '<i class="reorder-handle podlove-icon-reorder"></i>';
    }

    public function get_columns()
    {
        $columns = [
            'name' => __('Feed', 'podlove-podcasting-plugin-for-wordpress'),
            'url' => __('Subscribe URL', 'podlove-podcasting-plugin-for-wordpress'),
            'media' => __('Media', 'podlove-podcasting-plugin-for-wordpress'),
            'limit' => __('Item Limit', 'podlove-podcasting-plugin-for-wordpress'),
            'discoverable' => __('Discoverable', 'podlove-podcasting-plugin-for-wordpress'),
            'move' => '',
        ];

        return apply_filters('podlove_feed_list_table_columns', $columns);
    }

    public function prepare_items()
    {
        // number of items per page
        $per_page = get_user_meta(get_current_user_id(), 'podlove_feeds_per_page', true);
        if (empty($per_page)) {
            $per_page = 10;
        }

        // define column headers
        $this->_column_headers = $this->get_column_info();

        // retrieve data
        $data = \Podlove\Model\Feed::all('ORDER BY position ASC');

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
