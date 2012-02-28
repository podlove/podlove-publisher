<?php
namespace Podlove\Model;

class Release extends Base {

	public function find_or_create_by_episode_id_and_show_id( $episode_id, $show_id ) {
		$where = sprintf( 'episode_id = "%s" AND show_id = "%s"', $episode_id, $show_id );
		$release = Release::find_one_by_where( $where );

		if ( $release )
			return $release;

		$release = new Release();
		$release->episode_id = $episode_id;
		$release->show_id = $show_id;
		$release->save();

		return $release;
	}
	
}

Release::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Release::property( 'show_id', 'INT' );
Release::property( 'episode_id', 'INT' );
Release::property( 'active', 'INT' );
Release::property( 'block', 'INT' );
Release::property( 'slug', 'VARCHAR(255)' );
Release::property( 'duration', 'VARCHAR(255)' );
Release::property( 'cover_art', 'VARCHAR(255)' );