<?php

namespace Podlove\Settings;

use Podlove\Cache\TemplateCache;
use Podlove\Model;

class Templates
{
    use \Podlove\HasPageDocumentationTrait;

    public static $pagehook;

    public function __construct($handle)
    {
        self::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Templates', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Templates', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            'podlove_templates_settings_handle',
            // $function
            [$this, 'page']
        );

        $this->init_page_documentation(self::$pagehook);

        add_action('admin_init', [$this, 'scripts_and_styles']);

        register_setting(Templates::$pagehook, 'podlove_template_assignment', function ($args) {
            // when changing the assignment, clear caches
            TemplateCache::get_instance()->purge();

            return $args;
        });
    }

    public function scripts_and_styles()
    {
        if (!isset($_REQUEST['page'])) {
            return;
        }

        if ($_REQUEST['page'] != 'podlove_templates_settings_handle') {
            return;
        }

        wp_register_script('podlove-ace-js', \Podlove\PLUGIN_URL.'/js/admin/ace/ace.js');

        wp_register_script('podlove-template-js', \Podlove\PLUGIN_URL.'/js/admin/template.js', ['jquery', 'podlove-ace-js']);
        wp_enqueue_script('podlove-template-js');
    }

    public function page()
    {
        ?>
		<div class="wrap">
			<h2><?php echo __('Templates', 'podlove-podcasting-plugin-for-wordpress'); ?></h2>
			<?php
$this->view_template(); ?>
		</div>
		<?php
    }

    private function view_template()
    {
        echo sprintf(
            __('Episode Templates are an easy way to keep the same structure in all your episodes.
				You can use %sShortcodes%s as well as %sPublisher Template Tags%s to customize your episodes.<br>
				Please read the %sTemplating Guide%s to get started.
				', 'podlove-podcasting-plugin-for-wordpress'),
            '<a href="http://docs.podlove.org/ref/shortcodes.html" target="_blank">',
            '</a>',
            '<a href="http://docs.podlove.org/reference/template-tags/" target="_blank">',
            '</a>',
            '<a href="http://docs.podlove.org/guides/understanding-templates/" target="_blank">',
            '</a>'
        ); ?>
		<div id="template-editor">
			<div class="navigation">
				<ul>
					<?php foreach (Model\Template::all() as $template) { ?>
						<li>
							<a href="#" data-id="<?php echo $template->id; ?>">
								<span class="filename"><?php echo $template->title; ?></span>&nbsp;
							</a>
						</li>
					<?php } ?>
				</ul>
				<div class="add">
					<a href="#">+ <?php _e('add new template', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
				</div>
			</div>
			<div class="editor">
				<div class="toolbar">
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
			<footer>
			  <div class="actions">
					<a href="#" class="save button button-primary"><?php _e('Save Template', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
			  	<a href="#" class="delete"><?php _e('Delete Template', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
			  </div>
			</footer>
			<div class="clear"></div>
		</div>

		<div class="podlove-template-shortcode" style="margin-top: 8px">
		  <div>
	  		<strong><?php _e('Embed with Shortcode', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
			</div>
		  <div style="margin-top: 4px; display: flex">
				<input id="podlove_template_shortcode_preview" class="regular-text code" value="" style="margin-right: 8px">

				<button class="button clipboard-btn" data-clipboard-target="#podlove_template_shortcode_preview">
					Copy to Clipboard
				</button>
			</div>
		</div>

		<div class="podlove-form-card" style="margin-top: 40px">
		<h3><?php _e('Insert templates to content automatically', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

		<form method="post" action="options.php">
			<?php settings_fields(Templates::$pagehook);
        $template_assignment = Model\TemplateAssignment::get_instance();

        $form_attributes = [
            'context' => 'podlove_template_assignment',
            'form' => false,
        ];

        \Podlove\Form\build_for($template_assignment, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            $templates = [0 => __('Don\'t insert automatically', 'podlove-podcasting-plugin-for-wordpress')];
            foreach (Model\Template::all_globally() as $template) {
                $templates[$template->title] = $template->title;
            }

            $wrapper->select('top', [
                'label' => __('Insert at top', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $templates,
                'please_choose' => false,
            ]);

            $wrapper->select('bottom', [
                'label' => __('Insert at bottom', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $templates,
                'please_choose' => false,
            ]);

            $wrapper->select('head', [
                'label' => __('Insert in document head', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $templates,
                'please_choose' => false,
            ]);

            $wrapper->select('header', [
                'label' => __('Insert before header', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $templates,
                'please_choose' => false,
            ]);

            $wrapper->select('footer', [
                'label' => __('Insert after footer', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $templates,
                'please_choose' => false,
            ]);
        }); ?>
		</form>
		</div>
		<?php
    }
}
