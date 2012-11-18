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

	public function get_self_link() {
		return self::get_link_tag( array(
			'prefix' => ( $this->format === 'rss' ) ? 'atom' : NULL,
			'rel'    => 'self',
			'type'   => $this->get_content_type(),
			'title'  => \Podlove\Feeds\prepare_for_feed( $this->title_for_discovery() ),
			'href'   => $this->get_subscribe_url()
		) );
	}

	public function get_alternate_links() {

		$html = '';
		foreach ( self::find_all_by_discoverable(1) as $feed ) {
			if ( $feed->id !== $this->id ) {
				$html .= "\n\t" . self::get_link_tag( array(
					'prefix' => ( $this->format === 'rss' ) ? 'atom' : NULL,
					'rel'    => 'alternate',
					'type'   => $feed->get_content_type(),
					'title'  => \Podlove\Feeds\prepare_for_feed( $feed->title_for_discovery() ),
					'href'   => $feed->get_subscribe_url()
				) );
			}
		}

		return $html;
	}

	public static function get_link_tag( $args = array() ) {
		
		$defaults = array(
			'prefix' => NULL,
			'rel'    => 'alternate',
			'type'   => 'application/atom+xml',
			'title'  => '',
			'href'   => ''
		);
		$args = wp_parse_args( $args, $defaults );

		$tag_name = $args['prefix'] ? $args['prefix'] . ':link' : 'link';

		return sprintf(
			'<%s%s%s%s href="%s" />',
			$tag_name,
			$args['rel']   ? ' rel="'   . $args['rel']   . '"' : '',
			$args['type']  ? ' type="'  . $args['type']  . '"' : '',
			$args['title'] ? ' title="' . $args['title'] . '"' : '',
			$args['href']
		);
	}

	public function save() {
		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		parent::save();
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


// episode_asset_id
// => für audio
// => für video
// => für text
// => für ... (alle types)

// bitlove support
// auf feed level aktivieren