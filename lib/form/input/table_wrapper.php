<?php
namespace Podlove\Form\Input;

class TableWrapper extends Wrapper {

	public function do_template( $object_key, $field_name, $field_id, $field_values, $block ) {
		?>
		<tr class="row_<?php echo $field_id; ?>">
			<th scope="row" valign="top">
				<label for="<?php echo $field_id; ?>"><?php echo $field_values[ 'label' ]; ?></label>
			</th>
			<td>
				<?php call_user_func( $block ); ?>
				<!-- <br /> -->
				<?php if ( $field_values[ 'description' ] ): ?>
					<span class="description"><?php echo $field_values[ 'description' ]; ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

}