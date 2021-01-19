<?php

namespace Podlove\Modules\Contributors;

class Contributor_List_Table extends \Podlove\List_Table
{
    public function __construct()
    {
        global $status, $page;

        // Set parent defaults
        parent::__construct([
            'singular' => 'contributor',   // singular name of the listed records
            'plural' => 'contributors',  // plural name of the listed records
            'ajax' => false,       // does this table support ajax?
        ]);
    }

    public function column_avatar($contributor)
    {
        return $contributor
            ->avatar()
            ->setWidth(45)
            ->image()
        ;
    }

    public function column_realname($contributor)
    {
        $actions = [
            'edit' => Settings\GenericEntitySettings::get_action_link('contributor', $contributor->id, __('Edit', 'podlove-podcasting-plugin-for-wordpress')),
            'delete' => Settings\GenericEntitySettings::get_action_link('contributor', $contributor->id, __('Delete', 'podlove-podcasting-plugin-for-wordpress'), 'confirm_delete'),
            'list' => $this->get_episodes_link($contributor, __('Show Episodes', 'podlove-podcasting-plugin-for-wordpress')),
        ];

        return sprintf(
            '<strong>%1$s</strong><br /><em>%2$s %3$s</em><br />%4$s',
            Settings\GenericEntitySettings::get_action_link('contributor', $contributor->id, $contributor->getName()),
            $contributor->realname,
            ($contributor->nickname == '' ? '' : ' ('.$contributor->nickname.')'),
            $this->row_actions($actions)
        ).'<input type="hidden" class="contributor_id" value="'.$contributor->id.'">';
    }

    public function column_identifier($contributor)
    {
        return $contributor->identifier;
    }

    public function column_gender($contributor)
    {
        if ($contributor->gender == 'none') {
            return 'Not set';
        }

        return ucfirst($contributor->gender);
    }

    public function column_affiliation($contributor)
    {
        $affiliation = '';
        ($contributor->organisation == '' ? '' : $affiliation = $affiliation.'<strong>'.$contributor->organisation.'</strong><br />');
        ($contributor->department == '' ? '' : $affiliation = $affiliation.$contributor->department.'<br />');
        ($contributor->jobtitle == '' ? '' : $affiliation = $affiliation.'<em>'.$contributor->jobtitle.'</em><br />');

        return $affiliation;
    }

    public function column_privateemail($contributor)
    {
        return "<a href='mailto:".$contributor->privateemail."'>".$contributor->privateemail.'</a>';
    }

    public function column_default($contributor, $column_name)
    {
        return apply_filters('podlove_contributor_list_table_column_default', null, $contributor, $column_name);
    }

    public function column_visibility($contributor)
    {
        return $contributor->visibility ? '✓' : '×';
    }

    public function column_episodes($contributor)
    {
        return $this->get_episodes_link($contributor, $contributor->contributioncount);
    }

    public function column_social($contributor)
    {
        return $this->service_column_templates($contributor);
    }

    public function column_donation($contributor)
    {
        return $this->service_column_templates($contributor, 'donation');
    }

    public function get_columns()
    {
        $columns = [
            'avatar' => __('', 'podlove-podcasting-plugin-for-wordpress'),
            'realname' => __('Contributor', 'podlove-podcasting-plugin-for-wordpress'),
            'identifier' => __('Template ID', 'podlove-podcasting-plugin-for-wordpress'),
            'gender' => __('Gender', 'podlove-podcasting-plugin-for-wordpress'),
            'affiliation' => __('Affiliation', 'podlove-podcasting-plugin-for-wordpress'),
            'privateemail' => __('Private E-mail', 'podlove-podcasting-plugin-for-wordpress'),
            'episodes' => __('Episodes', 'podlove-podcasting-plugin-for-wordpress'),
            'visibility' => __('Visiblity', 'podlove-podcasting-plugin-for-wordpress'),
        ];

        return apply_filters('podlove_contributor_list_table_columns', $columns);
    }

    public function search_form()
    {
        ?>
		<form method="post">
		  <?php $this->search_box('search', 'search_id'); ?>
		</form>
		<?php
    }

    public function get_sortable_columns()
    {
        return [
            'realname' => ['realname', false],
            'identifier' => ['identifier', false],
            'gender' => ['gender', false],
            'affiliation' => ['organisation', false],
            'privateemail' => ['privateemail', false],
            'episodes' => ['contributioncount', true],
            'visibility' => ['visibility', false],
        ];
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
		td.column-avatar, th.column-avatar { width: 50px; }
		td.column-identifier, th.column-identifier { width: 12% !important; }
		td.column-visibility, th.column-visibility { width: 7% !important; }
		td.column-gender, th.column-gender { width: 7% !important; }
		td.column-episodes, th.column-episodes { width: 8% !important; }
		.add-new-h2 { float: left; }
		</style>
		<?php
    }

    public function prepare_items()
    {
        global $wpdb;

        // number of items per page
        $per_page = get_user_meta(get_current_user_id(), 'podlove_contributors_per_page', true);
        if (empty($per_page)) {
            $per_page = 10;
        }

        // define column headers
        $this->_column_headers = $this->get_column_info();

        // look for order options

        $orderby_whitelist = array_keys($this->get_sortable_columns());

        if (isset($_GET['orderby']) && in_array($_GET['orderby'], $orderby_whitelist)) {
            $orderby = 'ORDER BY '.$_GET['orderby'];
        } else {
            $orderby = 'ORDER BY contributioncount';
        }

        // look how to sort
        if (strtolower(filter_input(INPUT_GET, 'order')) === 'asc') {
            $order = 'ASC';
        } else {
            $order = 'DESC';
        }

        // retrieve data
        if (!isset($_POST['s']) || empty($_POST['s'])) {
            $data = \Podlove\Modules\Contributors\Model\Contributor::all($orderby.' '.$order);
        } else {
            $search = \Podlove\esc_like($_POST['s']);
            $search = '%'.$search.'%';

            $search_columns = ['gender', 'organisation', 'identifier', 'department', 'jobtitle', 'privateemail', 'realname', 'publicname', 'guid'];
            $search_columns = apply_filters('podlove_contributor_list_table_search_db_columns', $search_columns);

            $like_searches = implode(' OR ', array_map(function ($column) use ($search) {
                return '`'.$column.'` LIKE \''.$search.'\'';
            }, $search_columns));

            $data = \Podlove\Modules\Contributors\Model\Contributor::all(
                'WHERE '.$like_searches.' '.$orderby.' '.$order
            );
        }

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

        // Search box
        $this->search_form();
    }

    public function no_items()
    {
        $url = sprintf('?page=%s&action=%s&podlove_tab=contributors', filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING), 'new'); ?>
		<div style="margin: 20px 10px 10px 5px">
	 		<span class="add-new-h2" style="background: transparent">
			<?php _e('No items found.'); ?>
			</span>
			<a href="<?php echo $url; ?>" class="add-new-h2">
		 		<?php _e('Add New'); ?>
	 		</a>
	 	</div>
	 	<?php
    }

    private function get_episodes_link($contributor, $title)
    {
        return sprintf(
            '<a href="%s">%s</a>',
            admin_url('edit.php?post_type=podcast&contributor='.$contributor->id),
            $title
        );
    }

    private function service_column_templates($contributor, $type = 'social')
    {
        $contributor_services = \Podlove\Modules\Social\Model\ContributorService::find_by_contributor_id_and_category($contributor->id, $type);
        $source = '';

        foreach ($contributor_services as $contributor_service) {
            $service = $contributor_service->get_service();

            $source .= '<li>'
                    .$service->image()->setWidth(16)->image(['class' => 'podlove-contributor-list-social-logo'])
                    ."<a href='".$contributor_service->get_service_url()."'>"
                    .($service->url_scheme == '%account-placeholder%' ? 'link' : $contributor_service->value)
                    .'</a>'
                    ."</li>\n";
        }

        return '<ul class="podlove-contributor-social-list">'.$source.'</ul>';
    }
}
