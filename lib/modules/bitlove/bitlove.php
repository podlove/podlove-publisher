<?php
namespace Podlove\Modules\Bitlove;
use \Podlove\Model;

class Bitlove extends \Podlove\Modules\Base {

	protected $module_name = 'Bitlove';
	protected $module_description = 'Enable support for <a href="http://bitlove.org/" target="_blank">bitlove.org</a>. Bitlove creates Torrents for all enclosures of an RSS/ATOM feed and seeds them.';

	public function load() {
		add_action( 'wp_footer', array( $this, 'inject_base' ) );
		add_filter( 'the_content', array( $this, 'inject_widget' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'require_jquery' ) );
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

		$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
		$media_files = $episode->media_files();
		$downloads = array();

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
	    jQuery("#post-$post->ID [name='podlove_downloads']").append("<option value='" + url + "' data-raw-url='" + url + "'>" + title + "</option>")
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
	}

}