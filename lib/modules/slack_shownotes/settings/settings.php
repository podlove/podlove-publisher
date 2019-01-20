<?php
namespace Podlove\Modules\SlackShownotes\Settings;

class Settings
{

    const MENU_SLUG = 'podlove_slackshownotes_settings';

    public function __construct($handle)
    {
        $pagehook = add_submenu_page(
            /* $parent_slug*/$handle,
            /* $page_title */__('Slacknotes', 'podlove-podcasting-plugin-for-wordpress'),
            /* $menu_title */__('Slacknotes', 'podlove-podcasting-plugin-for-wordpress'),
            /* $capability */'edit_posts',
            /* $menu_slug  */self::MENU_SLUG,
            /* $function   */[$this, 'page']
        );
    }

    public function page()
    {
        ?>
		<div class="wrap">
			<h2><?php echo __('Slacknotes', 'podlove-podcasting-plugin-for-wordpress'); ?></a></h2>

			<?php if (\Podlove\Modules\SlackShownotes\Slack_Shownotes::instance()->get_api_token()): ?>
				<div id="slacknotes-app">
					<slacknotes></slacknotes>
				</div>
			<?php else: ?>
				<div class="card">
					<h2 class="title"><?php echo __('API Token Required', 'podlove-podcasting-plugin-for-wordpress') ?></h2>
					<p>
						<?php echo __('You need to configure a Slack API token before you can use Slacknotes.', 'podlove-podcasting-plugin-for-wordpress') ?>
					</p>
					<p>
						<a href="<?php echo admin_url('admin.php?page=podlove_settings_modules_handle#slack_shownotes') ?>" class="button button-primary"><?php echo __('Go to Module Settings', 'podlove-podcasting-plugin-for-wordpress') ?></a>
					</p>
				</div>
			<?php endif;?>
		</div>
		<?php
}

}
