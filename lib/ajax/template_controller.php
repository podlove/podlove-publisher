<?php
namespace Podlove\AJAX;

use \Podlove\Model\Template;

class TemplateController {

	public static function init() {

		$actions = array(
			'get', 'update', 'create', 'delete'
		);

		foreach ( $actions as $action )
			add_action( 'wp_ajax_podlove-template-' . $action, array( __CLASS__, str_replace( '-', '_', $action ) ) );
	}

	public static function get() {

		$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

		$template = Template::find_by_id($id);

		Ajax::respond_with_json(array(
			'id'      => $template->id,
			'title'   => $template->title,
			'content' => $template->content,
		));
	}

	public static function update() {

		$id      = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
		$title   = filter_input(INPUT_GET, 'title');
		$content = filter_input(INPUT_GET, 'content');

		if (!$id || !$title)
			Ajax::respond_with_json(array("success" => false));

		$template = Template::find_by_id($id);
		$template->title = $title;
		$template->content = $content;
		$template->save();

		Ajax::respond_with_json(array("success" => true));
	}

	public static function create() {
		
		$template = new Template;
		$template->title = "new template";
		$template->save();

		Ajax::respond_with_json(array("id" => $template->id));
	}

	public static function delete() {
		
		$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
		$template = Template::find_by_id($id);

		if (!$id || !$template) {
			Ajax::respond_with_json(array("success" => false));
		} else {
			$template->delete();
			Ajax::respond_with_json(array("success" => true));
		}
	}
}