<?php
/**
 * A helper class to manage tabs in options pages.
 * 
 * Example:
 *   $tabs = new Podlove_Tabs;
 *   $tabs->set_tab( 'edit', __( 'Edit Templates' ) );
 *   $tabs->set_tab( 'add', __( 'Add Templates' ) );
 *   $tabs->set_default( 'edit' );
 *   $tabs->display();
 * 
 * @version    1.0
 * @author     et
 */

if ( ! function_exists( 'add_filter' ) ) {
	echo "Hi there! I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

class Podlove_Tabs
{
	private $tabs;
	private $title;
	private $default;
	private $enforced_tab = NULL;
	
	public function set_tab( $id, $text ) {
		$this->tabs[ $id ] = $text;
	}
	
	public function set_title( $title ) {
		$this->title = $title;
	}
	
	public function set_default( $default ) {
		$this->default = $default;
	}
	
	public function display( ) {
		$current_tab = $this->get_current_tab();
		?>
		<h2 class="nav-tab-wrapper">
			<?php if ( $this->title ): ?>
				<?php echo $this->title; ?>
			<?php endif; ?>
			<?php foreach ( $this->tabs as $id => $name ): ?>
				<a href="<?php echo admin_url( 'admin.php?page=' . $_REQUEST[ 'page' ] . '&tab=' . $id ) ?>" class="nav-tab <?php echo ( $id == $current_tab ) ? 'nav-tab-active' : '' ?>">
					<?php echo $name ?>
				</a>
			<?php endforeach ?>
		</h2>
		<?php
	}
	
	public function get_current_tab() {
		$current_tab = $this->default;
		
		if ( $this->enforced_tab !== NULL ) {
			return $this->enforced_tab;
		}
		
		foreach ( $this->tabs as $id => $name ) {
			if ( isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == $id ) {
				$current_tab = $id;
				break;
			}
		}
		
		return $current_tab;
	}
	
	/**
	 * Override the default tab selection behaviour.
	 */
	public function enforce_tab( $tab_id ) {
		$this->enforced_tab = $tab_id;
	}
}
