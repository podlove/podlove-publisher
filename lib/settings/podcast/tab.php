<?php
namespace Podlove\Settings\Podcast;
use \Podlove\Settings\Settings;

/**
 * Represents one Expert Settings Tab
 */
class Tab {

	protected $page_hook = 'podlove_podcast_settings_page';

	/**
	 * Tab title
	 * @var string
	 */
	private $title;

	/**
	 * Tab slug used in URLs
	 * @var string
	 */
	private $slug;

	/**
	 * If this is true, use it if no tab is selected.
	 * @var bool
	 */
	private $is_default;

	public function __construct( $title, $is_default = false ) {
		$this->set_title( $title );
		$this->is_default = $is_default;
	}

	public function is_active() {
		$is_current_tab = isset( $_REQUEST['podlove_tab'] ) && $this->get_slug() == $_REQUEST['podlove_tab'];
		return $is_current_tab || ! isset( $_REQUEST['podlove_tab'] ) && $this->is_default;
	}

	public function get_title() {
		return $this->title;
	}

	public function set_title( $title ) {
		$this->title = $title;
		$this->slug = strtolower( \Podlove\slugify( $title ) );
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_url() {
		return sprintf( "?page=%s&podlove_tab=%s", filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING), $this->get_slug() );
	}

	public function page() {
		do_action( $this->page_hook );
	}

	public function init() {
		throw Exception( "You need to subclass Tab and implement Tab::init" );
	}
}
