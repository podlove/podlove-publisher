<?php
namespace Podlove;

/**
 * Building the Publisher Tools page
 * 
 * API inspired by WP Settings API
 */

function get_tools_sections() {
	global $podlove_tools_sections;
	return $podlove_tools_sections;
}

function get_tools_fields() {
	global $podlove_tools_fields;
	return $podlove_tools_fields;
}

function add_tools_section($id, $title, $callback = null) {
	global $podlove_tools_sections;

	$podlove_tools_sections[$id] = ['id' => $id, 'title' => $title, 'callback' => $callback];
}

function add_tools_field($id, $title, $callback, $section) {
	global $podlove_tools_fields;

	$podlove_tools_fields[$section][$id] = ['id' => $id, 'title' => $title, 'callback' => $callback];
}
