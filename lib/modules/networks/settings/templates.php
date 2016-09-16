<?php 
namespace Podlove\Modules\Networks\Settings;
use \Podlove\Model\Template;

class Templates {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __( 'Templates', 'podlove-podcasting-plugin-for-wordpress' ),
			/* $menu_title */ __( 'Templates', 'podlove-podcasting-plugin-for-wordpress' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_templates_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'scripts_and_styles' ) );	
	}

	public function scripts_and_styles() {

		if ( ! isset( $_REQUEST['page'] ) )
			return;

		if ( $_REQUEST['page'] != 'podlove_templates_settings_handle' )
			return;

		wp_register_script( 'podlove-ace-js', \Podlove\PLUGIN_URL . '/js/admin/ace/ace.js' );

		wp_register_script( 'podlove-template-js', \Podlove\PLUGIN_URL . '/js/admin/template.js', array( 'jquery', 'podlove-ace-js') );
		wp_enqueue_script( 'podlove-template-js' );
	}

	public function page() {

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;

		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Templates', 'podlove-podcasting-plugin-for-wordpress' ); ?></h2>
			<?php $this->view_template(); ?>
		</div>	
		<?php
	}

	private function view_template() {

		echo __(
			'Use network templates to share common templates in your podcast sites. 
			They are available in all podcast sites.
			If you define a local template for a template ID that also exists network-wide, the local template takes precedence.', 
			'podlove'
		);

		$templates = Template::with_network_scope(function(){
			return Template::all();
		});
		?>

		<div id="template-editor">
			<div class="navigation">
				<ul>
					<?php foreach ( $templates as $template ): ?>
						<li>
							<a href="#" data-id="<?php echo $template->id ?>">
								<span class="filename"><?php echo $template->title; ?></span>&nbsp;
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="add">
					<a href="#">+ add new template</a>
				</div>
			</div>
			<div class="editor">
				<div class="toolbar">
					<div class="actions">
						<a href="#" class="delete">delete</a>
						<a href="#" class="save button button-primary">Save</a>
					</div>
					<div class="title">
						<input type="text">
					</div>
					<div class="clear"></div>
				</div>
				<div class="editor-wrapper">
					<div class="main" id="ace-editor"></div>
					<div id="fullscreen" class="fullscreen-on fullscreen-button"></div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

}
