<?php
namespace Podlove\Modules\Networks;

use \Podlove\Modules\Networks\Model\PodcastList;
use \Podlove\Modules\Networks\Model\Network;
use \Podlove\Model\Episode;

class Podcast_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'podcast',   // singular name of the listed records
		    'plural'    => 'podcasts',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	public function no_items_content() {
		?>
		<span class="add-new-h2" style="background: transparent">
			<?php _e( 'No podcasts exist yet.', 'podlove' ); ?>
		</span>
		<?php
	}
	
	public function column_title($podcast) {
		return $podcast->with_blog_scope(function() use ($podcast) {
			if ($podcast->title) {
				return "<a href='" . admin_url() . "admin.php?page=podlove_settings_handle'>" . $podcast->title . "</a> <br />" . $podcast->subtitle;
			} else {
				return sprintf(__("No podcast title in blog %s.", 'podlove'), '<a href="' . admin_url() . '">' . get_bloginfo("name") . '</a>');
			}
		});
	}

	public function column_logo( $podcast ) {
		if (!trim($podcast->cover_image)) {
			return;
		} else {
			return "<img src='" . $podcast->cover_image . "' title='" . $podcast->title . "' alt='" . $podcast->title . "' />";
		}
	}	

	public function column_episodes( $podcast ) {
		return $podcast->with_blog_scope(function() {
			return count(Episode::find_all_by_time());
		});
	}

	public function column_latest_episode( $podcast ) {
		return $podcast->with_blog_scope(function() {
			if ($latest_episode = Episode::latest()) {
				$latest_episode_blog_post = get_post( $latest_episode->post_id );
		 		return "<a title='Published on " . date('Y-m-d h:i:s', strtotime( $latest_episode_blog_post->post_date )) ."' href='" . admin_url() . "post.php?post=" . $latest_episode->post_id . "&action=edit'>" . $latest_episode_blog_post->post_title . "</a>"
	 			     . "<br />" . \Podlove\relative_time_steps( strtotime( $latest_episode_blog_post->post_date ) );
			} else {
				return "—";
			}
		});
	}

	public function get_columns(){
		return [
			'logo'           => __( 'Logo', 'podlove' ),
			'title'          => __( 'Title', 'podlove' ),
			'episodes'       => __( 'Episodes', 'podlove' ),
			'latest_episode' => __( 'Latest Episode', 'podlove' )
		];
	}

	public function search_form() {
		?>
		<form method="post">
		  <?php $this->search_box('search', 'search_id'); ?>
		</form>
		<?php
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

		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = false;
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$items = Network::podcasts();

		uasort( $items, function ( $a, $b ) {
			return strnatcmp( $a->title, $b->title );
		});

		$this->items = $items;
	}
}
