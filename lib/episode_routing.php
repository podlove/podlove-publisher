<?php
namespace Podlove;

class Episode_Routing {

	public static function init() {

		/**
		 * Changes the permalink for a custom post type
		 * @uses $wp_post_types
		 * @uses $wp_rewrite
		 */
		add_action( 'init', function () {
			global $wp_post_types, $wp_rewrite;

			foreach ( $wp_post_types as $post_type => $options ) {
				if ( $post_type != "podcast" ) continue;

				// $custom_episode_slug = \Podlove\get_setting('custom_episode_slug') . "/%$post_type%";
				$custom_episode_slug = \Podlove\get_setting('custom_episode_slug');
				
				$wp_rewrite->add_rewrite_tag( "%$post_type%", '([^/]+)', "post_type=$post_type&name=" );
				$wp_rewrite->add_permastruct( $post_type, $custom_episode_slug, false, EP_PERMALINK );
			}
		}, 99 );

		/**
		 * Replace placeholders in permalinks with the correct values
		 */
		add_filter('post_type_link', function ( $post_link, $id ) {
			$post = &get_post($id);
			$unixtime = strtotime( $post->post_date );
			$post_link = str_replace( '%year%', date( 'Y', $unixtime ), $post_link );
			$post_link = str_replace( '%monthnum%', date( 'm', $unixtime ), $post_link );
			$post_link = str_replace( '%day%', date( 'd', $unixtime ), $post_link );
			$post_link = str_replace( '%hour%', date( 'H', $unixtime ), $post_link );
			$post_link = str_replace( '%minute%', date( 'i', $unixtime ), $post_link );
			$post_link = str_replace( '%second%', date( 's', $unixtime ), $post_link );
			$post_link = str_replace( '%post_id%', $post->ID, $post_link );
			$post_link = str_replace( '%postname%', $post->post_name, $post_link );

			// category and author replacement copied from WordPress core
			if ( strpos($post_link, '%category%') !== false ) {

				$cats = get_the_category($post->ID);
				if ( $cats ) {
					usort($cats, '_usort_terms_by_ID'); // order by ID
					$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
					$category_object = get_term( $category_object, 'category' );
					$category = $category_object->slug;
					if ( $parent = $category_object->parent )
						$category = get_category_parents($parent, false, '/', true) . $category;
				}

				if ( empty($category) ) {
					$default_category = get_category( get_option( 'default_category' ) );
					$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
				}

				$post_link = str_replace( '%category%', $category, $post_link );
			}

			if ( strpos($post_link, '%author%') !== false ) {
				$authordata = get_userdata($post->post_author);
				$post_link = str_replace( '%author%', $authordata->user_nicename, $post_link );
			}

			return $post_link;
		}, 10, 2);
	}

}