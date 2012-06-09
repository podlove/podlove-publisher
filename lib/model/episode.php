<?php
namespace Podlove\Model;

/**
 * We could use simple post_meta instead of a table here
 */
class Episode extends Base {

	public function find_or_create_by_post_id( $post_id ) {
		$episode = Episode::find_one_by_property( 'post_id', $post_id );

		if ( $episode )
			return $episode;

		$episode = new Episode();
		$episode->post_id = $post_id;
		$episode->save();

		return $episode;
	}
	
	/**
	 * Find the related release model.
	 * 
	 * @return \Podlove\Model\Release|NULL
	 */
	public function release() {
		return Release::find_one_by_episode_id( $this->id );
	}

}

Episode::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Episode::property( 'post_id', 'INT' );
Episode::property( 'subtitle', 'VARCHAR(255)' );