<?php
namespace Podlove\Modules\Widgets\Widgets;

use \Podlove\Model\Podcast;

class RecentEpisodes extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_recent_episodes_widget',
			'Podlove Recent Episodes',
			array( 'description' => __( 'Shows the recent episodes of your podcast.', 'podlove' ) )
		);
	}

	public function widget( $args, $instance ) {
		$number_of_episodes = ( is_numeric($instance['number_of_episodes']) ? $instance['number_of_episodes'] : 10 ); // Fallback for old browsers that allow a non-numeric string to be entered in the "number_of_episodes" field
		$episodes = \Podlove\Model\Episode::all( "LIMIT " . $number_of_episodes );

		echo $args['before_widget'];

		if ( ! empty($instance['title']) )
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];

		echo "<ul>";
		foreach ($episodes as $episode) {
			$post = get_post($episode->post_id);
			?>
				<li>
					<img src="<?php echo $episode->get_cover_art_with_fallback(); ?>" alt="<?php echo $post->post_title; ?>" style="float: left; height: 3em; margin-right: 0.5em;" />
					<p>
						<a href="<?php echo post_permalink($episode->post_id); ?>"><?php echo $post->post_title; ?></a><br />
						<?php echo get_the_date("Y-m-d h:i:s", $episode->post_id); ?>
					</p>
				</li>
			<?php
		}
		echo "</ul>";

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		$number_of_episodes = isset( $instance[ 'number_of_episodes' ] ) ? $instance[ 'number_of_episodes' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />

			<label for="<?php echo $this->get_field_id( 'number_of_episodes' ); ?>"><?php _e( 'Number of Episodes', 'podlove' ); ?></label> 
			<input class="widefat" type="number" id="<?php echo $this->get_field_id( 'number_of_episodes' ); ?>" name="<?php echo $this->get_field_name( 'number_of_episodes' ); ?>" value="<?php echo $number_of_episodes; ?>" />
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['number_of_episodes'] = ( ! empty( $new_instance['number_of_episodes'] ) ) ? strip_tags( $new_instance['number_of_episodes'] ) : '';

		return $instance;
	}
}