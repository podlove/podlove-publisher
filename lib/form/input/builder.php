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

	public function __construct( $object, $context ) {
		$this->object     = $object;
		$this->context    = $context;
	}

	public function get_field_name( $object_key ) {
		return ( $this->context ) ? "{$this->context}[{$object_key}]" : $object_key;
	}

	public function get_field_id( $object_key ) {
		return ( $this->context ) ? "{$this->context}_{$object_key}" : $object_key;
	}

	public function input( $field_name, $field_type ) {
		// echo $field_name;
	}

	public function string( $object_key ) {
		$field_name = $this->get_field_name( $object_key );
		$field_id   = $this->get_field_id( $object_key );
		?>
		<input type="text" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $this->object->{$object_key}; ?>">
		<?php
	}

}

