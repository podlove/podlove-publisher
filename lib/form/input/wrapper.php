<?php
namespace Podlove\Form\Input;

abstract class Wrapper {

	/**
	 * Form input builder.
	 * @var \Podlove\Form\Input\Builder
	 */
	public $builder;

	public function __construct( $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Decorate input field with html. Then forward call to input builder.
	 * 			
	 * @param  string $name      input type name
	 * @param  array  $arguments optional input arguments
	 * @return void
	 */
	public function __call( $name, $arguments = array() ) {
		$object_key   = $arguments[ 0 ];
		$field_name   = $this->builder->get_field_name( $object_key );
		$field_id     = $this->builder->get_field_id( $object_key );
		$field_values = ( isset( $arguments[ 1 ] ) ) ? $arguments[ 1 ] : array();
		$builder      = $this->builder;
		
		$this->do_template( $object_key, $field_name, $field_id, $field_values, function () use ( $builder, $name, $arguments ) {
			call_user_func_array( array( $builder, $name ), $arguments );
		} );
	}

	public abstract function do_template( $object_key, $field_name, $field_id, $field_values, $block );
}


