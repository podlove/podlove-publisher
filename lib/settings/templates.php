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
		add_action( 'admin_init', array( $this, 'scripts_and_styles' ) );	

		register_setting( Templates::$pagehook, 'podlove_template_assignment' );
	}

	public function scripts_and_styles() {

		if ( ! isset( $_REQUEST['page'] ) )
			return;

		if ( $_REQUEST['page'] != 'podlove_templates_settings_handle' )
			return;

		wp_register_script( 'podlove-ace-js', \Podlove\PLUGIN_URL . '/js/admin/ace/ace.js' );
		wp_enqueue_script( 'podlove-ace-js' );
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Templates', 'podlove' ); ?></h2>
			<?php
			$this->view_template();
			?>
		</div>	
		<?php
	}

	private function view_template() {

		echo sprintf(
			__( 'Episode Templates are an easy way to keep the same structure in all your episodes. Create one and use the displayed %sShortcode%s as the episode content.', 'podlove' ),
			'<a href="http://docs.podlove.org/ref/shortcodes.html" target="_blank">',
			'</a>'
			)
		;

		?>
		<div id="template-editor">
			<div class="navigation">
				<ul>
					<?php foreach ( Model\Template::all() as $template ): ?>
						<li>
							<a href="#" data-id="<?php echo $template->id ?>">
								<?php echo $template->title; ?>&nbsp;
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
				<div class="main" id="ace-editor"></div>
			</div>
			<div class="clear"></div>
		</div>

		<script type="text/javascript">
		(function($) {

			$(document).ready(function() {

				var $editor     = $("#template-editor");
				var $title      = $(".editor .title input", $editor);
				var $toolbar    = $(".toolbar", $editor);
				var $navigation = $(".navigation", $editor);

				// local cache
				var templates   = [];

				var editor = ace.edit("ace-editor");

				var activateTemplate = function(title, content) {
					$title.val(title);
					editor.getSession().setValue(content);
				};

				editor.setTheme("ace/theme/github");
				editor.getSession().setMode("ace/mode/twig");
				editor.getSession().setUseWrapMode(true);

				$navigation.on("click", "a", function(e) {
					var $this = $(this);
					var template_id = $this.data('id');

					$this.closest("li")
						.addClass("active")
						.siblings().removeClass("active")
					;

					if (templates[template_id]) {
						activateTemplate(templates[template_id].title, templates[template_id].content);
					} else {
						$.getJSON(ajaxurl, {
							id: template_id,
							action: 'podlove-template-get'
						}, function(data) {
							templates[template_id] = {
								title: data.title,
								content: data.content
							};

							activateTemplate(data.title, data.content);
						});
					}

					$this.blur(); // removes link outline
					e.preventDefault();
				});

				$toolbar.on("click", "a.save", function(e) {
					var template_id = $("li.active a", $navigation).data("id");
					var template_title = $title.val();
					var template_content = editor.getSession().getValue();

					$.getJSON(ajaxurl, {
						id: template_id,
						title: template_title,
						content: template_content,
						action: 'podlove-template-update'
					}, function(data) {
						if (!data.success) {
							console.log("Error: Could not save template.");
						}
					});

					e.preventDefault();
				});

				$title.keyup(function(e) {
					var $active_item = $("li.active a", $navigation);
					var template_id  = $active_item.data("id");
					var new_title    = $(this).val();

					// update cache
					templates[template_id].title = new_title;

					// update navigation element
					$active_item.html(new_title);
				});

				editor.on("input", function () {
					var $active_item = $("li.active a", $navigation);
					var template_id  = $active_item.data("id");
					var new_content = editor.getSession().getValue();

					// update cache
					templates[template_id].content = new_content;
				});

				// select first template on page load
				$("li:first a", $navigation).click();

				$(".add a", $navigation).click(function(e) {

					$.getJSON(ajaxurl, {
						action: 'podlove-template-create'
					}, function(data) {
						$("ul", $navigation)
							.append("<li><a href=\"#\" data-id=\"" + data.id + "\">new template</a></li>");
						$("ul li:last a", $navigation).click();
						$title.focus();
					});

					e.preventDefault();
				});

				$(".delete", $toolbar).click(function(e) {
					var template_id = $("li.active a", $navigation).data('id');

					if (window.confirm("Delete template?")) {
						$.getJSON(ajaxurl, {
							id: template_id,
							action: 'podlove-template-delete'
						}, function(data) {
							if (data.success) {
								$("li a[data-id=" + template_id + "]", $navigation)
									.closest("li")
									.remove();
								$("li:first a", $navigation).click();
							} else {
								console.log("Error: Could not delete template.");
							}
						});
					}

					e.preventDefault();
				});

			});

		}(jQuery));
		</script>

		<?php
		/*
		$table = new \Podlove\Template_List_Table();
		$table->prepare_items();
		$table->display();
		*/
		?>
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

}