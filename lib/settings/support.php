<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Support {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Support::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Support',
			/* $menu_title */ 'Support',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_Support_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Support', 'podlove' ); ?></h2>

			<p>
				<?php echo __( 'When reporting a bug, please append the following system report to help us trace the root cause:', 'podlove' ) ?>
			</p>

			<p>
				<?php 
				$r = new \Podlove\SystemReport;
				echo $r->render();
				?>
			</p>

			<!--
			- check for caching constants to determine popular caching plugins
			- modules: mod_rewrite
			-->

			<p>
				<a target="_blank" href="https://github.com/podlove/podlove-publisher/issues" class="button button-primary">
					<?php echo __( 'Report a Bug on GitHub', 'podlove' ) ?>
				</a>
			</p>

		</div>	
		<?php
	}

}
