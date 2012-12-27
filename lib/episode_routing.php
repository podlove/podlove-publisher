<?php
namespace Podlove;

/**
 * Custom routing logic for episode links.
 * Default CPT permalinks structure: /podcast/<slug>
 * Structure with this class:        /<slug>
 */
class Episode_Routing {

	public static function init() {
		add_filter( 'request', array( '\Podlove\Episode_Routing', 'custom_episodes_request' ), 10, 1 );
		add_filter( 'post_type_link', array( '\Podlove\Episode_Routing', 'remove_prefix_from_episode_permalinks' ), 10, 4 );
	}

	public static function remove_prefix_from_episode_permalinks( $permalink, $post, $leavename, $sample ) {

		if ( $post->post_type !== 'podcast' )
			return $permalink;

		$permalink = str_replace( '/podcast/' , '/', $permalink );

		return $permalink;
	}

	/**
	 * Virtualize default CPT permalink structure.
	 *
	 * WordPress expects the CPT to be at /podcast/<slug> but we want /<slug>.
	 * For WordPress, <slug> could be a post/page. So we ask WordPress what it
	 * thinks it might be. If WordPress says it doesn't know the url, that's
	 * where we start to interfere.
	 *
	 * We fake the URL by showing WordPress what it wants to see: A URL prefixed
	 * with /podcast. We only change the $_SERVER variable, there is no actual
	 * redirect! Then we let WordPress parse the URL again. When WordPress is 
	 * happy because it found the episode correctly, we restore the actual URL.
	 * 
	 * @param  WP $query
	 * @return WP       
	 */
	public static function custom_episodes_request( $query ) {

		$url = self::get_current_url_data();

		// redirect original URL to new URL
		$prefix_position = stripos( $url['url'], '/podcast/' );
		// URL must contain but not end with /podcast/
		if ( $prefix_position && strlen( $url['url'] ) !== $prefix_position + strlen( '/podcast/' ) ) {
			$new_url = str_replace( '/podcast/', '/', $url['url'] );
			wp_redirect( $new_url, 301 );
			exit;
		}

		// compatibility
		if ( isset( $query['name'] ) && ! isset( $query['pagename'] ) ) {
			$query['pagename'] = $query['name'];
		}

		// make sure it's not a known page
		if ( isset( $query['page'] ) && isset( $query['pagename'] ) ) {
			global $wpdb;

			$sql = $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $wpdb->posts . '` WHERE post_type IN ("page","post") AND post_name = "%s"', $query['pagename'] );
			$found_posts = $wpdb->get_var( $sql );
			if ( $found_posts > 0 ) {
				return $query; // found a page, don't interrupt
			} else {
				$query['error'] = '404'; // nope, that's not a known page. proceed.
			}
		}

		// For all unknown pages, prepend podcast prefix to see if this post exists.
		// If WordPress finds a post — hurray! If not, another 404 will be thrown.
		if	( isset( $query['error'] ) && $query['error'] == '404' ) { // page not found
			
			$url_base      = str_replace( $url['scheme'] . '://' . $url['domain'], '', home_url() );
			$permapart     = substr( $_SERVER['REQUEST_URI'], strlen( $url_base ) );
			$simulated_url = trailingslashit( $url_base ) . trailingslashit( 'podcast' ) . trim( $permapart, '/' );

			// show WordPress the URL it needs to see to find the custom post type entry
			$_SERVER['REQUEST_URI'] = $simulated_url;

			remove_filter( 'request', array( '\Podlove\Episode_Routing', 'custom_episodes_request' ), 10, 1 );
			global $wp;
			$wp->parse_request();
			$query = $wp->query_vars;
			add_filter( 'request', array( '\Podlove\Episode_Routing', 'custom_episodes_request' ), 10, 1 );

			// restore original URL
			$_SERVER['REQUEST_URI'] = $url['path'];
		}

		return $query;
	}

	function get_current_url_data() {
	    $url_data = array();
	    $uri = $_SERVER['REQUEST_URI'];

	    // query
	    $x = array_pad( explode( '?', $uri ), 2, false );
	    $url_data['query'] = ( $x[1] )? $x[1] : '' ;

	    // resource
	    $x = array_pad( explode( '/', $x[0] ), 2, false );
	    $x_last = array_pop( $x );
	    if ( strpos( $x_last, '.' ) === false ) {
	        $url_data['resource'] = '';
	        $x[] = $x_last;
	    } else {
	        $url_data['resource'] = $x_last;
	    }

	    // path    
	    $url_data['path'] = implode( '/', $x );
	    if( substr( $url_data['path'], -1 ) !== '/' ) $url_data['path'] .= '/';

	    // domain
	    $url_data['domain'] = $_SERVER['SERVER_NAME'];

	    // scheme
	    $server_prt = explode( '/', $_SERVER['SERVER_PROTOCOL'] );
	    $url_data['scheme'] = strtolower( $server_prt[0] );

	    // url
	    $url_data['url'] = $url_data['scheme'] . '://' . $url_data['domain'] . $uri;

	    return $url_data;
	}
}