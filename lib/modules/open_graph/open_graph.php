<?php
namespace Podlove\Modules\OpenGraph;
use \Podlove\Model;

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

			/** @var \Podlove\Model\Episode $episode */
			$episode = \Podlove\Model\Episode::find_one_by_post_id( $post_id );
			if ( ! $episode )
				return;

			$podcast = Model\Podcast::get_instance();

			// determine image
			$cover_art_url = $episode->get_cover_art();
			if ( ! $cover_art_url )
				$cover_art_url = $podcast->cover_image;

			// determine description
			if ( $episode->summary ) {
				$description = $episode->summary;
			} elseif ( $episode->subtitle ) {
				$description = $episode->subtitle;
			} else {
				$description = get_the_title();
			}
			$description = htmlspecialchars( trim( $description ) );

			// determine featured image (thumbnail)
			$thumbnail = NULL;
			if ( has_post_thumbnail() ) {
				$post_thumbnail_id = get_post_thumbnail_id( $post_id );
				$thumbnailInfo = wp_get_attachment_image_src($post_thumbnail_id);
				if ( is_array($thumbnailInfo )) {
					list($thumbnail, $width, $height) = $thumbnailInfo;
				}
			}

			$og_title = ( $podcast->title ) ? $podcast->title : get_the_title();
			?>
			<meta property="og:type" content="website" />
			<meta property="og:site_name" content="<?php echo $og_title; ?>" />
			<meta property="og:title" content="<?php echo $episode->full_title(); ?>" />
			<?php if ( $cover_art_url ): ?>
				<meta property="og:image" content="<?php echo $cover_art_url; ?>" />
			<?php endif; ?>
			<?php if ( isset( $thumbnail ) ): ?>
				<meta property="og:image" content="<?php echo $thumbnail; ?>" />
			<?php endif; ?>
			<meta property="og:url" content="<?php the_permalink(); ?>" />
			<meta property="og:description" content="<?php echo $description?>" />
			<?php $media_files = $episode->media_files(); ?>
			<?php foreach ( $media_files as $media_file ): ?>
				<?php $mime_type = $media_file->episode_asset()->file_type()->mime_type; ?>
				<?php if ( stripos( $mime_type, 'audio' ) !== false ): ?>
					<meta property="og:audio" content="<?php echo $media_file->get_file_url(); ?>" />
					<meta property="og:audio:type" content="<?php echo $mime_type ?>" />
				<?php endif; ?>
			<?php endforeach ?>
			<?php
		}		
}