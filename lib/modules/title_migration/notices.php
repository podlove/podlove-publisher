<?php
namespace Podlove\Modules\TitleMigration;

class Notices {

	public function register_init_notice()
	{
		add_action('admin_notices', [$this, 'the_init_notice']);
	}

	public function register_finished_notice()
	{
		add_action('admin_notices', [$this, 'the_finished_notice']);
	}

	public function the_init_notice()
	{
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php echo __('Podlove Module: Title Migration', 'podlove-podcasting-plugin-for-wordpress') ?></strong>
			</p>
			<p>
				<?php echo __('This update enables new episode fields introduced by Apple/iTunes iOS 11 Podcast Specification to enhance the listener experience. You need to fill in metadata fields in existing episodes to take advantage.', 'podlove-podcasting-plugin-for-wordpress') ?>
			</p>
			<p>
				<?php echo __('The Publisher provides a tool to help you update that metadata quickly.', 'podlove-podcasting-plugin-for-wordpress') ?>
			</p>
			<p>
				<a class="button" href="<?php echo admin_url('admin.php?page=podlove_tools_settings_handle#the_tools_section'); ?>"><?php echo __('Take me to the tool', 'podlove-podcasting-plugin-for-wordpress') ?></a> <a href="<?php echo self::hide_message_url(State::INITIALIZED_HIDDEN); ?>"><?php echo __('hide this message', 'podlove-podcasting-plugin-for-wordpress') ?></a>
			</p>
		</div>	
		<?php
	}

	public function the_finished_notice()
	{
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php echo __('Podlove Module: Title Migration', 'podlove-podcasting-plugin-for-wordpress') ?></strong>
			</p>
			<p>
				<?php echo __('You are done migrating your episode titles.', 'podlove-podcasting-plugin-for-wordpress') ?>
			</p>
			<p>
				<?php echo sprintf(
					__('If you would like to take advantage of automatic blog episode title generation based on episode number and title, head over to %s. Also remember to set your podcast mnemonic in %s.', 'podlove-podcasting-plugin-for-wordpress'),
					'<a href="' . admin_url('admin.php?page=podlove_settings_settings_handle') . '">' . __('Expert Settings', 'podlove-podcasting-plugin-for-wordpress') . '</a>',
					'<a href="' . admin_url('admin.php?page=podlove_settings_podcast_handle') . '">' . __('Podcast Settings', 'podlove-podcasting-plugin-for-wordpress') . '</a>'
				); ?>
			</p>
			<p>
				<?php echo __('When you are done, you can deactivate the title migration module.' , 'podlove-podcasting-plugin-for-wordpress') ?>
			</p>
			<p>
				<a class="button" href="<?php echo admin_url('admin.php?page=podlove_settings_modules_handle&podlove_disable_title_migration_module=1'); ?>"><?php echo __('Deactivate Title Migration Module', 'podlove-podcasting-plugin-for-wordpress') ?></a> <a href="<?php echo self::hide_message_url(State::FINISHED_HIDDEN); ?>"><?php echo __('hide this message', 'podlove-podcasting-plugin-for-wordpress') ?></a>
			</p>
		</div>	
		<?php
	}

	public static function hide_message_url($state)
	{
		if (isset($_REQUEST['page']) && $_REQUEST['page']) {
			return admin_url('admin.php?page=' . $_REQUEST['page'] . '&podlove_set_title_migration_state=' . $state);
		} else {
			return admin_url('admin.php?page=podlove_tools_settings_handle&podlove_set_title_migration_state=' . $state);
		}
	}

}
