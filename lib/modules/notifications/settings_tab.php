<?php 
namespace Podlove\Modules\Notifications;

use Podlove\Settings\Settings;
use Podlove\Settings\Expert\Tab;
use Podlove\Modules\Contributors\Settings\ContributorSettings;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Model\Episode;

class SettingsTab extends Tab {

	private $page = NULL;

	public function page()
	{
		parent::page();

		$debug_hook = self::debug_hook();

		?>
		<form method="post" action="options.php">
			<?php if ( isset( $_REQUEST['podlove_tab'] ) ): ?>
				<input type="hidden" name="podlove_tab" value="<?php echo esc_attr($_REQUEST['podlove_tab']) ?>" />
			<?php endif; ?>

			<?php settings_fields( $debug_hook ); ?>
			<?php do_settings_sections( $debug_hook ); ?>
			
			<?php submit_button( __( 'Send Test Emails', 'podlove-podcasting-plugin-for-wordpress' ), 'button', 'submit', TRUE ); ?>
		</form>		
		<?php
	}

	public static function settings_hook()
	{
		return ContributorSettings::$pagehook;
	}

	public static function debug_hook()
	{
		return ContributorSettings::$pagehook . '_debug';
	}

	public function init() {

		$hook = self::settings_hook();
		$debug_hook = self::debug_hook();

		add_settings_section(
			/* $id 		 */ 'podlove_settings_notifications_delay',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'E-Mail Notification Settings', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
			},
			/* $page	 */ $hook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_notifications_delay',
			/* $title    */ sprintf(
				'<label for="podlove_delay">%s</label>',
				__( 'Delay notifications', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_notifications[delay]" id="podlove_delay" type="number" value="<?php echo esc_attr(\Podlove\get_setting('notifications', 'delay')) ?>" class="text"> <?php _e('minutes', 'podlove-podcasting-plugin-for-wordpress') ?>
				<p>
					<span class="description">
						<?php echo __( 'Delay in minutes after an episode is published before notification e-mails are sent. Note that it may always take a few minutes longer than specified until e-mails are sent out.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
					</span>
				</p>	
				<?php
			},
			/* $page     */ $hook,  
			/* $section  */ 'podlove_settings_notifications_delay'
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_notifications_content',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'Content', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
				?>
				<p>
					<span class="description">
						<?php echo sprintf(
							__( 'Additionally to %sall standard template tags%s you have access to the receiving %scontributor%s in notification content.', 'podlove-podcasting-plugin-for-wordpress' ),
							'<a href="http://docs.podlove.org/podlove-publisher/reference/template-tags.html" target="_blank">',
							'</a>',
							'<a href="http://docs.podlove.org/podlove-publisher/reference/template-tags.html#contributor" target="_blank">',
							'</a>'
						); ?>
					</span>
						
				</p>
				<?php
			},
			/* $page	 */ $hook
		);

		add_settings_field(
			/* $id       */ 'podlove_settings_notifications_subject',
			/* $title    */ sprintf(
				'<label for="podlove_delay">%s</label>',
				__( 'Subject', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input type="text" name="podlove_notifications[subject]" value="<?php echo esc_attr(\Podlove\get_setting('notifications', 'subject')) ?>" class="text large-text">
				<?php
			},
			/* $page     */ $hook,  
			/* $section  */ 'podlove_settings_notifications_content'
		);

		add_settings_field(
			/* $id       */ 'podlove_settings_notifications_body',
			/* $title    */ sprintf(
				'<label for="podlove_delay">%s</label>',
				__( 'Message', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<textarea name="podlove_notifications[body]" class="large-text"><?php echo esc_html(\Podlove\get_setting('notifications', 'body')) ?></textarea>
				<?php
			},
			/* $page     */ $hook,  
			/* $section  */ 'podlove_settings_notifications_content'
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_notifications_sender',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'Sender', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
				?>
				<p>
					<span class="description">
						<?php echo __( 'Send e-mails with given contributor\'s name and e-mail.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
					</span>
				</p>
				<?php
			},
			/* $page	 */ $hook
		);

		add_settings_field(
			/* $id       */ 'podlove_settings_notifications_send_as',
			/* $title    */ sprintf(
				'<label for="podlove_delay">%s</label>',
				__( 'Send as', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				$contributors = Contributor::all();
				?>
				<select name="podlove_notifications[send_as]" class="chosen-image podlove-contributor-dropdown" style="width: 220px;">
					<option value=""><?php echo __('Choose Contributor', 'podlove-podcasting-plugin-for-wordpress') ?></option>
					<?php foreach ( $contributors as $contributor ): ?>
						<option value="<?php echo $contributor->id ?>" data-img-src="<?php echo $contributor->avatar()->setWidth(10)->url() ?>" <?php selected(\Podlove\get_setting('notifications', 'send_as'), $contributor->id) ?>><?php echo $contributor->getName(); ?></option>
					<?php endforeach; ?>
				</select>

				<script type="text/javascript">
				(function($) {
					$(".chosen").chosen({ width: '100%' });
					$(".chosen-image").chosenImage();
				}(jQuery));
				</script>
				<?php
			},
			/* $page     */ $hook,  
			/* $section  */ 'podlove_settings_notifications_sender'
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_notifications_recipients',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'Recipients', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
				?>
				<p>
					<span class="description">
						<?php echo __( 'Send e-mails to contributors of an episode. Send to either everyone or just contributors with a certain group or role.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
					</span>
				</p>
				<?php
			},
			/* $page	 */ $hook
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_notifications_group',
			/* $title    */ sprintf(
				'<label>%s</label>',
				__( 'Group', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				$groups = ContributorGroup::all();
				?>
				<select name="podlove_notifications[group]">
					<option value="0"><?php _e('All Groups', 'podlove-podcasting-plugin-for-wordpress') ?></option>
					<?php foreach ($groups as $group): ?>
						<option value="<?php echo esc_attr($group->id); ?>" <?php selected(\Podlove\get_setting('notifications', 'group'), $group->id) ?>><?php echo esc_html($group->title); ?></option>
					<?php endforeach ?>
				</select>
				<?php
			},
			/* $page     */ $hook,  
			/* $section  */ 'podlove_settings_notifications_recipients'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_notifications_role',
			/* $title    */ sprintf(
				'<label>%s</label>',
				__( 'Role', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				$roles = ContributorRole::all();
				?>
				<select name="podlove_notifications[role]">
					<option value="0"><?php _e('All Roles', 'podlove-podcasting-plugin-for-wordpress') ?></option>
					<?php foreach ($roles as $role): ?>
						<option value="<?php echo esc_attr($role->id); ?>" <?php selected(\Podlove\get_setting('notifications', 'role'), $role->id) ?>><?php echo esc_html($role->title); ?></option>
					<?php endforeach ?>
				</select>
				<?php
			},
			/* $page     */ $hook,  
			/* $section  */ 'podlove_settings_notifications_recipients'
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_notifications_test',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'Testing', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
				?>
				<p>
					<span class="description">
						<?php echo __( 'Send test emails to see if everything works as expected. Sends all emails based on contributors in selected episode but receiver is always the one configured here in the test section.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
					</span>
				</p>
				<?php
			},
			/* $page	 */ $debug_hook
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_notifications_test_episode',
			/* $title    */ sprintf(
				'<label>%s</label>',
				__( 'Episode', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				$episodes = Episode::find_all_by_time();
				?>
				<select name="podlove_notifications_test[episode]">
					<option value="0"><?php _e('Select Episode', 'podlove-podcasting-plugin-for-wordpress') ?></option>
					<?php foreach ($episodes as $episode): ?>
						<option value="<?php echo esc_attr($episode->id); ?>" <?php selected(\Podlove\get_setting('notifications_test', 'episode'), $episode->id) ?>><?php echo esc_html($episode->title()); ?></option>
					<?php endforeach ?>
				</select>
				<?php
			},
			/* $page     */ $debug_hook,  
			/* $section  */ 'podlove_settings_notifications_test'
		);


		add_settings_field(
			/* $id       */ 'podlove_setting_notifications_test_receiver',
			/* $title    */ sprintf(
				'<label for="podlove_delay">%s</label>',
				__( 'Receiver', 'podlove-podcasting-plugin-for-wordpress' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove_notifications_test[receiver]" type="email" value="<?php echo esc_attr(\Podlove\get_setting('notifications_test', 'receiver')) ?>" class="text">
				<?php
			},
			/* $page     */ $debug_hook,  
			/* $section  */ 'podlove_settings_notifications_test'
		);

		register_setting( $hook, 'podlove_notifications' );
		register_setting( $debug_hook, 'podlove_notifications_test' );
	}

	/*
	public function render_page() {
		?>
		<div class="wrap">
			Hello World?
		</div>
		<?php
	}
	*/

}
