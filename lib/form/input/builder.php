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

	/**
	 * Generate values required to build input fields.
	 * 
	 * @param  string $object_key name of the model attribute
	 * @param  array  $arguments  input field options
	 * @return void
	 */
	private function build_input_values( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$this->field_name      = $this->get_field_name();
		$this->field_value     = $this->object->{$object_key};
		$this->field_id        = $this->get_field_id();
		$this->html_attributes = $this->get_extra_html_attributes();
	}

	public function string( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<div>
			<input type="text" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo $this->field_value; ?>" <?php echo $this->html_attributes; ?>>
		</div>
		<?php
	}

	public function text( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<div>
			<textarea name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html_attributes; ?>><?php echo $this->field_value; ?></textarea>
		</div>
		<?php
	}

	public function checkbox( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<input type="checkbox" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if ( $this->field_value ): ?>checked="checked"<?php endif; ?> <?php echo $this->html_attributes; ?>>
		<input type="hidden" name="checkboxes[]" value="<?php echo $this->object_key ?>">
		<?php
	}

	public function select( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<select name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html_attributes; ?>>
			<option value=""><?php echo \Podlove\t( 'Please choose ...' ); ?></option>
			<?php foreach ( $this->arguments[ 'options' ] as $key => $value ): ?>
				<option value="<?php echo $key; ?>"<?php if ( $key == $this->field_value ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function radio( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<?php foreach ( $this->arguments[ 'options' ] as $key => $value ): ?>
			<input type="radio" id="<?php echo $this->field_id . '_' . $key; ?>" name="<?php echo $this->field_name; ?>" value="<?php echo $key; ?>"<?php if ( $key == $this->field_value ): ?> checked="checked"<?php endif; ?>>
			<label for="<?php echo $this->field_id . '_' . $key; ?>"><?php echo $value; ?></label>
		<?php endforeach; ?>
		<?php
	}

	public function image( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		
		// determine image dimensions
		$img_html_attributes = '';

		if ( isset( $arguments[ 'image_width' ] ) )
			$img_html_attributes .= ' width="' . $arguments[ 'image_width' ] . '"';

		if ( isset( $arguments[ 'image_height' ] ) )
			$img_html_attributes .= ' height="' . $arguments[ 'image_height' ] . '"';

		?>
		<div>
			<img src="<?php echo $this->field_value; ?>" <?php echo $img_html_attributes ?>>
			<br>
			<input type="text" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo $this->field_value; ?>" <?php echo $this->html_attributes; ?>>
		</div>
		<?php
	}

	/**
	 * Build nested form.
	 * 
	 * @param  object   $object   object that shall be modified via the form
	 * @param  array    $args     list of options, all optional
	 * 		- hidden dictionary with hidden values
	 * @param  function $callback inner form
	 * @return void
	 */
	function fields_for( $object, $args, $callback ) {
		// determine context
		$context = isset( $args[ 'context' ] ) ? $this->context . '[' . $args[ 'context' ] . ']' . "[{$object->id}]" : $this->context; 
		// build input elements
		call_user_func( $callback, new \Podlove\Form\Input\Builder( $object, $context ) );
	}

}

