<?php
namespace Podlove\Form;

class Builder {
	function form_textarea_input( $context, $object, $field_key, $field_value, $args ) {
		?>
		<textarea name="<?php echo $context; ?>[<?php echo $field_key; ?>]" id="<?php echo $context . '_' . $field_key; ?>"><?php echo $object->{$field_key}; ?></textarea>
		<?php
	}
	
	function form_text_input( $context, $object, $field_key, $field_value, $args ) {
		?>
		<input type="text" name="<?php echo $context; ?>[<?php echo $field_key; ?>]" value="<?php echo $object->{$field_key}; ?>" id="<?php echo $context . '_' . $field_key; ?>">
		<?php
	}

	function form_select_input( $context, $object, $field_key, $field_value, $args ) {
		?>
		<select name="<?php echo $context; ?>[<?php echo $field_key; ?>]" id="<?php echo $context . '_' . $field_key; ?>">
			<?php foreach ( $args[ 'options' ] as $key => $value ): ?>
				<option value="<?php echo $value; ?>"<?php if ( $value == $object->{$field_key} ): ?> selected="selected"<?php endif; ?>><?php echo $key; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	function input( $context, $object, $field_key, $field_value ) {
		$args = ( isset( $field_value[ 'args' ] ) ) ? $field_value[ 'args' ] : array();
		$type = ( isset( $args[ 'type' ] ) ) ? $args[ 'type' ] : 'text';
		$function = 'form_' . $type . '_input';
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="<?php echo $context . '_' . $field_key; ?>"><?php echo $field_value[ 'label' ]; ?></label>
			</th>
			<td>
				<?php call_user_func_array( array( $this, $function ), array( $context, $object, $field_key, $field_value, $args ) ); ?>
				<br />
				<?php if ( $field_value[ 'description' ] ): ?>
					<span class="description"><?php echo $field_value[ 'description' ]; ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}