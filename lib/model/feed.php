<?php
namespace Podlove\Model;

class Feed extends Base {
	
	/**
	 * Sets default values.
	 * 
	 * @return array
	 */
	public function default_values() {
		return array(
			'discoverable'     => true,
			'language'         => get_bloginfo( 'language' ),
			'enable'           => true,
			'show_description' => true
		);
	}

	/**
	 * Build public url where the feed can be subscribed at.
	 *
	 * @return string
	 */
	public function get_subscribe_url() {
		return sprintf(
			'%s/feed/%s/%s/',
			get_bloginfo( 'url' ),
			$this->show()->slug,
			$this->slug
		);
	}

	/**
	 * Build html link to subscribe.
	 * 
	 * @return string
	 */
	public function get_subscribe_link() {
		$url = $this->get_subscribe_url();
		return sprintf( '<a href="%s">%s</a>', $url, $url );
	}

	/**
	 * Find the related show model.
	 *
	 * @return \Podlove\Model\Show|NULL
	 */
	public function show() {
		return Show::find_by_id( $this->show_id );
	}

	/**
	 * Find the related media location model.
	 * 
	 * @return \Podlove\Model\MediaLocation|NULL
	 */
	public function media_location() {
		return ( $this->media_location_id ) ? MediaLocation::find_by_id( $this->media_location_id ) : NULL;
	}

	/**
	 * For now, we support just atom.
	 */
	public function get_content_type() {
		return "application/atom+xml";
	}

	public function find_by_show_id_and_media_location_id( $show_id, $media_location_id ) {
		$where = sprintf( 'show_id = "%s" AND media_location_id = "%s"', $show_id, $media_location_id );
		return Feed::find_one_by_where( $where );
	}
}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'show_id', 'INT' );
Feed::property( 'media_location_id', 'INT' );
Feed::property( 'itunes_feed_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'language', 'VARCHAR(255)' );
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'enable', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'show_description', 'INT' );
