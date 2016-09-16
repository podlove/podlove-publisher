<?php
namespace Podlove;

if( ! class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Extend WordPress WP_List_Table by some functionality
 */
class List_Table extends \WP_List_Table {

	/**
	 * Override display of empty list table.
	 *
	 * Display "Add New" link directly in the table.
	 */
	public function no_items() {
		?>
		<div style="margin: 20px 10px 10px 5px">
			<?php $this->no_items_content(); ?>
		</div>
		<?php
	}

	public function no_items_content() {

		$podlove_tab = filter_input(INPUT_GET, 'podlove_tab', FILTER_SANITIZE_STRING);
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);

		$url  = sprintf( '?page=%s&action=%s', $page, 'new' );
		$url .= !empty($podlove_tab) ? "&amp;podlove_tab=" . $podlove_tab : ''
		?>
		<span class="add-new-h2" style="background: transparent">
			<?php _e( 'No items found.' ); ?>
		</span>
		<a href="<?php echo esc_attr($url) ?>" class="add-new-h2">
			<?php _e( 'Add New' ) ?>
		</a>
		<?php
	}

}
