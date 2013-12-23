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

	public function column_avatar( $contributor ) {
		
		return $contributor->getAvatar("45px");
	}
	
	public function column_realname( $contributor ) {
		$actions = array(
			'edit'   => Settings\Contributors::get_action_link( $contributor, __( 'Edit', 'podlove' ) ),
			'delete' => Settings\Contributors::get_action_link( $contributor, __( 'Delete', 'podlove' ), 'confirm_delete' ),
			'list'   => $this->get_episodes_link($contributor, __('Show Episodes', 'podlove'))
		);
	
		if (!($name = $contributor->realname))
			$name = $contributor->publicname;

		return sprintf( '<strong>%1$s</strong><br /><em>%2$s %3$s</em><br />%4$s',
		    Settings\Contributors::get_action_link( $contributor, $name ),
		    $contributor->publicname,
		    ( $contributor->nickname == "" ? "" : " (" . $contributor->nickname . ")"  ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="contributor_id" value="' . $contributor->id . '">';;
	}

	private function get_episodes_link($contributor, $title) {
		return sprintf('<a href="%s">%s</a>',
			admin_url( 'edit.php?post_type=podcast&contributor=' . $contributor->slug ),
			$title
		);
	}

	public function column_slug( $contributor ) {
		return $contributor->slug;
	}

	public function column_gender( $contributor ) {
		if( $contributor->gender == 'none' ) {
			return 'Not set';
		} else {
			return ucfirst($contributor->gender);	
		}
	}

	public function column_social( $contributor ) {
		$social_services = array(
				'appdotnet'	=> array(
								'title' => 'App.net',
								'url_template' => 'http://alpha.app.net/',
								'account' => $contributor->adn 
							   ),
				'twitter'  => array( 
								'title' => 'Twitter',
								'url_template' => 'http://twitter.com/',
								'account' => $contributor->twitter
							  ),
				'facebook' => array(
								'title' => 'Facebook',
								'url_template' => '',
								'account' => $contributor->facebook
							  )
		);

		$social = '';
		foreach ( $social_services as $service => $details ) {
			( $details['account'] == "" ? "" : $social = $social . '<i class="podlove-icon-' . $service .'" title="' . $details['title'] . '"></i> <a target="_blank" href="' . $details['url_template'] . $details['account'] . '">' . $details['account'] . '</a><br />' );
		}

		return $social;
	}

	public function column_affiliation( $contributor ) {
		$affiliation = '';
		( $contributor->organisation == "" ? "" : $affiliation = $affiliation . '<strong>' . $contributor->organisation . '</strong><br />' );
		( $contributor->department == "" ? "" : $affiliation = $affiliation .$contributor->department . '<br />' );
		( $contributor->jobtitle == "" ? "" : $affiliation = $affiliation . '<em>' .  $contributor->jobtitle . '</em><br />' );
		return $affiliation;
	}
	
	public function column_privateemail( $contributor ) {
		return "<a href='mailto:".$contributor->privateemail."'>".$contributor->privateemail."</a>";
	}
	
	public function column_showpublic( $contributor ) {
		return $contributor->showpublic ? '✓' : '×';
	}

	public function column_episodes( $contributor ) {
		return $this->get_episodes_link($contributor, $contributor->contributioncount);
	}

	public function get_columns(){
		$columns = array(
			'avatar'             => __( '', 'podlove' ),
			'realname'             => __( 'Contributor', 'podlove' ),
			'slug'                 => __( 'ID', 'podlove' ),
			'gender'             => __( 'Gender', 'podlove' ),
			'affiliation'             => __( 'Affiliation', 'podlove' ),
			'social'             => __( 'Social', 'podlove' ),
			'privateemail'         => __( 'Private E-mail', 'podlove' ),
			'episodes'             => __( 'Episodes', 'podlove' ),
			'showpublic'           => __( 'Public', 'podlove' )
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
	    'slug'                 => array('slug',false),
	    'gender'               => array('gender',false),
	    'affiliation'          => array('organisation',false),
	    'privateemail'         => array('privateemail',false),
	    'episodes'             => array('contributioncount',true),
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
		td.column-avatar, th.column-avatar { width: 50px; }
		td.column-slug, th.column-slug { width: 12% !important; }
		td.column-showpublic, th.column-showpublic { width: 7% !important; }
		td.column-gender, th.column-gender { width: 7% !important; }
		td.column-episodes, th.column-episodes { width: 8% !important; }
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
		$this->_column_headers = $this->get_column_info();

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
																			`jobtitle` LIKE \'%'.$foo.'%\' OR
																			`twitter` LIKE \'%'.$foo.'%\' OR
																			`adn` LIKE \'%'.$foo.'%\' OR
																			`facebook` LIKE \'%'.$foo.'%\' OR
																			`flattr` LIKE \'%'.$foo.'%\' OR
																			`paypal` LIKE \'%'.$foo.'%\' OR
																			`bitcoin` LIKE \'%'.$foo.'%\' OR
																			`litecoin` LIKE \'%'.$foo.'%\' OR
																			`publicemail` LIKE \'%'.$foo.'%\' OR
																			`privateemail` LIKE \'%'.$foo.'%\' OR
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

	function no_items() {
		$url = sprintf( '?page=%s&action=%s&post_type=podcast', $_REQUEST['page'], 'new' );
		?>
		<div style="margin: 20px 10px 10px 5px">
	 		<span class="add-new-h2" style="background: transparent">
			<?php _e( 'No items found.' ); ?>
			</span>
			<a href="<?php echo $url ?>" class="add-new-h2">
	 		<?php _e( 'Add New' ) ?>
	 		</a>
	 	</div>
	 	<?php
	 }
}
