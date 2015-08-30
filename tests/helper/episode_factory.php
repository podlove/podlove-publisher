<?php

class EpisodeFactory {

	private $defaults;
	private $factory;

	public function __construct(WP_UnitTest_Factory $factory) {
		$this->factory = $factory;
		$this->defaults = [
			'enable' => 1,
			'slug'   => 'episode' . mt_rand(1,10000)
		];
	}

	public function create($args = []) {
		
		if (!isset($args['post_id']) || !$args['post_id']) {
			$post_factory = new WP_UnitTest_Factory_For_Post($this->factory);
			$args['post_id'] = $post_factory->create(['post_type' => 'podcast']);
		} else {
			// just make sure the connected post has the correct post type
			wp_update_post([
				'ID' => $args['post_id'],
				'post_type' => 'podcast'
			]);
		}

		return \Podlove\Model\Episode::create($args);
	}

}