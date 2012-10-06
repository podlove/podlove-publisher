<?php
namespace Podlove\Modules\OpenGraph;
use \Podlove\Model;

class Open_Graph extends \Podlove\Modules\Base {

		protected $module_name = 'Open Graph Integration';
		protected $module_description = 'Adds Open Graph metadata to episodes. Useful for third party services.';

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

			add_action( 'wp_head', array( $this, 'insert_open_graph_metadata' ) );
		}

		/**
		 * Insert HTML meta tags into site head.
		 *
		 * @todo  caching
		 * @todo  let user choose what's in og:description: subtitle, excerpt, ...
		 * @todo  handle multiple releases per episode
		 */
		public function insert_open_graph_metadata() {

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
			
			?>
			<meta property="og:type" content="website" />
			<meta property="og:site_name" content="<?php echo $episode->full_title(); ?>" />
			<meta property="og:title" content="<?php the_title(); ?>" />
			<?php if ( $cover_art_url ): ?>
				<meta property="og:image" content="<?php echo $cover_art_url; ?>" />
			<?php endif ?>
			<meta property="og:url" content="<?php the_permalink(); ?>" />
			<?php $media_files = $episode->media_files(); ?>
			<?php foreach ( $media_files as $media_file ): ?>
				<meta property="og:audio" content="<?php echo $media_file->get_file_url(); ?>" />
				<meta property="og:audio:type" content="<?php echo $media_file->episode_asset()->file_type()->mime_type ?>" />
			<?php endforeach ?>
			<?php
		}		
}