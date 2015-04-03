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
		$url  = sprintf( '?page=%s&action=%s', $_REQUEST['page'], 'new' );
		$url .= ( isset( $_REQUEST['podlove_tab'] ) ? "&amp;podlove_tab=".$_REQUEST['podlove_tab'] : '' )
		?>
		<div style="margin: 20px 10px 10px 5px">
			<?php $this->no_items_content(); ?>
		</div>
		<?php
	}

	public function no_items_content() {
		?>
		<span class="add-new-h2" style="background: transparent">
			<?php _e( 'No items found.' ); ?>
		</span>
		<a href="<?php echo $url ?>" class="add-new-h2">
			<?php _e( 'Add New' ) ?>
		</a>
		<?php
	}

}