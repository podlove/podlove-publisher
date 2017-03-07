<?php 
namespace Podlove\Modules\Notifications;

use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;

class Notifications extends \Podlove\Modules\Base {
	
	protected $module_name = 'E-Mail Notifications';
	protected $module_description = 'Notify contributors via E-Mail when episodes get published.';
	protected $module_group = 'system';

	public function load()
	{
		add_action('publish_podcast', [$this, 'maybe_send_notifications'], 10, 2);

		add_filter('podlove_contributor_settings_tabs', function ($tabs) {
			$tabs->addTab( new \Podlove\Modules\Notifications\SettingsTab( __( 'E-Mail Notifications', 'podlove-podcasting-plugin-for-wordpress' ) ) );
			return $tabs;
		});

		if (isset($_REQUEST['debug_notification']) && $_REQUEST['debug_notification']) {
			$this->maybe_send_notifications(4116, get_post(4116));
		}
	}

	public function maybe_send_notifications($post_id, $post)
	{
		error_log(print_r('maybe_send_notifications', true));

		// if ($this->notifications_sent($post_id))
		// 	return;

		$this->mark_notifications_sent($post_id);

		error_log(print_r('marker is set, now we send', true));
		
		// who? until there is UI, all contributors

		// THOUGHT: maybe ... this should only be a hook that expects emails, names 
		// or something, so it doesn't directly depend on the contributor module.

		$episode = Episode::find_one_by_property('post_id', (int) $post_id);
		$contributions = EpisodeContribution::find_all_by_property('episode_id', $episode->id);

		// map contributions to contributors
		$contributors = array_map(function($c) {
			return Contributor::find_by_id($c->contributor_id);
		}, $contributions);

		// stop if there is no one to be notified
		if (!count($contributors))
			return;

		foreach ($contributors as $contributor) {
			
			if (!$contributor->privateemail) {
				// todo: log that no email is set
				error_log(print_r("no email for " . $contributor->getName() . " (id " . $contributor->id . ")", true));
				continue;
			}

			// todo: subject should be user configurable template with placeholders
			$subject = "Episode Published: " . get_the_title($post);
			$headers = [
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . self::getSenderAddress()
			];
			$message = "Hello " . $contributor->getName() . ",<p>Geht voll ab!</p><p>Greetings, Dein Internet</p>";

			// todo: send using background jobs
			$success = wp_mail( $contributor->getMailAddress(), $subject, $message, $headers );

			// todo: log is not successful

			error_log(print_r("wp_mail: $success", true));
		}
	}

	public static function getSenderAddress()
	{
		$default   = get_option('admin_email');
		$sender_id = \Podlove\get_setting('notifications', 'send_as');
		$sender    = Contributor::find_by_id($sender_id);

		if (!$sender)
			return $default;

		$address = $sender->getMailAddress();

		if (!$address)
			return $default;

		return $address;
	}

	/**
	 * Were notifications for this episode sent already?
	 * 
	 * @param  int $post_id
	 * @return bool    True if notifications were sent, otherwise false.
	 */
	public function notifications_sent($post_id)
	{
		return (bool) get_post_meta($post_id, '_podlove_notifications_sent', true);
	}

	/**
	 * Remember that notifications have been sent.
	 * 
	 * @param  int $post_id
	 */
	public function mark_notifications_sent($post_id)
	{
		update_post_meta($post_id, '_podlove_notifications_sent', true);
	}

}
