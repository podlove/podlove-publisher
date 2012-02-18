<?php
namespace Podlove\Form;

class Builder {
	function form_textarea_input() {
		?>
		<textarea name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if ( $this->class ): ?>class="<?php echo $this->class; ?>"<?php endif; ?> <?php echo $this->html; ?>><?php echo $this->field_value; ?></textarea>
		<?php
	}
	
	function form_text_input() {
		?>
		<input type="text" name="<?php echo $this->field_name; ?>" value="<?php echo $this->field_value; ?>" id="<?php echo $this->field_id; ?>" <?php if ( $this->class ): ?>class="<?php echo $this->class; ?>"<?php endif; ?> <?php echo $this->html; ?>>
		<?php
	}

	function form_select_input() {
		?>
		<select name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if ( $this->class ): ?>class="<?php echo $this->class; ?>"<?php endif; ?> <?php echo $this->html; ?>>
			<option value=""><?php echo \Podlove\t( 'Please choose ...' ); ?></option>
			<?php foreach ( $this->args[ 'options' ] as $key => $value ): ?>
				<option value="<?php echo $key; ?>"<?php if ( $key == $this->field_value ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
	
	function form_checkbox_input() {
		$this->field_value = in_array( $this->field_value, array( 1, '1', true, 'true', 'on' ) );
		?>
		<input type="checkbox" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if ( $this->field_value ): ?>checked="checked"<?php endif; ?> <?php if ( $this->class ): ?>class="<?php echo $this->class; ?>"<?php endif; ?> <?php echo $this->html; ?>>
		<?php
	}
	
	function form_multiselect_input() {
		if ( ! isset( $this->field_value ) || ! is_array( $this->field_value ) )
			$this->field_value = array();
			
		foreach ( $this->args[ 'options' ] as $key => $value ) {
			if ( isset( $this->field_value[ $key ] ) ) {
				$checked = $this->field_value[ $key ];
			} else {
				$checked = $this->args[ 'default' ];
			}
			
			$name = $this->field_name . '[' . $key . ']';
			
			// generate an id without braces by turning braces into underscores
			$id = $this->field_id . '_' . $key;
			$id = str_replace( array( '[', ']' ), '_', $id );
			$id = str_replace( '__', '_', $id );
			
			if ( $this->args[ 'form_field_callback' ] ) {
				$callback = call_user_func( $this->args[ 'form_field_callback' ], $key );
			} else {
				$callback = '';
			}
			
			?>
			<div>
				<label for="<?php echo $id; ?>">
					<input type="checkbox" name="<?php echo $name; ?>]" id="<?php echo $id; ?>" <?php if ( $checked ): ?>checked="checked"<?php endif; ?> <?php echo $callback; ?> <?php if ( $this->class ): ?>class="<?php echo $this->class; ?>"<?php endif; ?> <?php echo $this->html; ?>> <?php echo $value; ?>
				</label>
			</div>
			<?php
		}
	}

	function input( $context, $value, $field_key, $field_values ) {
		$args     = ( isset( $field_values[ 'args' ] ) ) ? $field_values[ 'args' ] : array();
		$type     = ( isset( $args[ 'type' ] ) )         ? $args[ 'type' ]         : 'text';
		$default  = ( isset( $args[ 'default' ] ) )      ? $args[ 'default' ]      : NULL;
		$class    = ( isset( $args[ 'class' ] ) )        ? $args[ 'class' ]        : NULL;
		$html     = ( isset( $args[ 'html' ] ) )         ? $args[ 'html' ]         : NULL;
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
		$this->class        = $class;
		$this->html         = $html;
		$this->args         = $args;
		?>
		<tr class="row_<?php echo $field_key; ?>">
			<th scope="row" valign="top">
				<label for="<?php echo $this->field_id; ?>"><?php echo $field_values[ 'label' ]; ?></label>
			</th>
			<td>
				<?php call_user_func_array( array( $this, $function ), array() ); ?>
				<?php if ( $type !== 'checkbox' ): ?>
					<br />
				<?php endif; ?>
				<?php if ( $field_values[ 'description' ] ): ?>
					<span class="description"><?php echo $field_values[ 'description' ]; ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}