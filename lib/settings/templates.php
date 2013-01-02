<?php 
namespace Podlove\Settings;
use \Podlove\Model;

class Templates {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __( 'Templates', 'podlove' ),
			/* $menu_title */ __( 'Templates', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_templates_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'admin_init', array( $this, 'scripts_and_styles' ) );
	}

	public function scripts_and_styles() {

		if ( ! isset( $_REQUEST['page'] ) )
			return;

		if ( $_REQUEST['page'] != 'podlove_templates_settings_handle' )
			return;

		$codemirror_path = \Podlove\PLUGIN_URL . '/js/admin/codemirror/';

		wp_register_script( 'podlove-codemirror-mode-css-js', $codemirror_path . 'modes/css/css.js', array( 'podlove-codemirror-js' ) );
		wp_register_script( 'podlove-codemirror-mode-javascript-js', $codemirror_path . 'modes/javascript/javascript.js', array( 'podlove-codemirror-js' ) );
		wp_register_script( 'podlove-codemirror-mode-xml-js', $codemirror_path . 'modes/xml/xml.js', array( 'podlove-codemirror-js' ) );
		wp_register_script( 'podlove-codemirror-mode-htmlmixed-js', $codemirror_path . 'modes/htmlmixed/htmlmixed.js', array(
			'podlove-codemirror-mode-css-js',
			'podlove-codemirror-mode-javascript-js',
			'podlove-codemirror-mode-xml-js'
		) );

		wp_register_script(
			'podlove-codemirror-util-hint-js',
			$codemirror_path . 'util/simple-hint.js'
		);

		wp_register_script(
			'podlove-codemirror-util-cursor-js',
			$codemirror_path . 'util/searchcursor.js'
		);

		wp_register_script(
			'podlove-codemirror-util-match-js',
			$codemirror_path . 'util/match-highlighter.js',
			array( 'podlove-codemirror-util-cursor-js' )
		);

		wp_register_script(
			'podlove-codemirror-util-close-js',
			$codemirror_path . 'util/closetag.js'
		);

		wp_register_script(
			'podlove-codemirror-js',
			$codemirror_path . 'codemirror.js'
		);

		wp_enqueue_script( 'podlove-codemirror-js' );
		wp_enqueue_script( 'podlove-codemirror-mode-htmlmixed-js' );
		wp_enqueue_script( 'podlove-codemirror-util-close-js' );
		wp_enqueue_script( 'podlove-codemirror-util-match-js' );
		wp_enqueue_script( 'podlove-codemirror-util-hint-js' );

	    wp_register_style(
	    	'podlove-codemirror-css',
			\Podlove\PLUGIN_URL . '/css/codemirror.css'
	    );

	    wp_register_style(
	    	'podlove-codemirror-hint-css',
			$codemirror_path . 'util/simple-hint.css'
	    );

	    wp_enqueue_style( 'podlove-codemirror-css' );
	    wp_enqueue_style( 'podlove-codemirror-hint-css' );
	}

	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['template'] ) )
			return;
			
		$template = \Podlove\Model\Template::find_by_id( $_REQUEST['template'] );
		$template->update_attributes( $_POST['podlove_template'] );
		
		$this->redirect( 'edit', $template->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$template = new \Podlove\Model\Template;
		$template->update_attributes( $_POST['podlove_template'] );

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['template'] ) )
			return;

		\Podlove\Model\Template::find_by_id( $_REQUEST['template'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $template_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $template_id ) ? '&template=' . $template_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}

	public function process_form() {

		if ( ! isset( $_REQUEST['template'] ) )
			return;

		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;

		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Templates', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
			$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;
			switch ( $action ) {
				case 'new':   $this->new_template();  break;
				case 'edit':  $this->edit_template(); break;
				case 'index': $this->view_template(); break;
				default:      $this->view_template(); break;
			}
			?>
		</div>	
		<?php
	}

	private function view_template() {

		echo __( 'Episode Templates are an easy way to keep the same structure in all your episodes. Create one and use the displayed <a href="https://github.com/eteubert/podlove/wiki/Shortcodes" target="_blank">Shortcode</a> as the episode content.', 'podlove' );

		$table = new \Podlove\Template_List_Table();
		$table->prepare_items();
		$table->display();
		?>
		<script type="text/javascript">
		jQuery(function($) {
			var readonly_textareas = $(".highlight-readonly");
			readonly_textareas.each(function() {
				var podlove_code_highlight = CodeMirror.fromTextArea(this, {
					mode: "htmlmixed",
					lineNumbers: false,
					theme: "default",
					indentUnit: 4,
					readOnly: "nocursor",
					lineWrapping: true,
				});
				podlove_code_highlight.setSize(null, "150");
			});
		});
		</script>
		<style type="text/css">
		.column-name { width: 33%; }
		</style>
		<?php
	}

	private function new_template() {
		$template = new \Podlove\Model\Template;
		?>
		<h3><?php echo __( 'Add New Template', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $template, 'create', __( 'Add New Template', 'podlove' ) );
	}

	private function form_template( $template, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_template',
			'hidden'  => array(
				'template' => $template->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $template, $form_args, function ( $form ) {
			$f = new \Podlove\Form\Input\TableWrapper( $form );

			$f->string( 'title', array(
				'label'       => __( 'ID', 'podlove' ),
				'description' => __( 'Description to identify the template in the shortcode: <code>[podlove-template id="<span id=\'template_title_preview\'>' . $form->object->title . '</span>"]</code>', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			$f->text( 'content', array(
				'label'       => __( 'HTML Template', 'podlove' ),
				'description' => __( 'Have a look at the <a href="https://github.com/eteubert/podlove/wiki/Shortcodes" target="_blank">Shortcode documentation</a> for all available options.', 'podlove' ),
				'html' => array( 'class' => 'large-text required', 'rows' => 20 ),
				'default' => <<<EOT
Type "[" to see a list of available shortcodes. HTML is allowed.
Example Template:

<h4 class="podlove-subtitle">[podlove-episode field="subtitle"]</h4>

<span class="podlove-duration">Duration: [podlove-episode field="duration"]</span>

[podlove-episode field="summary"]

[podlove-web-player]
[podlove-episode-downloads]

Published by <a href="[podlove-podcast field="publisher_url"]" target="_blank">[podlove-podcast field="publisher_name"]</a> under <a href="[podlove-podcast field="license_url"]" target="_blank">[podlove-podcast field="license_name"]</a>.
EOT
			) );

			$f->checkbox( 'autoinsert', array(
				'label'       => __( 'Insert Automatically', 'podlove' ),
				'description' => __( 'Use this template when creating new episodes.' ),
				'default'     => false
			) );

		} );
		?>
		<script type="text/javascript">
		var podlove_template_content = document.getElementById("podlove_template_content");
		var podlove_template_editor = CodeMirror.fromTextArea(podlove_template_content, {
			mode: "htmlmixed",
			lineNumbers: true,
			theme: "default",
			indentUnit: 4,
			lineWrapping: true,
			extraKeys: {
				"'>'": function(cm) { cm.closeTag(cm, '>'); },
				"'/'": function(cm) { cm.closeTag(cm, '/'); },
				"'['": function(cm) {
					CodeMirror.simpleHint(cm, function(cm) {
						return {
							list:[
								"[podlove-episode-downloads]",
								"[podlove-web-player]",
								"[podlove-episode field=\"\"]",
								"[podlove-podcast field=\"\"]",
								"[podlove-contributors]"
							],
							from: cm.getCursor()
						};
					});
				}
			},
			onCursorActivity: function() {
				podlove_template_editor.matchHighlight("CodeMirror-matchhighlight");
			}
		});		

		podlove_template_editor.setSize(null, "350");

		jQuery(function($){
			$("#podlove_template_title").bind("keyup", function(e) {
				$("#template_title_preview").html($(this).val());
			});
		});
		</script>
		<style type="text/css">
		span.CodeMirror-matchhighlight {
			background: #e9e9e9;
		}
		.CodeMirror-focused span.CodeMirror-matchhighlight {
			background: #e7e4ff; !important
		}
		.CodeMirror-scroll {
			border: 1px solid #CCC;
		}
		</style>
		<?php
	}
	
	private function edit_template() {
		$template = \Podlove\Model\Template::find_by_id( $_REQUEST['template'] );
		echo '<h3>' . sprintf( __( 'Edit Template: %s', 'podlove' ), $template->title ) . '</h3>';
		$this->form_template( $template, 'save' );
	}

}