<?php
namespace Podlove\Modules\Widgets\Widgets;

use \Podlove\Model\Podcast;
use \Podlove\Model\Episode;

class RecentEpisodes extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_recent_episodes_widget',
			__('Recent Episodes', 'podlove-podcasting-plugin-for-wordpress'),
			array( 'description' => __( 'Shows the recent episodes of your podcast.', 'podlove-podcasting-plugin-for-wordpress' ) )
		);
	}

	public function widget( $args, $instance ) {
		$number_of_episodes = ( is_numeric($instance['number_of_episodes']) ? $instance['number_of_episodes'] : 10 ); // Fallback for old browsers that allow a non-numeric string to be entered in the "number_of_episodes" field
		$episodes = array_slice(
			Episode::find_all_by_time(['post_status' => ['private', 'publish']]), 0, $number_of_episodes
		);

		echo $args['before_widget'];

		if ( ! empty($instance['title']) )
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];

		echo "<ul style='list-style-type: none;'>";
		foreach ($episodes as $episode) {
			$post = get_post($episode->post_id);
			$episode_duration = new \Podlove\Duration( $episode->duration );
			?>
				<li>
					<?php if ($instance[ 'show_image' ]) : ?>
					<img src="<?php echo $episode->cover_art_with_fallback()->setWidth(400)->url(); ?>" alt="<?php echo $post->post_title; ?>" style="width: 20%; vertical-align: top; margin-right: 2%;"/>
					<div style="display: inline-block; width: 75%;">
					<?php endif; ?>
					<p>
						<a href="<?php echo get_permalink($episode->post_id); ?>"><?php echo $post->post_title; ?></a><br />
						<i class="podlove-icon-calendar"></i> <?php echo get_the_date( get_option('date_format'), $episode->post_id ); ?>
						<?php 
						if ($instance[ 'show_duration' ])
							echo "<br /><i class='podlove-icon-time'></i> " . ($episode_duration->get('human-readable'));
						?>
					</p>
					<?php if ($instance[ 'show_image' ]) : ?>
					</div>
					<?php endif; ?>
				</li>
			<?php
		}
		echo "</ul>";

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		$number_of_episodes = isset( $instance[ 'number_of_episodes' ] ) ? $instance[ 'number_of_episodes' ] : '';
		$show_image = isset( $instance[ 'show_image' ] ) ? $instance[ 'show_image' ] : '';
		$show_duration = isset( $instance[ 'show_duration' ] ) ? $instance[ 'show_duration' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number_of_episodes' ); ?>"><?php _e( 'Number of Episodes', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<input class="widefat" type="number" id="<?php echo $this->get_field_id( 'number_of_episodes' ); ?>" name="<?php echo $this->get_field_name( 'number_of_episodes' ); ?>" value="<?php echo $number_of_episodes; ?>" />
		</p>
		<p>		
			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id( 'show_image' ); ?>" name="<?php echo $this->get_field_name( 'show_image' ); ?>" <?php echo ( $show_image ? 'checked="checked"' : '' ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_image' ); ?>"><?php _e( 'Display episode image', 'podlove-podcasting-plugin-for-wordpress' ); ?></label><br />

			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id( 'show_duration' ); ?>" name="<?php echo $this->get_field_name( 'show_duration' ); ?>" <?php echo ( $show_duration ? 'checked="checked"' : '' ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_duration' ); ?>"><?php _e( 'Show duration', 'podlove-podcasting-plugin-for-wordpress' ); ?></label><br />
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['number_of_episodes'] = ( ! empty( $new_instance['number_of_episodes'] ) ) ? strip_tags( $new_instance['number_of_episodes'] ) : '';
		$instance['show_image'] = ( ! empty( $new_instance['show_image'] ) ) ? strip_tags( $new_instance['show_image'] ) : '';
		$instance['show_duration'] = ( ! empty( $new_instance['show_duration'] ) ) ? strip_tags( $new_instance['show_duration'] ) : '';

		return $instance;
	}
}
