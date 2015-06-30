<?php

class VoidTest extends WP_UnitTestCase {
	
	function testPublisherPluginIsActive() {
		$this->assertTrue( is_plugin_active('podlove-podcasting-plugin-for-wordpress/podlove.php') );
	}

}
