<?php
namespace Podlove\Form;

class Builder {
	function form_textarea_input() {
		?>
		<textarea name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>"><?php echo $this->object->{$this->field_key}; ?></textarea>
		<?php
	}
	
	function form_text_input() {
		?>
		<input type="text" name="<?php echo $this->field_name; ?>" value="<?php echo $this->object->{$this->field_key}; ?>" id="<?php echo $this->field_id; ?>">
		<?php
	}

	function form_select_input() {
		?>
		<select name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>">
			<option value=""><?php echo \Podlove\t( 'Please choose ...' ); ?></option>
			<?php foreach ( $this->args[ 'options' ] as $key => $value ): ?>
				<option value="<?php echo $key; ?>"<?php if ( $key == $this->object->{$this->field_key} ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	function input( $context, $object, $field_key, $field_values ) {
		$args = ( isset( $field_values[ 'args' ] ) ) ? $field_values[ 'args' ] : array();
		$type = ( isset( $args[ 'type' ] ) ) ? $args[ 'type' ] : 'text';
		$function = 'form_' . $type . '_input';
		
		$this->context      = $context;
		$this->object       = $object;
		$this->field_key    = $field_key;
		$this->field_values = $field_values;
		$this->field_value  = $object->{$field_key};
		$this->field_name   = "{$context}[{$field_key}]";
		$this->field_id     = "{$context}_{$field_key}";
		$this->args         = $args;

		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="<?php echo $this->field_id; ?>"><?php echo $field_values[ 'label' ]; ?></label>
			</th>
			<td>
				<?php call_user_func_array( array( $this, $function ), array() ); ?>
				<br />
				<?php if ( $field_values[ 'description' ] ): ?>
					<span class="description"><?php echo $field_values[ 'description' ]; ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}