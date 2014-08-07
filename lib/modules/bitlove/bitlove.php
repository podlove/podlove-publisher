<?php
namespace Podlove\Modules\Bitlove;
use \Podlove\Model;

class Bitlove extends \Podlove\Modules\Base {

	protected $module_name = 'Bitlove';
	protected $module_description = 'Enable support for <a href="http://bitlove.org/" target="_blank">Bitlove</a>. Bitlove creates Torrents for all enclosures of an RSS/ATOM feed and seeds them. Unfortunately, there is only limited support if you use a feedproxy like Feedburner or feedpress.it.';
	protected $module_group = 'external services';

	public function load() {
		add_action( 'wp_footer', array( $this, 'inject_base' ) );
		add_filter( 'the_content', array( $this, 'inject_widget' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'require_jquery' ) );

		add_action( 'podlove_feeds_directories', array( $this, 'enable_bitlove_flag_for_feed' ) );

		add_action( 'podlove_module_was_activated_bitlove', array( $this, 'was_activated' ) );
		add_action( 'podlove_module_was_deactivated_bitlove', array( $this, 'was_deactivated' ) );

		add_action( 'admin_init', array( $this, 'add_feed_model_extension' ) );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		add_action( 'wp_ajax_podlove-fetch-bitlove-url', array( $this, 'fetch_bitlove_url' ) );
	}

	public function fetch_bitlove_url() {
		\Podlove\AJAX\Ajax::respond_with_json( array(
			'bitlove_url'   => self::get_bitlove_feed_url( $_REQUEST['feed_id'] )
		) );
	}

	public function admin_print_styles() {
		wp_register_script(
			'podlove_bitlove_admin_script',
			$this->get_module_url() . '/js/admin.js',
			array( 'jquery', 'jquery-ui-tabs' ),
			\Podlove\get_plugin_header( 'Version' )
		);
		wp_enqueue_script('podlove_bitlove_admin_script');
	}

	public static function get_bitlove_feed_url( $feed_id ) {
		$feed = \Podlove\Model\Feed::find_one_by_id( $feed_id );
		$cache_key = "podlove_bitlove_feed_url_" . $feed_id;

		if ( ( $bitlove_feed_url = get_transient( $cache_key ) ) !== FALSE ) {
			return $bitlove_feed_url;
		} else {
			$subscribe_url = $feed->get_subscribe_url();
			$url = 'http://api.bitlove.org/feed-lookup.json?url=' . $subscribe_url;

			$curl = new \Podlove\Http\Curl();
			$curl->request( $url, array(
				'headers' => array( 'Content-type'  => 'application/json' )
			) );
			$response = $curl->get_response();

			if (!$curl->isSuccessful())
				return array();

			$decoded_answer = get_object_vars(json_decode($response['body']));
			$bitlove_url = $decoded_answer[$subscribe_url][0]; // The response is always the first array element

			set_transient( $cache_key, $bitlove_url, 60*60*24 );
			return $bitlove_url; 
		}
	}

	public function add_feed_model_extension() {
		\Podlove\Model\Feed::property( 'bitlove', 'TINYINT(1)' );
	}

	public function was_activated() {
		global $wpdb;

		if( get_option("_podlove_added_bitlove_to_feed_model") !== 1 ) {
			$wpdb->query( sprintf(
				"ALTER TABLE `%s` ADD COLUMN `bitlove` TINYINT(1) DEFAULT '0'",
				\Podlove\Model\Feed::table_name()
			) );
			update_option( "_podlove_added_bitlove_to_feed_model", 1 );
		}
	}

	public function was_deactivated() {
		$feeds = \Podlove\Model\Feed::all("WHERE `bitlove` = '1'");

		foreach ($feeds as $feed) {
			delete_transient( "podlove_bitlove_feed_url_" . $feed->id );
		}
		
	}

	public function enable_bitlove_flag_for_feed( $wrapper ) {
		$wrapper->checkbox( 'bitlove', array(
			'label'       	=> __( 'Available via Bitlove?', 'podlove' ),
			'description' 	=> __( 'The Bitlove feed will be added to your list of feeds.
									  <p class="podlove-bitlove-status"></p>', 'podlove' ),
			'default'     	=> false,
			'html' 	=> array( 'data-feed-id' => $_GET['feed'] )
		) );
	}

	public function require_jquery() {
		if ( ! is_admin() )
			wp_enqueue_script( 'jquery' );
	}

	public function inject_base() {
		?>
		<script src="http://bitlove.org/widget/base.js" type="text/javascript"></script>
		<?php
	}

	public function inject_widget( $content ) {
		global $post;

		if ( 'podcast' !== get_post_type() )
			return $content;

		if ( is_feed() )
			return $content;

		$cache = \Podlove\Cache\TemplateCache::get_instance();
		$bitlove_html = $cache->cache_for('bitlove_' . get_permalink($post->ID), function() use ($post) {

			$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
			$media_files = $episode->media_files();
			$downloads = array();
			$content = '';

			foreach ( $media_files as $media_file ) {

				$episode_asset = $media_file->episode_asset();

				if ( ! $episode_asset->downloadable )
					continue;

				$file_type = $episode_asset->file_type();
				
				$download_link_url  = $media_file->get_file_url();
				$download_link_name = str_replace( " ", "&nbsp;", $episode_asset->title );

				$downloads[] = array(
					'url'  => $download_link_url,
					'name' => $download_link_name,
					'size' => \Podlove\format_bytes( $media_file->size, 0 ),
					'file' => $media_file
				);
			}

			$content .= '<script type="text/javascript">';
			$content .= '    /* <!-- */';
			foreach ( $downloads as $download ) {
				$content .= <<<EOF
jQuery(function($) {
	torrentByEnclosure("${download['url']}", function(info) {
	  if (info) {
	    var url   = info.sources[0].torrent,
	        title = "Torrent:&nbsp;${download['name']}";
	    // select-style download-widget
	    jQuery("#post-$post->ID [name='download_media_file']").append("<option value='" + url + "' data-raw-url='" + url + "'>" + title + "</option>")
	    // button-stile download-widget
	    jQuery("#post-$post->ID .episode_download_list").append("<li><a href='" + url + "'>" + title + "<span class='size'></span></a></li>")
	  }
	});
});
EOF;
			}
			$content .= '    /* --> */';
			$content .= '</script>';

			return $content;
		});

		return $content . $bitlove_html;
	}

}