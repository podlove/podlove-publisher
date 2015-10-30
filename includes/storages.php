<?php
use \Podlove\Storage;

add_action('init', 'podlove_init_storages');

function podlove_init_storages() {
	// @todo should I register them in a list? probably.
	new Storage\WordpressStorage;
	new Storage\ExternalStorage;
}
