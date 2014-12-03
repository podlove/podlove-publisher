<?php
namespace Podlove\Modules\SubscribeButton;

use \Podlove\Model\Podcast;
use \Podlove\Model\Feed;

class Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_subscribe_button_widget',
			'Podlove Subscribe Button',
			array( 'description' => __( 'Adds a Podlove Subscribe Button to your Sidebar', 'podlove' ), )
		);
	}

	public function widget( $args, $instance ) {
		?>
		<aside id="<?php echo $args['widget_id']; ?>" class="widget">
			
			<?php if ( strlen($instance['title']) ): ?>
				<h3 class="widget-title"><?php echo $instance['title'] ?></h3>
			<?php endif; ?>
			
			<?php echo $this->button($instance['style'], $instance['autowidth']); ?>
			
			<?php if ( strlen($instance['infotext']) ): ?>
				<p><?php echo $instance['infotext'] ?></p>
			<?php endif; ?>

		</aside>
		<?php
	}

	public function button( $style = 'big-logo', $autowidth = true ) {
		return Subscribe_Button::render_button(array(
			'size'  => $style,
			'width' => ($autowidth === true ? 'auto' : '')
		));
	}

	public function form( $instance ) {
		$title     = isset( $instance[ 'title' ] )     ? $instance[ 'title' ]      : '';
		$button    = isset( $instance[ 'button' ] )    ? $instance[ 'button' ]     : '';
		$style     = isset( $instance[ 'style' ] )     ? $instance[ 'style' ]      : '';
		$autowidth = isset( $instance[ 'autowidth' ] ) ? $instance[ 'autowidth' ]  : 0;
		$infotext  = isset( $instance[ 'infotext' ] )  ? $instance[ 'infotext' ]   : '';

		$subscribebutton = Podcast::get_instance();
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />

			<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php _e( 'Style', 'podlove' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>">
				<option value="small"    <?php echo ( $style == 'small'    ? 'selected=\"selected\"' : '' ); ?>><?php _e( 'Small', 'podlove' ) ?></option>
				<option value="medium"   <?php echo ( $style == 'medium'   ? 'selected=\"selected\"' : '' ); ?>><?php _e( 'medium', 'podlove' ) ?></option>
				<option value="big"      <?php echo ( $style == 'big'      ? 'selected=\"selected\"' : '' ); ?>><?php _e( 'Big', 'podlove' ) ?></option>
				<option value="big-logo" <?php echo ( $style == 'big-logo' ? 'selected=\"selected\"' : '' ); ?>><?php _e( 'Big with logo', 'podlove' ) ?></option>
			</select>

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'autowidth' ); ?>" name="<?php echo $this->get_field_name( 'autowidth' ); ?>" <?php echo ( $autowidth ? 'checked="checked"' : '' ); ?>/>
			<label for="<?php echo $this->get_field_id( 'autowidth' ); ?>"><?php _e( 'Auto-adjust width?', 'podlove' ); ?></label><br />
			
			<label for="<?php echo $this->get_field_id( 'infotext' ); ?>"><?php _e( 'Description', 'podlove' ); ?></label> 
			<textarea class="widefat" rows="10" id="<?php echo $this->get_field_id( 'infotext' ); ?>" name="<?php echo $this->get_field_name( 'infotext' ); ?>"><?php echo $infotext; ?></textarea>
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['infotext']  = ( ! empty( $new_instance['infotext'] ) )  ? $new_instance['infotext']                : '';
		$instance['title']     = ( ! empty( $new_instance['title'] ) )     ? strip_tags( $new_instance['title'] )     : '';
		$instance['style']     = ( ! empty( $new_instance['style'] ) )     ? strip_tags( $new_instance['style'] )     : '';
		$instance['autowidth'] = ( ! empty( $new_instance['autowidth'] ) ) ? strip_tags( $new_instance['autowidth'] ) : 0;
		$instance['button']    = ( ! empty( $new_instance['button'] ) )    ? strip_tags( $new_instance['button'] )    : '';

		return $instance;
	}

}
