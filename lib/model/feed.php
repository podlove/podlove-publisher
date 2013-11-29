<?php
namespace Podlove\Model;

class Feed extends Base {

	public function save() {
		global $wpdb;
		
		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		$this->slug = \Podlove\slugify( $this->slug );

		if ( ! $this->position ) {
			$pos = $wpdb->get_var( sprintf( 'SELECT MAX(position)+1 FROM %s', self::table_name() ) );
			$this->position = $pos ? $pos : 1;
		}
		
		parent::save();
	}

	/**
	 * Build public url where the feed can be subscribed at.
	 *
	 * @return string
	 */
	public function get_subscribe_url() {

		$podcast = \Podlove\Model\Podcast::get_instance();

		if ( '' != get_option( 'permalink_structure' ) ) {
			$url = sprintf(
				'%s/feed/%s/',
				get_bloginfo( 'url' ),
				\Podlove\slugify( $this->slug )
			);
		} else {
			$url = get_feed_link( $this->slug );
		}


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

		if ( ! $episode_asset = $this->episode_asset() )
			return $this->name;

		if ( ! $file_type = $episode_asset->file_type() )
			return $this->name;

		$file_extension = $file_type->extension;

		$title_template = is_feed() ? '%s (%s)' : __( 'Podcast Feed: %s (%s)', 'podcast' );

		$title = sprintf( $title_template, $podcast->title, $this->name );
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
		$media_files = array_filter( $media_files, function($mf){ return $mf->size > 0; });
		$episode_ids = array_map( function ( $v ) { return $v->episode_id; }, $media_files );
		$episodes = Episode::find_all_by_where( "id IN (" . implode( ',', $episode_ids ) . ")" );

		return array_map( function ( $v ) { return $v->post_id; }, $episodes );
	}

	public function get_content_type() {
		return 'application/rss+xml';
	}

	public function get_self_link() {

		$href = $this->get_subscribe_url();

		$current_page = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
		if ( $current_page > 1 ) {
			$href .= "?paged=" . $current_page;
		}

		return self::get_link_tag( array(
			'prefix' => 'atom',
			'rel'    => 'self',
			'type'   => $this->get_content_type(),
			'title'  => \Podlove\Feeds\prepare_for_feed( $this->title_for_discovery() ),
			'href'   => $href
		) );
	}

	public function get_alternate_links() {

		$html = '';
		foreach ( self::find_all_by_discoverable(1) as $feed ) {
			if ( $feed->id !== $this->id ) {
				$html .= "\n\t" . self::get_link_tag( array(
					'prefix' => 'atom',
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

		if (isset($_GET['redirect'])) {
			$op = parse_url($args['href'], PHP_URL_QUERY) ? '&amp;' : '?';
			$args['href'] .= $op . "redirect=" . $_GET['redirect'];
		}

		return sprintf(
			'<%s%s%s%s href="%s" />',
			$tag_name,
			$args['rel']   ? ' rel="'   . $args['rel']   . '"' : '',
			$args['type']  ? ' type="'  . $args['type']  . '"' : '',
			$args['title'] ? ' title="' . $args['title'] . '"' : '',
			$args['href']
		);
	}

}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'episode_asset_id', 'INT' );
Feed::property( 'itunes_feed_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'position', 'FLOAT' );
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'redirect_http_status', 'INT' );
Feed::property( 'enable', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'embed_content_encoded', 'INT' );
Feed::property( 'protected', 'TINYINT(1)' ); 
Feed::property( 'protection_type', 'TINYINT(1)' ); // Protection type: 0: local, 1: WordPress User
Feed::property( 'protection_user', 'VARCHAR(60)' );
Feed::property( 'protection_password', 'VARCHAR(64)' );