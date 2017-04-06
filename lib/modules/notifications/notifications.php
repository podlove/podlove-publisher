<?php 
namespace Podlove\Modules\Notifications;

use Podlove\Modules\Contributors\Model\EpisodeContribution;
use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Log;

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
			add_action('init', function () {
				$this->maybe_send_notifications(4116, get_post(4116));
			} );
		}
	}

	public function maybe_send_notifications($post_id, $post)
	{
		global $post;

		// if ($this->notifications_sent($post_id))
		// 	return;

		$this->mark_notifications_sent($post_id);

		$episode = Episode::find_one_by_property('post_id', (int) $post_id);
		$contributions = EpisodeContribution::find_all_by_property('episode_id', $episode->id);

		// map contributions to contributors
		$contributors = array_map(function($c) {
			return Contributor::find_by_id($c->contributor_id);
		}, $contributions);

		// stop if there is no one to be notified
		if (!count($contributors))
			return;

		// setup post data for Twig context
		$post = get_post($post_id);
		setup_postdata($post);

		foreach ($contributors as $contributor) {

			// add contributor to message context
			$add_contribtutor_to_context = function ($context) use ($contributor) {
				$context['contributor'] = new \Podlove\Modules\Contributors\Template\Contributor($contributor);
				return $context;
			};

			add_filter('podlove_templates_global_context', $add_contribtutor_to_context);
			
			if (!$contributor->privateemail) {
				Log::get()->addWarning("Tried sending email notification to " . $contributor->getName() . ". Unsuccessful due to missing contact email.", [
					'module' => $this->get_module_name(),
					'contributor_id'   => $contributor->id,
					'contributor_name' => $contributor->getName()
				]);
				continue;
			}

			$subject = \Podlove\get_setting('notifications', 'subject');
			$subject = \Podlove\Template\TwigFilter::apply_to_html($subject);

			$headers = [
				'Content-Type: text/plain; charset=UTF-8',
				'From: ' . self::getSenderAddress()
			];

			$message = \Podlove\get_setting('notifications', 'body');
			$message = \Podlove\Template\TwigFilter::apply_to_html($message);

			// todo: send using background jobs
			$success = wp_mail( $contributor->getMailAddress(), $subject, $message, $headers );

			remove_filter('podlove_templates_global_context', $add_contribtutor_to_context);

			if (!$success) {
				Log::get()->addWarning("Tried sending email notification to " . $contributor->getName() . ". wp_mail was unable to send.", [
					'module' => $this->get_module_name(),
					'contributor_id'   => $contributor->id,
					'contributor_name' => $contributor->getName()
				]);
			}
		}

		wp_reset_postdata();
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
