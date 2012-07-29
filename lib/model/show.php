<?php
namespace Podlove\Model;

class Show extends Base {

	/**
	 * Generate a human readable title.
	 * 
	 * Return name and, if available, the subtitle. Separated by a dash.
	 * 
	 * @return string
	 */
	public function full_title() {
		$t = $this->name;
		
		if ( $this->subtitle )
			$t = $t . ' - ' . $this->subtitle;
		
		return $t;
	}

	/**
	 * Return all media_locations related to this show.
	 *
	 * @return array
	 */
	public function media_locations() {
		return MediaLocation::find_all_by_show_id( $this->id );
	}

	/**
	 * Return all releases related to this show.
	 *
	 * @param bool $only_actiive Optional. Default: true. Fetch only releases
	 *                           which are enabled and have a slug.
	 * @return array
	 */
	public function releases( $only_active = true ) {
		if ( $only_active ) {
			$where    = sprintf( 'show_id = "%s" AND enable AND slug IS NOT NULL', $this->id );
			$releases = Release::find_all_by_where( $where );
		} else {
			$releases = Release::find_all_by_show_id( $this->id );
		}

		return $releases;
	}

	/**
	 * Return all media_locations with an associated format.
	 * 
	 * @return array
	 */
	function valid_media_locations() {

		$where = sprintf( 'show_id = "%s" AND media_format_id > 0', $this->id );

		return MediaLocation::find_all_by_where( $where );
	}

	/**
	 * Return all feeds related to this show.
	 * 
	 * @return array
	 */
	function feeds() {
		return Feed::find_all_by_show_id( $this->id );
	}

	function feed_by_slug( $feed_slug ) {
		$feeds = $this->feeds();

		foreach ( $feeds as $feed ) {
			if ( $feed->slug === $feed_slug )
				return $feed;
		}

		return NULL;
	}

	/**
	 * Get cover image img tag.
	 * 
	 * @param  int $dimensions width and height of image. default: 1400
	 * @return string
	 */
	public function get_cover( $dimensions = '1400' ) {
		if ( ! $this->cover_image )
			return '';

		return '<img src="' . $this->cover_image . '" alt="' . esc_attr( $this->name ) . '" width="' . $dimensions . '" height="' . $dimensions . '" />';
	}
	
}

Show::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Show::property( 'name', 'VARCHAR(255)' );
Show::property( 'subtitle', 'VARCHAR(255)' );
Show::property( 'cover_image', 'VARCHAR(255)' );
Show::property( 'summary', 'TEXT' );
Show::property( 'author_name', 'VARCHAR(255)' );
Show::property( 'owner_name', 'VARCHAR(255)' );
Show::property( 'owner_email', 'VARCHAR(255)' );
Show::property( 'keywords', 'VARCHAR(255)' );
Show::property( 'category_1', 'VARCHAR(255)' );
Show::property( 'category_2', 'VARCHAR(255)' );
Show::property( 'category_3', 'VARCHAR(255)' );
Show::property( 'explicit', 'INT' );
Show::property( 'slug', 'VARCHAR(255)' );
Show::property( 'label', 'VARCHAR(255)' );
Show::property( 'episode_prefix', 'VARCHAR(255)' );
Show::property( 'media_file_base_uri', 'VARCHAR(255)' );
Show::property( 'uri_delimiter', 'VARCHAR(255)' );
Show::property( 'episode_number_length', 'INT' );