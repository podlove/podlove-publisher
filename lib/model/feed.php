<?php
namespace Podlove\Model;

class Feed extends Base {
	
	/**
	 * Build public url where the feed can be subscribed at.
	 *
	 * @return string
	 */
	public function subscribe_url() {
		return sprintf(
			'%s/feed/%s/%s/',
			get_bloginfo( 'url' ),
			$this->show()->slug,
			$this->slug
		);
	}

	/**
	 * Find the related show model.
	 *
	 * return \Podlove\Model\Show|NULL
	 */
	public function show() {
		return Show::find_by_id( $this->show_id );
	}
}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'show_id', 'INT' );
Feed::property( 'format_id', 'INT' );
Feed::property( 'itunes_feed_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'language', 'VARCHAR(255)' );
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'block', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'show_description', 'INT' );
