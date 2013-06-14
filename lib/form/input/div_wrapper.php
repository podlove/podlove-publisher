<?php
namespace Podlove\Form\Input;

class DivWrapper extends Wrapper {

	public function do_template( $object_key, $field_name, $field_id, $field_values, $block ) {
		?>
		<div class="row_<?php echo $field_id; ?>">
			<span>
				<?php if ( isset( $field_values['label'] ) && $field_values['label'] ): ?>
					<label for="<?php echo $field_id; ?>"><?php echo $field_values['label']; ?></label>
				<?php endif ?>
			</span>
			<div>
				<?php call_user_func( $block ); ?>
				<?php if ( isset( $field_values['description'] ) &&  $field_values['description'] ): ?>
					<span class="description"><?php echo $field_values['description']; ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function subheader( $title, $description = '' ) {
		?>
		<div>
			<h3><?php echo $title ?></h3>
			<?php if ( $description ): ?>
				<em><?php echo $description ?></em>
			<?php endif; ?>
		</div>
		<?php
	}

}