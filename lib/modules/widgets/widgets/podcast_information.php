<?php
namespace Podlove\Modules\Widgets\Widgets;

use \Podlove\Model\Podcast;

class PodcastInformation extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_podcast_widget',
			__('Podcast Information', 'podlove'),
			array( 'description' => __( 'Displays basic information about your Podcast.', 'podlove' ) )
		);
	}

	public function widget( $args, $instance ) {
		$podcast = \Podlove\Model\Podcast::get();

		echo $args['before_widget'];

		echo $args['before_title'] . apply_filters( 'widget_title', ( empty($instance['title']) ) ? $podcast->title : $instance['title'] ) . $args['after_title'];

		if ( $instance['show_image'] )
			echo $podcast->cover_art()->setWidth(400)->image(["alt" => $podcast->title]);

		if ( $instance['show_subtitle'] )
			echo '<p><strong>' . $podcast->subtitle . '</strong></p>';

		if ( $instance['show_summary'] )
			echo wpautop($podcast->summary);

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$podcast = \Podlove\Model\Podcast::get();

		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		$show_image = isset( $instance[ 'show_image' ] ) ? $instance[ 'show_image' ] : '';
		$show_subtitle = isset( $instance[ 'show_subtitle' ] ) ? $instance[ 'show_subtitle' ] : '';
		$show_summary = isset( $instance[ 'show_summary' ] ) ? $instance[ 'show_summary' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" placeholder="<?php echo $podcast->title; ?>" />
		</p>
		<p>
			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id( 'show_image' ); ?>" name="<?php echo $this->get_field_name( 'show_image' ); ?>" <?php echo ( $show_image ? 'checked="checked"' : '' ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_image' ); ?>"><?php _e( 'Display image', 'podlove' ); ?></label><br />

			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id( 'show_subtitle' ); ?>" name="<?php echo $this->get_field_name( 'show_subtitle' ); ?>" <?php echo ( $show_subtitle ? 'checked="checked"' : '' ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_subtitle' ); ?>"><?php _e( 'Display subtitle', 'podlove' ); ?></label><br />

			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id( 'show_summary' ); ?>" name="<?php echo $this->get_field_name( 'show_summary' ); ?>" <?php echo ( $show_summary ? 'checked="checked"' : '' ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_summary' ); ?>"><?php _e( 'Display summary', 'podlove' ); ?></label><br />
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['show_image'] = ( ! empty( $new_instance['show_image'] ) ) ? strip_tags( $new_instance['show_image'] ) : '';
		$instance['show_subtitle'] = ( ! empty( $new_instance['show_subtitle'] ) ) ? strip_tags( $new_instance['show_subtitle'] ) : '';
		$instance['show_summary'] = ( ! empty( $new_instance['show_summary'] ) ) ? strip_tags( $new_instance['show_summary'] ) : '';

		return $instance;
	}
}