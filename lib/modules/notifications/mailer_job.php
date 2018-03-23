<?php 
namespace Podlove\Modules\Notifications;

use Podlove\Log;
use Podlove\Jobs\JobTrait;
use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;

class MailerJob {
	use JobTrait;

	public static function title() {
		return __('Sending Notification E-Mails', 'podlove-podcasting-plugin-for-wordpress');
	}

	public static function description() {
		return __('Sends notification emails to contributors.', 'podlove-podcasting-plugin-for-wordpress');
	}

	public static function mode($args)
	{
		if (isset($args['debug']) && $args['debug']) {
			return __('Test', 'podlove-podcasting-plugin-for-wordpress');
		}

		return '';
	}

	public function get_total_steps() {
		return count($this->job->args['contributors']);
	}

	public function setup() {
		$this->hooks['init'] = [$this, 'init_job'];
	}

	public function init_job()
	{
		// todo: verify episode and contributors params exist and are valid; abort and log otherwise
		$this->job->state = [
			'contributors_todo' => $this->job->args['contributors']
		];
	}

	protected function do_step()
	{
		// fetch next contributor to receive notification and save state
		$contributors_todo = $this->job->state['contributors_todo'];
		$contributor_id    = array_pop($contributors_todo);
		$contributor       = Contributor::find_by_id($contributor_id);

		$this->job->update_state('contributors_todo', $contributors_todo);

		self::register_log_mailer_errors();
		$this->prepare_and_send_mail($contributor);
		self::deregister_log_mailer_errors();

		return 1;
	}

	private static function register_log_mailer_errors() {
		add_action('wp_mail_failed', [__CLASS__, 'log_mailer_errors']);
	}

	private static function deregister_log_mailer_errors() {
		remove_action('wp_mail_failed', [__CLASS__, 'log_mailer_errors']);
	}

	public static function log_mailer_errors($wp_error)
	{
		Log::get()->addWarning("Sending email failed. Reason: " . $wp_error->get_error_message(), [
			'module'   => 'E-Mail Notifications',
			'wp_error' => $wp_error
		]);
	}

	private function prepare_and_send_mail(Contributor $contributor)
	{
		global $post; // required for setup_postdata()
		
		$episode = Episode::find_by_id($this->job->args['episode']);
		$post    = get_post($episode->post_id);

		if (!$contributor->privateemail) {
			Log::get()->addWarning("Tried sending email notification to " . $contributor->getName() . ". Unsuccessful due to missing contact email.", [
				'module' => 'E-Mail Notifications',
				'contributor_id'   => $contributor->id,
				'contributor_name' => $contributor->getName(),
				'episode_id'    => $episode->id,
				'episode_title' => $episode->title()
			]);
			return;
		}
		
		setup_postdata($post);

		// add contributor to message context
		$add_contribtutor_to_context = function ($context) use ($contributor) {
			$context['contributor'] = new \Podlove\Modules\Contributors\Template\Contributor($contributor);
			return $context;
		};

		add_filter('podlove_templates_global_context', $add_contribtutor_to_context);

		$success = wp_mail(
			$this->getReceiver($contributor),
			$this->getSubject(),
			$this->getMessage(),
			$this->getHeaders()			
		);

		remove_filter('podlove_templates_global_context', $add_contribtutor_to_context);

		if (!$success) {
			Log::get()->addWarning("Tried sending email notification to " . $contributor->getName() . ". wp_mail was unable to send.", [
				'module' => 'E-Mail Notifications',
				'contributor_id'   => $contributor->id,
				'contributor_name' => $contributor->getName(),
				'episode_id'    => $episode->id,
				'episode_title' => $episode->title()
			]);
		}

		wp_reset_postdata();
	}

	public function getReceiver(Contributor $contributor)
	{
		if (!$this->isDebug()) {
			return $contributor->getMailAddress();
		} else {
			return $this->job->args['debug_receiver'];
		}
	}

	public function isDebug()
	{
		return $this->job->args['debug'] && $this->job->args['debug_receiver'];
	}

	public function getSubject()
	{
		$subject = \Podlove\get_setting('notifications', 'subject');
		$subject = \Podlove\Template\TwigFilter::apply_to_html($subject);

		if ($this->isDebug()) {
			$subject = '[TEST] ' . $subject;
		}

		return $subject;
	}

	public function getHeaders()
	{
		return [
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . self::getSenderAddress()
		];		
	}

	public function getMessage()
	{
		$message = \Podlove\get_setting('notifications', 'body');
		$message = \Podlove\Template\TwigFilter::apply_to_html($message);

		return $message;
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
}
