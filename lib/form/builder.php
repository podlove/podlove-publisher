<?php
namespace Podlove\Form;

/**
 * @deprecated use the input builder instead
 */
class Builder {
	
	private $context;
	private $field_key;
	private $field_values;
	private $field_name;
	private $field_id;
	private $html;
	
	private function form_textarea_input() {
		?>
		<textarea name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html; ?>><?php echo $this->field_value; ?></textarea>
		<?php
	}
	
	private function form_text_input() {
		?>
		<input type="text" name="<?php echo $this->field_name; ?>" value="<?php echo $this->field_value; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html; ?>>
		<?php
	}

	private function form_select_input() {
		?>
		<select name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html; ?>>
			<option value=""><?php echo \Podlove\t( 'Please choose ...' ); ?></option>
			<?php foreach ( $this->field_values[ 'options' ] as $key => $value ): ?>
				<option value="<?php echo $key; ?>"<?php if ( $key == $this->field_value ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
	
	private function form_checkbox_input() {
		$this->field_value = in_array( $this->field_value, array( 1, '1', true, 'true', 'on' ) );
		?>
		<input type="checkbox" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if ( $this->field_value ): ?>checked="checked"<?php endif; ?> <?php echo $this->html; ?>>
		<?php
	}
	
	private function form_multiselect_input() {
		if ( ! isset( $this->field_value ) || ! is_array( $this->field_value ) )
			$this->field_value = array();
			
		foreach ( $this->field_values[ 'options' ] as $key => $value ) {
			if ( isset( $this->field_value[ $key ] ) ) {
				$checked = $this->field_value[ $key ];
			} else {
				$checked = $this->field_values[ 'default' ];
			}
			
			$name = $this->field_name . '[' . $key . ']';
			
			// generate an id without braces by turning braces into underscores
			$id = $this->field_id . '_' . $key;
			$id = str_replace( array( '[', ']' ), '_', $id );
			$id = str_replace( '__', '_', $id );
			
			if ( isset( $this->field_values[ 'multiselect_callback' ] ) ) {
				$callback = call_user_func( $this->field_values[ 'multiselect_callback' ], $key );
			} else {
				$callback = '';
			}
			
			?>
			<div>
				<label for="<?php echo $id; ?>">
					<input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php if ( $checked ): ?>checked="checked"<?php endif; ?> <?php echo $callback; ?> <?php echo $this->html; ?>> <?php echo $value; ?>
				</label>
			</div>
			<?php
		}
	}
	
	/**
	 * Generic input generator.
	 * 
	 * @param string $context form field name prefix
	 * @param mixed $value form field value
	 * @param string $field_key form field identifier used in name and id
	 * @param array $field_values
	 * 	- label			form label text
	 *  - description	form element description
	 * 	- type		     input type. supported: text, select, textarea, checkbox, multiselect. default: text
	 * 	- default	     default value
	 * 	- html		     array with additional html attributes. e.g. array( 'class' => 'regular-text' )
	 * 	- options	     array with options for select fields
	 * 	
	 * 	- before_input_callback lambda called at the beginning of the table cell
	 * 	- after_input_callback  lambda called at the end of the table cell, after description
	 * 	- multiselect_callback  lambda to add additional attributes to the multiselect form fields
	 */
	public function input( $context, $value, $field_key, $field_values ) {
		$type     = ( isset( $field_values[ 'type' ] ) )    ? $field_values[ 'type' ]    : 'text';
		$default  = ( isset( $field_values[ 'default' ] ) ) ? $field_values[ 'default' ] : NULL;
		$html     = ( isset( $field_values[ 'html' ] ) )    ? $field_values[ 'html' ]    : NULL;
		$function = 'form_' . $type . '_input';
		
		if ( $value !== NULL ) {
			$this->field_value = $value;
		} else {
			$this->field_value = $default;
		}
		
		if ( is_array( $html ) ) {
			$compiled_html = '';
			foreach ( $html as $key => $value ) {
				$compiled_html .= "$key=\"$value\" ";
			}
			$html = $compiled_html;
		}
		
		$this->context      = $context;
		$this->field_key    = $field_key;
		$this->field_values = $field_values;
		$this->field_name   = "{$context}[{$field_key}]";
		$this->field_id     = "{$context}_{$field_key}";
		$this->html         = $html;

		$this->before_input_callback = isset( $field_values[ 'before_input_callback' ] ) ? $field_values[ 'before_input_callback' ] : NULL;
		$this->after_input_callback  = isset( $field_values[ 'after_input_callback' ] )  ? $field_values[ 'after_input_callback' ]  : NULL;
		?>
		<tr class="row_<?php echo $field_key; ?>">
			<th scope="row" valign="top">
				<label for="<?php echo $this->field_id; ?>"><?php echo $field_values[ 'label' ]; ?></label>
			</th>
			<td>
				<?php if ( $this->before_input_callback ): ?>
					<?php call_user_func( $this->before_input_callback, array( 'value' => $this->field_value ) ); ?>
				<?php endif ?>
				<?php call_user_func_array( array( $this, $function ), array() ); ?>
				<?php if ( $type !== 'checkbox' ): ?>
					<br />
				<?php endif; ?>
				<?php if ( $field_values[ 'description' ] ): ?>
					<span class="description"><?php echo $field_values[ 'description' ]; ?></span>
				<?php endif; ?>
				<?php if ( $this->after_input_callback ): ?>
					<?php call_user_func( $this->after_input_callback, array( 'value' => $this->field_value ) ); ?>
				<?php endif ?>
			</td>
		</tr>
		<?php
	}
}