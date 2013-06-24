<?php
namespace Podlove\Modules\Pubsubhubbub;
use \Podlove\Model;

class Pubsubhubbub extends \Podlove\Modules\Base {

		protected $module_name = 'PubSubHubbub Support';
		protected $module_description = 'Adds PubSubHubbub discovery to your feeds. Ping services on feed updates.';
		protected $module_group = 'web publishing';

		public function load() {

			add_action( 'init', array( $this, 'register_hooks' ) );

			$this->register_option( 'hub_url', 'string', array(
				'label'       => __( 'Hub URL', 'podlove' ),
				'description' => __( 'Use hub URL for all feeds.', 'podlove' ),
				'html'        => array(
					'class' => 'regular-text',
					'placeholder' => 'http://<your-hub-name>.superfeedr.com/'
				)
			) );
		}

		/**
		 * Register hooks on episode pages only.
		 */
		public function register_hooks() {

			$hub_url = $this->get_module_option( 'hub_url' );

			if ( ! $hub_url )
				return;

			add_action( 'podlove_rss2_head', function( $feed ) use ( $hub_url ) {
				echo "\t" . sprintf( '<atom:link rel="hub" href="%s" />', $hub_url );
			} );

			add_action( 'save_post', array( $this, 'announce_feed_changes' ), 10, 2 );
		}

		/**
		 * Ping hub for every feed.
		 *
		 * @todo do it in a wp cron for more faster UX
		 * @todo subscribe url or redirect=no url?
		 */
		public function announce_feed_changes( $post_ID, $post ) {

			if ( get_post_type( $post ) !== 'podcast' )
				return;

			foreach ( Model\Feed::all() as $feed ) {
				$this->send_ping( $feed->get_subscribe_url() );
			}
		}

		public function send_ping( $ping_url ) {

			$hub_url = $this->get_module_option( 'hub_url' );

			if ( ! $hub_url )
				return;

			$curl = new \Podlove\Http\Curl();
			$curl->request( $hub_url, array(
				'method' => 'POST',
				'body'   => array(
					'hub.mode' => 'publish',
					'hub.url'  => $ping_url
				),
			) );
		}
}