<?php
namespace Podlove\Settings\Tools;

use \Podlove\Model\UserAgent;

class UserAgentRefresh {

	public function __construct() {
		add_action('wp_ajax_podlove-useragentrefresh', [$this, 'refresh'] );
	}

	public function refresh() {

		$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);

		if (!$offset)
			$offset = 0;

		podlove_refresh_user_agents($offset);

		echo json_encode([
			'offset' => $offset + 500,
			'total'  => UserAgent::count()
		]);
		exit;
	}

}
