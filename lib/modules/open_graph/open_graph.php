<?php
namespace Podlove\Modules\OpenGraph;

use Podlove\Model;
use Podlove\DomDocumentFragment;

class Open_Graph extends \Podlove\Modules\Base {

		protected $module_name = 'Open Graph Integration';
		protected $module_description = 'Adds Open Graph metadata to episodes. Useful for third party services.';
		protected $module_group = 'web publishing';

		public function load() {
			add_action( 'wp', array( $this, 'register_hooks' ) );
		}

		/**
		 * Register hooks on episode pages only.
		 */
		public function register_hooks() {
			
			if ( ! is_single() )
				return;

			if ( 'podcast' !== get_post_type() )
				return;

			add_filter( 'language_attributes', function ( $output = '' ) {
				return $output . ' prefix="og: http://ogp.me/ns#"';
			} );

			add_action( 'wp_head', array( $this, 'the_open_graph_metadata' ) );
		}

		public function the_open_graph_metadata()
		{
			$cache_key = 'opg' . get_the_ID() . get_permalink();

			$cache = \Podlove\Cache\TemplateCache::get_instance();
			return $cache->cache_for($cache_key, function() {
				return \Podlove\Modules\OpenGraph\Open_Graph::get_open_graph_metadata();
			});
		}

		/**
		 * Insert HTML meta tags into site head.
		 *
		 * @todo  caching
		 * @todo  let user choose what's in og:description: subtitle, excerpt, ...
		 * @todo  handle multiple releases per episode
		 */
		public static function get_open_graph_metadata() {

			$post_id = get_the_ID();
			if ( ! $post_id )
				return;

			$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
			if ( ! $episode )
				return;

			$podcast = Model\Podcast::get_instance();

			// determine image
			$cover_art_url = $episode->get_cover_art();
			if ( ! $cover_art_url )
				$cover_art_url = $podcast->cover_image;

			// determine featured image (thumbnail)
			$thumbnail = NULL;
			if ( has_post_thumbnail() ) {
				$post_thumbnail_id = get_post_thumbnail_id( $post_id );
				$thumbnailInfo = wp_get_attachment_image_src( $post_thumbnail_id );
				if ( is_array( $thumbnailInfo ) )
					list( $thumbnail, $width, $height ) = $thumbnailInfo;
			}

			// define meta tags
			$data = array(
				array(
					'property' => 'og:type',
					'content'  => 'website'
				),
				array(
					'property' => 'og:site_name',
					'content'  => ( $podcast->title ) ? $podcast->title : get_the_title()
				),
				array(
					'property' => 'og:title',
					'content'  => $episode->full_title()
				),
				array(
					'property' => 'og:url',
					'content'  => get_permalink()
				),
				array(
					'property' => 'og:description',
					'content'  => $episode->description()
				)
			);
			
			if ($cover_art_url) {
				$data[] = array(
					'property' => 'og:image',
					'content'  => $cover_art_url
				);
			}

			if (isset($thumbnail)) {
				$data[] = array(
					'property' => 'og:image',
					'content'  => $thumbnail
				);
			}

			foreach ($episode->media_files() as $media_file) {
				$mime_type = $media_file->episode_asset()->file_type()->mime_type;
				if (stripos($mime_type, 'audio') !== false) {
					$data[] = array( 'property' => 'og:audio', 'content' => $media_file->get_file_url() );
					$data[] = array( 'property' => 'og:audio:type', 'content' => $mime_type );
				}
			}

			// print meta tags
			$dom = new DomDocumentFragment;

			foreach ($data as $meta_element) {
				$element = $dom->createElement('meta');
				foreach ($meta_element as $attribute => $value) {
					$element->setAttribute($attribute,$value);
				}
				$dom->appendChild($element);
			}

			return $dom;
		}		
}