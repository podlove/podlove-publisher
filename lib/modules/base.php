<?php 
namespace Podlove\Modules;

abstract class Base {

	abstract function load();

	function get_module_name() {
		return $this->module_name;
	}

	function get_module_description() {
		return $this->module_description;
	}

}