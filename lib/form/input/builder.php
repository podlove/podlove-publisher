<?php
namespace Podlove\Form\Input;

class Builder {

	/**
	 * Model record.
	 * @var object
	 */
	public $object;

	/**
	 * Form field name prefix.
	 * @var string
	 */
	public $context;

	public $object_key;
	public $arguments;

	public function __construct( $object, $context ) {
		$this->object     = $object;
		$this->context    = $context;
	}

	public function get_field_name() {
		return ( $this->context ) ? "{$this->context}[{$this->object_key}]" : $this->object_key;
	}

	public function get_field_id() {
		return ( $this->context ) ? "{$this->context}_{$this->object_key}" : $this->object_key;
	}

	public function get_extra_html_attributes() {
		if ( ! isset( $this->arguments[ 'html' ] ) || ! is_array( $this->arguments[ 'html' ] ) )
			return '';

		$compiled_html = '';

		foreach ( $this->arguments[ 'html' ] as $key => $value )
			$compiled_html .= "$key=\"$value\" ";

		return $compiled_html;
	}

	public function string( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$field_name      = $this->get_field_name();
		$field_value     = $this->object->{$object_key};
		$field_id        = $this->get_field_id();
		$html_attributes = $this->get_extra_html_attributes();
		?>
		<div>
			<input type="text" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $field_value; ?>" <?php echo $html_attributes; ?>>
		</div>
		<?php
	}

	public function text( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$field_name      = $this->get_field_name();
		$field_value     = $this->object->{$object_key};
		$field_id        = $this->get_field_id();
		$html_attributes = $this->get_extra_html_attributes();
		?>
		<div>
			<textarea name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" <?php echo $html_attributes; ?>><?php echo $field_value; ?></textarea>
		</div>
		<?php
	}

	public function checkbox( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$field_name      = $this->get_field_name();
		$field_value     = $this->object->{$object_key};
		$field_id        = $this->get_field_id();
		$html_attributes = $this->get_extra_html_attributes();
		?>
		<input type="checkbox" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" <?php if ( $field_value ): ?>checked="checked"<?php endif; ?> <?php echo $html_attributes; ?>>
		<input type="hidden" name="checkboxes[]" value="<?php echo $this->object_key ?>">
		<?php
	}

	public function select( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$field_name      = $this->get_field_name();
		$field_value     = $this->object->{$object_key};
		$field_id        = $this->get_field_id();
		$html_attributes = $this->get_extra_html_attributes();
		?>
		<select name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" <?php echo $html_attributes; ?>>
			<option value=""><?php echo \Podlove\t( 'Please choose ...' ); ?></option>
			<?php foreach ( $this->arguments[ 'options' ] as $key => $value ): ?>
				<option value="<?php echo $key; ?>"<?php if ( $key == $field_value ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function image( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$field_name      = $this->get_field_name();
		$field_value     = $this->object->{$object_key};
		$field_id        = $this->get_field_id();
		$html_attributes = $this->get_extra_html_attributes();

		// determine image dimensions
		$img_html_attributes = '';

		if ( isset( $arguments[ 'image_width' ] ) )
			$img_html_attributes .= ' width="' . $arguments[ 'image_width' ] . '"';

		if ( isset( $arguments[ 'image_height' ] ) )
			$img_html_attributes .= ' height="' . $arguments[ 'image_height' ] . '"';

		?>
		<div>
			<img src="<?php echo $this->object->{$object_key} ?>" <?php echo $img_html_attributes ?>>
			<br>
			<input type="text" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $field_value; ?>" <?php echo $html_attributes; ?>>
		</div>
		<?php
	}

}

