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

}

Episode::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Episode::property( 'post_id', 'INT' );
Episode::property( 'show_id', 'INT' );
Episode::property( 'subtitle', 'VARCHAR(255)' );
Episode::property( 'active', 'INT' ); // publicized or not?
Episode::property( 'enable', 'INT' ); // listed in podcast directories or not?
Episode::property( 'slug', 'VARCHAR(255)' );
Episode::property( 'duration', 'VARCHAR(255)' );
Episode::property( 'cover_art', 'VARCHAR(255)' );
Episode::property( 'chapters', 'TEXT' );
// episode_number
// season_number
// episode_id nach template, virtual attribute