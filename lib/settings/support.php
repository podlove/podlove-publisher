<?php

namespace Podlove\Settings;

class Support
{
    use \Podlove\HasPageDocumentationTrait;

    public static $pagehook;

    public function __construct($handle)
    {
        Support::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Support', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Support', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            'podlove_Support_settings_handle',
            // $function
            [$this, 'page']
        );

        $this->init_page_documentation(self::$pagehook);
    }

    public function page()
    {
        ?>
		<div class="wrap">
			<h2><?php echo __('Support', 'podlove-podcasting-plugin-for-wordpress'); ?></h2>

    		<h3><?php echo __('Bug Reports, Feature Requests & Help', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>

			<p>
				<ul>
					<li>
						<?php echo sprintf(
            __('Please report bugs at %sGitHub Issues%s.', 'podlove-podcasting-plugin-for-wordpress'),
            '<a href="https://github.com/podlove/podlove-publisher/issues" target="_blank">',
            '</a>'
        ); ?>
					</li>
					<li>
						<?php echo sprintf(
            __('%sPodlove Community%s is the best place to find answers, ask the community for help and discuss features.', 'podlove-podcasting-plugin-for-wordpress'),
            '<a target="_blank" href="//community.podlove.org">',
            '</a>'
        ); ?>
					</li>
					<li>
						<?php echo sprintf(
            __('For quick remarks and feedback, you can reach us at %sTwitter (@podlove_org)%s', 'podlove-podcasting-plugin-for-wordpress'),
            '<a target="_blank" href="//twitter.com/podlove_org">',
            '</a>'
        ); ?>
					</li>
				</ul>
			</p>

			<p>
				<?php echo __('When reporting a bug, please append the following system report to help us trace the root cause:', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>

			<p>
				<?php
                $r = new \Podlove\SystemReport();
        $report = $r->render(); ?>
				<textarea class="podlove_system_report" readonly cols="100" rows="<?php echo substr_count($report, "\n") + 1; ?>"><?php echo $report; ?></textarea>
			</p>

		</div>	
		<?php

        do_action('podlove_support_page_footer');
    }
}
