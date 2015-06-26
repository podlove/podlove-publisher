<?php
namespace Podlove\Form\Input;

class TableWrapper extends Wrapper {

	public function do_template( $object_key, $field_name, $field_id, $field_values, $block ) {
		$skiplabel = isset($field_values['nolabel']) && $field_values['nolabel'];
		?>
		<tr class="row_<?php echo $field_id; ?>">
			<?php if ( !$skiplabel ): ?>
				<th scope="row" valign="top">
					<?php if ( isset( $field_values['label'] ) && $field_values['label'] ): ?>
						<label for="<?php echo $field_id; ?>"><?php echo $field_values['label']; ?></label>
					<?php endif ?>
				</th>
			<?php endif; ?>
			<td <?php echo $skiplabel ? 'colspan="2"' : '' ?>>
				<?php call_user_func( $block ); ?>
				<!-- <br /> -->
				<?php if ( isset( $field_values['description'] ) &&  $field_values['description'] ): ?>
					<span class="description"><?php echo $field_values['description']; ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public function subheader( $title, $description = '' ) {
		?>
		<tr>
			<th scope="row" valign="top" colspan="2">
				<h3 style="margin-bottom: 0"><?php echo $title ?></h3>
			</th>
		</tr>
		<?php if ( $description ): ?>
			<tr>
				<td colspan="2">
					<?php echo $description ?>
				</td>
			</tr>
		<?php endif; ?>
		<?php
	}

}