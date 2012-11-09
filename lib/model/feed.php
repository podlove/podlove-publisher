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
			'%s/feed/%s/',
			get_bloginfo( 'url' ),
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

		$episode_asset = $this->episode_asset();

		if ( ! $episode_asset )
			return $this->name;

		$file_type = $episode_asset->file_type();

		if ( ! $file_type )
			return $this->name;

		$file_extension = $file_type->extension;

		$title = sprintf( __( 'Podcast Feed: %s (%s)', 'podcast' ), $podcast->title, $this->name );
		$title = apply_filters( 'podlove_feed_title_for_discovery', $title, $this->title, $file_extension, $this->id );

		return $title;
	}

	/**
	 * Find the related episode asset model.
	 * 
	 * @return \Podlove\Model\EpisodeAsset|NULL
	 */
	public function episode_asset() {
		return ( $this->episode_asset_id ) ? EpisodeAsset::find_by_id( $this->episode_asset_id ) : NULL;
	}

	/**
	 * Find all post_ids associated with this feed.
	 * 
	 * @return array
	 */
	function post_ids() {

		$episode_asset = $this->episode_asset();

		if ( ! $episode_asset )
			return array();

		$media_files = $episode_asset->media_files();

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

}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'episode_asset_id', 'INT' );
Feed::property( 'itunes_feed_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'format', 'VARCHAR(255)' ); // atom, rss
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'redirect_http_status', 'INT' );
Feed::property( 'enable', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'show_description', 'INT' );


// episode_asset_id
// => für audio
// => für video
// => für text
// => für ... (alle types)

// bitlove support
// auf feed level aktivieren