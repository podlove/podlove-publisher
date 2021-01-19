<?php

namespace Podlove\Modules\Networks;

use Podlove\Cache\TemplateCache;
use Podlove\Model\Episode;
use Podlove\Modules\Networks\Model\Network;

class Podcast_List_Table extends \Podlove\List_Table
{
    public function __construct()
    {
        global $status, $page;

        // Set parent defaults
        parent::__construct([
            'singular' => 'podcast',   // singular name of the listed records
            'plural' => 'podcasts',  // plural name of the listed records
            'ajax' => false,       // does this table support ajax?
        ]);
    }

    public function no_items_content()
    {
        ?>
		<span class="add-new-h2" style="background: transparent">
			<?php _e('No podcasts exist yet.', 'podlove-podcasting-plugin-for-wordpress'); ?>
		</span>
		<?php
    }

    public function column_title($podcast)
    {
        return $podcast->with_blog_scope(function () use ($podcast) {
            if ($podcast->title) {
                return "<a href='".admin_url()."admin.php?page=podlove_settings_handle'>".$podcast->title.'</a> <br />'.$podcast->subtitle;
            }

            return sprintf(__('No podcast title in blog %s.', 'podlove-podcasting-plugin-for-wordpress'), '<a href="'.admin_url().'">'.get_bloginfo('name').'</a>');
        });
    }

    public function column_logo($podcast)
    {
        if (!trim($podcast->cover_art()->url())) {
            return;
        }

        return $podcast->cover_art()->setWidth(70)->image(['alt' => $podcast->title]);
    }

    public function column_episodes($podcast)
    {
        return $podcast->with_blog_scope(function () {
            return count(Episode::find_all_by_time());
        });
    }

    public function column_downloads($podcast)
    {
        return $podcast->with_blog_scope(function () {
            $total = TemplateCache::get_instance()
                ->cache_for('podlove_downloads_total', '\Podlove\Model\DownloadIntentClean::total_downloads', 5 * MINUTE_IN_SECONDS)
            ;

            return is_numeric($total) ? number_format_i18n($total) : __('no data', 'podlove-podcasting-plugin-for-wordpress');
        });
    }

    public function column_latest_episode($podcast)
    {
        return $podcast->with_blog_scope(function () {
            if ($latest_episode = Episode::latest()) {
                $latest_episode_blog_post = get_post($latest_episode->post_id);

                return "<a title='Published on ".date('Y-m-d h:i:s', strtotime($latest_episode_blog_post->post_date))."' href='".admin_url().'post.php?post='.$latest_episode->post_id."&action=edit'>".$latest_episode_blog_post->post_title.'</a>'
                     .'<br />'.\Podlove\relative_time_steps(strtotime($latest_episode_blog_post->post_date));
            }

            return '—';
        });
    }

    public function get_columns()
    {
        return [
            'logo' => __('Logo', 'podlove-podcasting-plugin-for-wordpress'),
            'title' => __('Title', 'podlove-podcasting-plugin-for-wordpress'),
            'episodes' => __('Episodes', 'podlove-podcasting-plugin-for-wordpress'),
            'downloads' => __('Downloads', 'podlove-podcasting-plugin-for-wordpress'),
            'latest_episode' => __('Latest Episode', 'podlove-podcasting-plugin-for-wordpress'),
        ];
    }

    public function search_form()
    {
        ?>
		<form method="post">
		  <?php $this->search_box('search', 'search_id'); ?>
		</form>
		<?php
    }

    /**
     * @override
     */
    public function display()
    {
        parent::display(); ?>
		<style type="text/css">
		/* avoid mouseover jumping */
		#permanentcontributor { width: 160px; }
		</style>
		<?php
    }

    public function prepare_items()
    {
        // define column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = false;
        $this->_column_headers = [$columns, $hidden, $sortable];
        $items = Network::podcasts();

        uasort($items, function ($a, $b) {
            return strnatcmp($a->title, $b->title);
        });

        $this->items = $items;
    }
}
