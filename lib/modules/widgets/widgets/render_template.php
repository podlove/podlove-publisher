<?php
namespace Podlove\Modules\Widgets\Widgets;

use \Podlove\Model\Podcast;
use \Podlove\Model\Template;

class RenderTemplate extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_render_template_widget',
			__('Podlove Template', 'podlove'),
			array( 'description' => __( 'Renders a Podlove template.', 'podlove' ) )
		);
	}

	public function widget( $args, $instance ) {
		$podcast = \Podlove\Model\Podcast::get();

		echo $args['before_widget'];

		if ( ! empty($instance['title']) )
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];

		echo do_shortcode( '[podlove-template id="' . $instance[ 'template' ] . '" autop="' . ( $instance[ 'autop' ] ? 'yes' : 'no' ) . '"]' );

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$templates = \Podlove\Model\Template::all();
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		$selected_template = isset( $instance[ 'template' ] ) ? $instance[ 'template' ] : '';
		$autop = isset( $instance[ 'autop' ] ) ? $instance[ 'autop' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />

			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template', 'podlove' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>">
				<?php
					foreach ($templates as $template) {
						?>
						<option value="<?php echo $template->title; ?>" <?php echo ( $selected_template == $template->title ? 'selected=\"selected\"' : '' ); ?>><?php echo $template->title; ?></option>
						<?php
					}
				?>
			</select>

			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id( 'autop' ); ?>" name="<?php echo $this->get_field_name( 'autop' ); ?>" <?php echo ( $autop ? 'checked="checked"' : '' ); ?> />
			<label for="<?php echo $this->get_field_id( 'autop' ); ?>"><?php _e( 'Autowrap blocks of text?', 'podlove' ); ?></label><br />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['template'] = ( ! empty( $new_instance['template'] ) ) ? strip_tags( $new_instance['template'] ) : '';
		$instance['autop'] = ( ! empty( $new_instance['autop'] ) ) ? strip_tags( $new_instance['autop'] ) : '';

		return $instance;
	}
}