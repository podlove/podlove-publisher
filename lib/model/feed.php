<?php
namespace Podlove\Model;

class Feed extends Base {

	/**
	 * Build public url where the feed can be subscribed at.
	 *
	 * @return string
	 */
	public function get_subscribe_url() {

		$podcast = \Podlove\Model\Podcast::get_instance();

		$url = sprintf(
			'%s/feed/%s/%s/',
			get_bloginfo( 'url' ),
			$podcast->slug,
			$this->slug
		);

		return apply_filters( 'podlove_subscribe_url', $url );
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
	 * Get title for browser feed discovery.
	 *
	 * This title is used by clients to show the user the subscribe option he
	 * has. Therefore, the most obvious thing to do is to display the show
	 * title and the file extension in paranthesis.
	 *
	 * Fallback to internal feed name.
	 * 
	 * @return string
	 */
	public function title_for_discovery() {

		$podcast = Podcast::get_instance();

		$media_location = $this->media_location();

		if ( ! $media_location )
			return $this->name;

		$media_format   = $media_location->media_format();

		if ( ! $media_format )
			return $this->name;

		$file_extension = $media_format->extension;

		$title = sprintf( '%s (%s)', $podcast->title, $file_extension );
		$title = apply_filters( 'podlove_feed_title_for_discovery', $title, $this->title, $file_extension, $this->id );

		return $title;
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
	 * Find all post_ids associated with this feed.
	 * 
	 * @return array
	 */
	function post_ids() {

		$media_location = $this->media_location();

		if ( ! $media_location )
			return array();

		$media_files = $media_location->media_files();

		if ( ! count( $media_files ) )
			return array();

		// fetch releases
		$episode_ids = array_map( function ( $v ) { return $v->episode_id; }, $media_files );
		$episodes = Episode::find_all_by_where( "id IN (" . implode( ',', $episode_ids ) . ")" );

		return array_map( function ( $v ) { return $v->post_id; }, $episodes );
	}

	public function get_content_type() {

		if ( $this->format === 'rss' )
			return 'application/rss+xml';
		else
			return "application/atom+xml";	

	}

	/**
	 * Rails-ish update_attributes for easy form handling.
	 *
	 * Takes an array of form values and takes care of serializing it.
	 * 
	 * @param  array $attributes
	 * @return bool
	 */
	public function update_attributes( $attributes ) {

		if ( ! isset( $attributes ) || ! is_array( $attributes ) )
			return false;
			
		foreach ( $attributes as $key => $value )
			$this->{$key} = $value;
		
		$checkboxes = array( 'enable', 'discoverable', 'show_description' );
		foreach ( $checkboxes as $checkbox ) {
			if ( isset( $attributes[ $checkbox ] ) && $attributes[ $checkbox ] === 'on' ) {
				$this->$checkbox = 1;
			} else {
				$this->$checkbox = 0;
			}
		}

		return $this->save();
	}

}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'media_location_id', 'INT' );
Feed::property( 'itunes_feed_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'format', 'VARCHAR(255)' ); // atom, rss
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'enable', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'show_description', 'INT' );


// media_location_id
// => für audio
// => für video
// => für text
// => für ... (alle types)

// bitlove support
// auf feed level aktivieren