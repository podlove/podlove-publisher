<?php
namespace Podlove\Modules\Contributors;

class Contributor_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'contributor',   // singular name of the listed records
		    'plural'    => 'contributors',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	
	public function column_realname( $contributor ) {
		$actions = array(
			'edit'   => Settings\Contributors::get_action_link( $contributor, __( 'Edit', 'podlove' ) ),
			'delete' => Settings\Contributors::get_action_link( $contributor, __( 'Delete', 'podlove' ), 'confirm_delete' ),
			'list'   => $this->get_episodes_link($contributor, __('Show Episodes', 'podlove'))
		);
	
		return sprintf( '%1$s %2$s',
		    Settings\Contributors::get_action_link( $contributor, $contributor->realname ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="contributor_id" value="' . $contributor->id . '">';;
	}

	private function get_episodes_link($contributor, $title) {
		return sprintf('<a href="%s">%s</a>',
			admin_url( 'edit.php?post_type=podcast&contributor=' . $contributor->slug ),
			$title
		);
	}

	public function column_publicname( $contributor ) {
		return $contributor->publicname;
	}

	public function column_slug( $contributor ) {
		return $contributor->slug;
	}
	
	public function column_role( $contributor ) {
		if ($role = $contributor->getRole()) {
			return $role->title;
		} else {
			return '';
		}
	}
	
	public function column_privateemail( $contributor ) {
		return "<a href='mailto:".$contributor->privateemail."'>".$contributor->privateemail."</a>";
	}
	
	public function column_showpublic( $contributor ) {
		return $contributor->showpublic ? '✓' : '×';
	}

	public function column_permanentcontributor( $contributor ) {
		return $contributor->permanentcontributor ? '✓' : '×';
	}

	public function column_episodes( $contributor ) {
		return $this->get_episodes_link($contributor, $contributor->contributioncount);
	}

	public function get_columns(){
		$columns = array(
			'realname'             => __( 'Real Name', 'podlove' ),
			'publicname'           => __( 'Public Name', 'podlove' ),
			'role'                 => __( 'Default Role', 'podlove' ),
			'episodes'             => __( 'Episodes', 'podlove' ),
			'slug'                 => __( 'ID', 'podlove' ),
			'privateemail'         => __( 'Private E-mail', 'podlove' ),
			'permanentcontributor' => __( 'Regular Contributor', 'podlove' ),
			'showpublic'           => __( 'Public Profile?', 'podlove' )
		);
		return $columns;
	}

	public function search_form() {
		?>
		<form method="post">
		  <?php $this->search_box('search', 'search_id'); ?>
		</form>
		<?php
	}

	public function get_sortable_columns() {
	  $sortable_columns = array(
	    'realname'             => array('realname',false),
	    'publicname'           => array('publicname',false),
	    'role'                 => array('role',false),
	    'episodes'             => array('contributioncount',true),
	    'slug'                 => array('slug',false),
	    'privateemail'         => array('privateemail',false),
	    'permanentcontributor' => array('permanentcontributor',false),
	    'showpublic'           => array('showpublic',false)
	  );
	  return $sortable_columns;
	}		

	/**
	 * @override
	 */
	public function display() {
		parent::display();
		?>
		<style type="text/css">
		/* avoid mouseover jumping */
		#permanentcontributor { width: 160px; }
		</style>
		<?php
	}

	public function prepare_items() {

		// number of items per page
		$per_page = get_user_meta( get_current_user_id(), 'podlove_contributors_per_page', true);
		if( empty($per_page) ) {
			$per_page = 10;
		}

		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// look for order options
		if( isset($_GET['orderby'])  ) {
			$orderby = 'ORDER BY ' . $_GET['orderby'];
		} else{
			$orderby = 'ORDER BY contributioncount';
		}

		// look how to sort
		if( isset($_GET['order'])  ) {
			$order = $_GET['order'];
		} else{
			$order = 'DESC';
		}
		
		// retrieve data
		if( !isset($_POST['s']) ) {
			$data = \Podlove\Modules\Contributors\Model\Contributor::all( $orderby . ' ' . $order );
		} else if ( empty($_POST['s']) ) {
			$data = \Podlove\Modules\Contributors\Model\Contributor::all( $orderby . ' ' . $order );
		} else {
			$foo = $_POST['s'];
			$data = \Podlove\Modules\Contributors\Model\Contributor::all( 'WHERE 
																			`slug` LIKE \'%'.$foo.'%\' OR
																			`gender` LIKE \'%'.$foo.'%\' OR
																			`organisation` LIKE \'%'.$foo.'%\' OR
																			`slug` LIKE \'%'.$foo.'%\' OR
																			`department` LIKE \'%'.$foo.'%\' OR
																			`twitter` LIKE \'%'.$foo.'%\' OR
																			`adn` LIKE \'%'.$foo.'%\' OR
																			`facebook` LIKE \'%'.$foo.'%\' OR
																			`flattr` LIKE \'%'.$foo.'%\' OR
																			`publicemail` LIKE \'%'.$foo.'%\' OR
																			`privateemail` LIKE \'%'.$foo.'%\' OR
																			`role` LIKE \'%'.$foo.'%\' OR
																			`realname` LIKE \'%'.$foo.'%\' OR
																			`publicname` LIKE \'%'.$foo.'%\' OR
																			`guid` LIKE \'%'.$foo.'%\' OR
																			`www` LIKE \'%'.$foo.'%\'
																			'.$orderby.' '.$order );
		}
		
		// get current page
		$current_page = $this->get_pagenum();
		// get total items
		$total_items = count( $data );
		// extrage page for current page only
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ) , $per_page );
		// add items to table
		$this->items = $data;
		
		// register pagination options & calculations
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page,
		    'total_pages' => ceil( $total_items / $per_page )
		) );

		// Search box
		$this->search_form();
	}
}
