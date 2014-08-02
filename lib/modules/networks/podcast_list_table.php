<?php
namespace Podlove\Modules\Networks;
use \Podlove\Modules\Networks\Model\PodcastList;

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
	
	public function column_title( $podcast ) {
		switch_to_blog( $podcast->blog_id );
		
		return "<a href='" . admin_url() . "admin.php?page=podlove_settings_handle'>" . $podcast->title . "</a> <br />" . $podcast->subtitle;
	}

	public function column_logo( $podcast ) {
		if( $podcast->cover_image == "" ) {
			return;
		} else {
			return "<img src='" . $podcast->cover_image . "' title='" . $podcast->title . "' alt='" . $podcast->title . "' />";
		}
	}	

	public function column_episodes( $podcast ) {
		switch_to_blog( $podcast->blog_id );

		$episodes = array_filter( \Podlove\Model\Episode::allByTime() , function($e) { return $e->is_valid(); });
		return count( $episodes );
	}

	public function column_latest_episode( $podcast ) {
		switch_to_blog( $podcast->blog_id );

		$episodes = array_filter( \Podlove\Model\Episode::allByTime() , function($e) { return $e->is_valid(); });
		$latest_episode = reset($episodes);
		$latest_episode_blog_post = get_post( $latest_episode->post_id );
 		return "<a title='Published on " . date('Y-m-d h:i:s', strtotime( $latest_episode_blog_post->post_date )) ."' href='" . admin_url() . "post.php?post=" . $latest_episode->post_id . "&action=edit'>" . $latest_episode_blog_post->post_title . "</a>"
 				. "<br />" . \Podlove\relative_time_steps( strtotime( $latest_episode_blog_post->post_date ) );
	}

	public function get_columns(){
		$columns = array(
			'logo'             => __( 'Logo', 'podlove' ),
			'title'           => __( 'Title', 'podlove' ),
			'episodes'                 => __( 'Episodes', 'podlove' ),
			'latest_episode'                 => __( 'Latest Episode', 'podlove' )
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
		$items = \Podlove\Modules\Networks\Model\PodcastList::all_podcasts_ordered();

		uasort( $items, function ( $a, $b ) {
			return strnatcmp( $a->title, $b->title );
		});

		$this->items = $items;
	}
}
