<div class="wrap">
	<?php screen_icon( 'podlove-podcast' ); ?>
	<h2><?php echo __( 'Podlove Dashboard', 'podlove' ); ?></h2>

	<div id="poststuff" class="metabox-holder has-right-sidebar">
		
		<!-- sidebar -->
		<div id="side-info-column" class="inner-sidebar">
			<?php do_action( 'podlove_settings_before_sidebar_boxes' ); ?>
			<?php do_meta_boxes( \Podlove\Settings\Dashboard::$pagehook, 'side', NULL ); ?>
			<?php do_action( 'podlove_settings_after_sidebar_boxes' ); ?>
		</div>

		<!-- main -->
		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">
				<?php do_action( 'podlove_settings_before_main_boxes' ); ?>
				<?php do_meta_boxes( \Podlove\Settings\Dashboard::$pagehook, 'normal', NULL ); ?>
				<?php do_meta_boxes( \Podlove\Settings\Dashboard::$pagehook, 'additional', NULL ); ?>
				<?php do_action( 'podlove_settings_after_main_boxes' ); ?>						
			</div>
		</div>

		<br class="clear"/>

	</div>

	<!-- Stuff for opening / closing metaboxes -->
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ){
		// close postboxes that should be closed
		$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
		// postboxes setup
		postboxes.add_postbox_toggles( '<?php echo \Podlove\Settings\Dashboard::$pagehook; ?>' );
	} );
	</script>

	<form style='display: none' method='get' action=''>
		<?php
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		?>
	</form>

</div>