<?php

class EpisodeFactory
{
    private $defaults;
    private $factory;

    public function __construct(WP_UnitTest_Factory $factory)
    {
        $this->factory = $factory;
        $this->defaults = [
            'enable' => 1,
            'slug' => 'episode'.wp_rand(1, 10000),
        ];
    }

    public function create($args = [])
    {
        if (!isset($args['post_id']) || !$args['post_id']) {
            $post_factory = new WP_UnitTest_Factory_For_Post($this->factory);
            $args['post_id'] = $post_factory->create(['post_type' => 'podcast']);
        } else {
            wp_update_post([
                'ID' => $args['post_id'],
                'post_type' => 'podcast',
            ]);
        }

        $existing = \Podlove\Model\Episode::find_one_by_property('post_id', $args['post_id']);
        if ($existing) {
            return $existing;
        }

        $args = array_merge($this->defaults, $args);

        return \Podlove\Model\Episode::create($args);
    }
}
