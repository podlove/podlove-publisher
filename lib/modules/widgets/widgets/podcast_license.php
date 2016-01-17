<?php
namespace Podlove\Modules\Widgets\Widgets;

use \Podlove\Model\Podcast;

class PodcastLicense extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_podcast_license_widget',
			__('Podcast License', 'podlove-podcasting-plugin-for-wordpress'),
			array( 'description' => __( 'Displays the license of your podcast.', 'podlove-podcasting-plugin-for-wordpress' ) )
		);
	}

	public function widget( $args, $instance ) {
		$podcast = \Podlove\Model\Podcast::get();

		echo $args['before_widget'];

		if (!empty($instance['title']))
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];

		echo $podcast->get_license_html();

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}
