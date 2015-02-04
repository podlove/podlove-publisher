<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Support {

	use \Podlove\HasPageDocumentationTrait;

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

		$this->init_page_documentation(self::$pagehook);
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Support', 'podlove' ); ?></h2>

			<h3><?php echo __('Get in Touch', 'podlove') ?></h3>

			<p>
				<?php echo __('For quick remarks, feedback and questions, you can reach us here:', 'podlove') ?>
				<ul class="ul-disc">
					<li><strong>Twitter:</strong> <a target="_blank" href="https://twitter.com/podlove_org">@podlove_org</a></li>
					<li><strong>ADN:</strong> <a target="_blank" href="https://alpha.app.net/podlove">@podlove</a></li>
				</ul>
			</p>

			<?php do_action('podlove_support_repair_html'); ?>

			<h3><?php echo __('Bug Reports, Feature Requests & Help', 'podlove') ?></h3>

			<p>
				<?php
				echo sprintf(
					__('The best way to reach us is via %sGitHub Issues%s. Feel free to join in on discussions or create new topics.', 'podlove'),
					'<a href="https://github.com/podlove/podlove-publisher/issues" target="_blank">',
					'</a>'
				);
				?>
			</p>

			<p>
				<a target="_blank" href="https://github.com/podlove/podlove-publisher/issues" class="button button-primary">
					<?php echo __( 'Go to GitHub', 'podlove' ) ?>
				</a>
			</p>

			<p>
				<?php echo __( 'When reporting a bug, please append the following system report to help us trace the root cause:', 'podlove' ) ?>
			</p>

			<p>
				<?php 
				$r = new \Podlove\SystemReport;
				$report = $r->render();
				?>
				<textarea class="podlove_system_report" readonly cols="100" rows="<?php echo substr_count( $report, "\n" )+1; ?>"><?php echo $report ?></textarea>
			</p>

		</div>	
		<?php
	}

}
