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

		register_setting( Templates::$pagehook, 'podlove_template_assignment' );
	}

	public static function get_action_link( $template, $title, $action = 'edit', $type = 'link' ) {
		return sprintf(
			'<a href="?page=%s&action=%s&template=%s"%s>' . $title . '</a>',
			$_REQUEST['page'],
			$action,
			$template->id,
			$type == 'button' ? ' class="button"' : ''
		);
	}

	public function scripts_and_styles() {

		if ( ! isset( $_REQUEST['page'] ) )
			return;

		if ( $_REQUEST['page'] != 'podlove_templates_settings_handle' )
			return;

		\Podlove\require_code_mirror();
	}

	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['template'] ) )
			return;
			
		$template = \Podlove\Model\Template::find_by_id( $_REQUEST['template'] );
		$template->update_attributes( $_POST['podlove_template'] );
		
		$this->redirect( 'index', $template->id );
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

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;

		if ( $action == 'confirm_delete' && isset( $_REQUEST['template'] ) ) {
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo __( 'Are you sure you want do delete this template?', 'podlove' ) ?>
					</strong>
				</p>
				<p>
					<?php echo __( 'If you have inserted this templated manually into your posts, it might be a better idea to just empty the template.', 'podlove' ) ?>
				</p>
				<p>
					<?php echo self::get_action_link( \Podlove\Model\Template::find_by_id( (int) $_REQUEST['template'] ), __( 'Delete permanently', 'podlove' ), 'delete', 'button' ) ?>
				</p>
			</div>
			<?php
		}
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

		echo sprintf(
			__( 'Episode Templates are an easy way to keep the same structure in all your episodes. Create one and use the displayed %sShortcode%s as the episode content.', 'podlove' ),
			'<a href="http://docs.podlove.org/publisher/shortcodes/" target="_blank">',
			'</a>'
			)
		;

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

		<h3><?php echo __( 'Insert templates to content automatically', 'podlove' ) ?></h3>
		<form method="post" action="options.php">
			<?php settings_fields( Templates::$pagehook );
			$template_assignment = Model\TemplateAssignment::get_instance();

			$form_attributes = array(
				'context'    => 'podlove_template_assignment',
				'form'       => false
			);

			\Podlove\Form\build_for( $template_assignment, $form_attributes, function ( $form ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
				
				$templates = array( 0 => __( 'Don\'t insert automatically', 'podlove' ) );
				foreach ( Model\Template::all() as $template ) {
					$templates[ $template->id ] = $template->title;
				}

				$wrapper->select( 'top', array(
					'label'   => __( 'Insert at top', 'podlove' ),
					'options' => $templates,
					'please_choose' => false
				) );

				$wrapper->select( 'bottom', array(
					'label'   => __( 'Insert at bottom', 'podlove' ),
					'options' => $templates,
					'please_choose' => false
				) );

			});
		?>
		</form>
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
				'description' => __( 'Description to identify the template in the shortcode: <code>[podlove-template id="<span class=\'template_title_preview\'>' . $form->object->title . '</span>"]</code>', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			$f->text( 'content', array(
				'label'       => __( 'HTML Template', 'podlove' ),
				'description' => __( 'Have a look at the <a href="http://docs.podlove.org/publisher/shortcodes/" target="_blank">Shortcode documentation</a> for all available options.', 'podlove' ),
				'html' => array( 'class' => 'large-text required', 'rows' => 20 ),
				'default' => <<<EOT
[podlove-web-player]
[podlove-episode-downloads]

<span class="podlove-duration">Duration: [podlove-episode field="duration"]</span>

[podlove-podcast-license]
EOT
			) );

		} );
		?>
		<script type="text/javascript">
		var podlove_template_content = document.getElementById("podlove_template_content");
		var podlove_template_editor = CodeMirror.fromTextArea(podlove_template_content, {
			mode: "text/html",
			lineNumbers: true,
			theme: "default",
			indentUnit: 4,
			lineWrapping: true,
			autoCloseTags: true,
			onCursorActivity: function() {
				podlove_template_editor.matchHighlight("CodeMirror-matchhighlight");
			}
		});		

		podlove_template_editor.setSize(null, "350");

		jQuery(function($){
			$("#podlove_template_title").bind("keyup", function(e) {
				$(".template_title_preview").html($(this).val());
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