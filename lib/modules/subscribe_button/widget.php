<?php
namespace Podlove\Modules\SubscribeButton;

use \Podlove\Model\Podcast;
use \Podlove\Model\Feed;

class Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'podlove_subscribe_button_widget',
			__('Podcast Subscribe Button', 'podlove-podcasting-plugin-for-wordpress'),
			array( 'description' => __( 'Adds a Podlove Subscribe Button to your Sidebar', 'podlove-podcasting-plugin-for-wordpress' ), )
		);

		add_action( 'admin_enqueue_scripts', function() {
			if (!in_array(get_current_screen()->base, ['widgets', 'customize']))
				return;
	
			wp_enqueue_style('podlove-spectrum', \Podlove\PLUGIN_URL . '/js/admin/spectrum/spectrum.css');
			wp_register_script('podlove-spectrum', \Podlove\PLUGIN_URL . '/js/admin/spectrum/spectrum.js', ['jquery']);
			wp_enqueue_script('podlove-psb-widget', Subscribe_Button::instance()->get_module_url() . '/js/admin.js', ['podlove-spectrum']);
		} );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		if (!empty($instance['title']))
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];

		if ($instance['autowidth']) {
			$instance['width'] = 'auto';
		}

		echo $this->button($instance);

		if (!empty($instance['infotext']))
			echo wpautop($instance['infotext']);

		echo $args['after_widget'];
	}

	public function button($instance) {
		return Subscribe_Button::button($instance);
	}

	public function form( $instance ) {
		$title     = isset( $instance[ 'title' ] )     ? $instance[ 'title' ]     : '';
		$button    = isset( $instance[ 'button' ] )    ? $instance[ 'button' ]    : '';
		$size      = isset( $instance[ 'size' ] )      ? $instance[ 'size' ]      : 'big';
		$style     = isset( $instance[ 'style' ] )     ? $instance[ 'style' ]     : 'filled';
		$format    = isset( $instance[ 'format' ] )    ? $instance[ 'format' ]    : 'cover';
		$autowidth = isset( $instance[ 'autowidth' ] ) ? $instance[ 'autowidth' ] : true;
		$infotext  = isset( $instance[ 'infotext' ] )  ? $instance[ 'infotext' ]  : '';
		$color     = isset( $instance[ 'color' ] )     ? $instance[ 'color' ]     : '#75ad91';

		$subscribebutton = Podcast::get();
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'color' ); ?>"><?php _e( 'Color', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<input type="text" id="<?php echo $this->get_field_id( 'color' ); ?>" name="<?php echo $this->get_field_name( 'color' ); ?>" class="podlove_subscribe_color" value="<?php echo $color ?>" />
		</p>

		<style type="text/css">
		.sp-replacer { display: flex }
		.sp-preview { flex-grow: 10; }
		</style>

		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Size', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>">
			<?php foreach (Subscribe_Button::sizes() as $size_key => $size_name): ?>
				<option value="<?php echo $size_key ?>" <?php selected($size, $size_key) ?>><?php echo $size_name ?></option>
			<?php endforeach ?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'format' ); ?>"><?php _e( 'Format', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'format' ); ?>" name="<?php echo $this->get_field_name( 'format' ); ?>">
			<?php foreach (Subscribe_Button::formats() as $format_key => $format_name): ?>
				<option value="<?php echo $format_key ?>" <?php selected($format, $format_key) ?>><?php echo $format_name ?></option>
			<?php endforeach ?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php _e( 'Style', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>">
			<?php foreach (Subscribe_Button::styles() as $style_key => $style_name): ?>
				<option value="<?php echo $style_key ?>" <?php selected($style, $style_key) ?>><?php echo $style_name ?></option>
			<?php endforeach ?>
			</select>
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'autowidth' ); ?>" name="<?php echo $this->get_field_name( 'autowidth' ); ?>" <?php echo ( $autowidth ? 'checked="checked"' : '' ); ?>/>
			<label for="<?php echo $this->get_field_id( 'autowidth' ); ?>"><?php _e( 'Auto-adjust width', 'podlove-podcasting-plugin-for-wordpress' ); ?></label><br />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'infotext' ); ?>"><?php _e( 'Content', 'podlove-podcasting-plugin-for-wordpress' ); ?></label> 
			<textarea class="widefat" rows="10" id="<?php echo $this->get_field_id( 'infotext' ); ?>" name="<?php echo $this->get_field_name( 'infotext' ); ?>"><?php echo $infotext; ?></textarea>
			<em>This text will be shown below the subscribe button.</em>
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['infotext']  = ( ! empty( $new_instance['infotext'] ) )  ? $new_instance['infotext']                : '';
		$instance['title']     = ( ! empty( $new_instance['title'] ) )     ? strip_tags( $new_instance['title'] )     : '';
		$instance['size']      = ( ! empty( $new_instance['size'] ) )      ? strip_tags( $new_instance['size'] )      : '';
		$instance['format']    = ( ! empty( $new_instance['format'] ) )    ? strip_tags( $new_instance['format'] )    : '';
		$instance['style']     = ( ! empty( $new_instance['style'] ) )     ? strip_tags( $new_instance['style'] )     : '';
		$instance['autowidth'] = ( ! empty( $new_instance['autowidth'] ) ) ? strip_tags( $new_instance['autowidth'] ) : 0;
		$instance['button']    = ( ! empty( $new_instance['button'] ) )    ? strip_tags( $new_instance['button'] )    : '';
		$instance['color']    = ( ! empty( $new_instance['color'] ) )    ? $new_instance['color']    : '';

		return $instance;
	}

}
