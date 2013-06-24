<?php
namespace Podlove\Modules\TwitterSummaryCard;
use \Podlove\Model;

class Twitter_Summary_Card extends \Podlove\Modules\Base {

		protected $module_name = 'Twitter Card Integration';
		protected $module_description = 'Adds Twitter summary card metadata to episodes. <a href="https://dev.twitter.com/form/participate-twitter-cards" target="_blank">Right now, you need to apply here to make it work.</a>';
		protected $module_group = 'web publishing';

		public function load() {
			add_action( 'wp', array( $this, 'register_hooks' ) );

			$this->register_option( 'site', 'string', array(
				'label'       => __( 'Twitter Site', 'podlove' ),
				'description' => __( '@username for the website used in the card footer', 'podlove' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

			$this->register_option( 'creator', 'string', array(
				'label'       => __( 'Twitter Creator', 'podlove' ),
				'description' => __( '@username for the content creator / author', 'podlove' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

		}

		/**
		 * Register hooks on episode pages only.
		 */
		public function register_hooks() {
			
			if ( ! is_single() )
				return;

			if ( 'podcast' !== get_post_type() )
				return;

			add_action( 'wp_head', array( $this, 'insert_twitter_card_metadata' ) );
		}

		/**
		 * Insert HTML meta tags into site head.
		 */
		public function insert_twitter_card_metadata() {

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

			// determine description
			if ( $episode->summary ) {
				$description = $episode->summary;
			} elseif ( $episode->subtitle ) {
				$description = $episode->subtitle;
			} else {
				$description = get_the_title();
			}
			$description = htmlspecialchars( trim( $description ) );
			
			?>
			<meta name="twitter:card" content="summary">
			<?php if ( $this->get_module_option( 'site' ) ): ?>
				<meta name="twitter:site" content="<?php echo $this->get_module_option( 'site' ) ?>">
			<?php endif; ?>
			<?php if ( $this->get_module_option( 'creator' ) ): ?>
				<meta name="twitter:creator" content="<?php echo $this->get_module_option( 'creator' ) ?>">
			<?php endif; ?>
			<meta name="twitter:url" content="<?php the_permalink(); ?>">
			<meta name="twitter:title" content="<?php the_title(); ?>">
			<meta name="twitter:description" content="<?php echo $description ?>">
			<?php if ( $cover_art_url ): ?>
				<meta name="twitter:image" content="<?php echo $cover_art_url ?>">
			<?php endif; ?>
			<?php
		}		
}