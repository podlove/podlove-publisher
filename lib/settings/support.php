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
			<h2><?php echo __( 'Support', 'podlove-podcasting-plugin-for-wordpress' ); ?></h2>

			<div class="notice" style="margin: 3%">
				<p style="float: right">
					<a href="//publisher.podlove.org/support/" target="_blank" class="button button-primary"><?php echo __('Get Support', 'podlove-podcasting-plugin-for-wordpress') ?></a>
				</p>
				<h2><?php echo __('Get Professional Support', 'podlove-podcasting-plugin-for-wordpress') ?></h2>
				<p>
					<?php echo sprintf(
						__('If you need quick, private and competent support, get it at %spublisher.podlove.org/support%s.', 'podlove-podcasting-plugin-for-wordpress'),
						'<a href="//publisher.podlove.org/support/" target="_blank">', '</a>'
					); ?>
				</p>
				<p>
					<?php echo __('We are happy to help getting you up and running during setup or answering questions that come up once in a while.', 'podlove-podcasting-plugin-for-wordpress') ?>
				</p>
			</div>

			<h3><?php echo __('Bug Reports, Feature Requests & Help', 'podlove-podcasting-plugin-for-wordpress') ?></h3>

			<p>
				<ul>
					<li>
						<?php echo sprintf(
							__('Please report bugs at %sGitHub Issues%s.', 'podlove-podcasting-plugin-for-wordpress'),
							'<a href="https://github.com/podlove/podlove-publisher/issues" target="_blank">', '</a>'
						); ?>
					</li>
					<li>
						<?php echo sprintf(
							__('%sPodlove Community%s is the best place to find answers, ask the community for help and discuss features.', 'podlove-podcasting-plugin-for-wordpress'),
							'<a target="_blank" href="//community.podlove.org">', '</a>'
						); ?>
					</li>
					<li>
						<?php echo sprintf(
							__('%sTrello%s shows our roadmap.', 'podlove-podcasting-plugin-for-wordpress'),
							'<a target="_blank" href="//trello.com/board/podlove-publisher/508293f65573fa3f62004e0a">', '</a>'
						); ?>
					</li>
					<li>
						<?php echo sprintf(
							__('For quick remarks and feedback, you can reach us at %sTwitter (@podlove_org)%s and %sADN (@podlove)%s', 'podlove-podcasting-plugin-for-wordpress'),
							'<a target="_blank" href="//twitter.com/podlove_org">', '</a>',
							'<a target="_blank" href="https://alpha.app.net/podlove">', '</a>'
						); ?>
					</li>
				</ul>
			</p>

			<?php do_action('podlove_support_repair_html'); ?>

			<p>
				<?php echo __( 'When reporting a bug, please append the following system report to help us trace the root cause:', 'podlove-podcasting-plugin-for-wordpress' ) ?>
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
