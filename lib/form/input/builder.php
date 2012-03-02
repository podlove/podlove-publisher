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
		if ( ! is_array( $this->arguments[ 'html' ] ) )
			return '';

		$compiled_html = '';

		foreach ( $this->arguments[ 'html' ] as $key => $value )
			$compiled_html .= "$key=\"$value\" ";

		return $compiled_html;
	}

	public function input( $field_name, $field_type ) {
		// echo $field_name;
	}

	public function string( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$field_name      = $this->get_field_name();
		$field_id        = $this->get_field_id();
		$html_attributes = $this->get_extra_html_attributes();
		?>
		<input type="text" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $this->object->{$object_key}; ?>" <?php echo $html_attributes; ?>>
		<?php
	}

}

