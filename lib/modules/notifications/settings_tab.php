<?php 
namespace Podlove\Modules\Notifications;

use Podlove\Settings\Settings;
use Podlove\Settings\Expert\Tab;
use Podlove\Modules\Contributors\Settings\ContributorSettings;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\Contributor;

class SettingsTab extends Tab {

	private $page = NULL;

	public function init() {

		$hook = ContributorSettings::$pagehook;

		add_settings_section(
			/* $id 		 */ 'podlove_settings_notifications_delay',
			/* $title 	 */ __( '', 'podlove-podcasting-plugin-for-wordpress' ),	
			/* $callback */ function () {
				echo '<h3>' . __( 'E-Mail Notification Settings', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>';
				?>
				<p>
					<span class="description">
						<?php echo __( 'Delay in minutes after an episode is published before notification e-mails are sent. Note that it may always take a few minutes longer than specified until e-mails are sent out.', 'podlove-podcasting-plugin-for-wordpress' ); ?>
					</span>
				</p>				
				<?php
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
				<input type="text" name="podlove_notifications[subject]" value="<?php echo \Podlove\get_setting('notifications', 'subject') ?>" class="regular-text">
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
				<textarea name="podlove_notifications[body]" class="large-text autogrow"><?php echo \Podlove\get_setting('notifications', 'body') ?></textarea>
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
				'<label for="enable_episode_recording_date">%s</label>',
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
				'<label for="enable_episode_recording_date">%s</label>',
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

		register_setting( $hook, 'podlove_notifications' );
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
