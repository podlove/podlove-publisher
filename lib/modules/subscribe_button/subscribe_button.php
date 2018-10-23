<?php
namespace Podlove\Modules\SubscribeButton;
use Podlove\Model;

class Subscribe_Button extends \Podlove\Modules\Base {

	protected $module_name = 'Subscribe Button';
	protected $module_description = 'Use <code title="Shortcode for the Subscribe Button">[podlove-podcast-subscribe-button]</code> to display a button which allows users to easily subscribe to your podcast. <a href="https://docs.podlove.org/podlove-publisher/reference/shortcodes.html#subscribe-button">Documentation</a>';
	protected $module_group = 'web publishing';

	public static function styles() {
		return [
			'filled'    => __('Filled', 'podlove-podcasting-plugin-for-wordpress'),
			'outline'   => __('Outline', 'podlove-podcasting-plugin-for-wordpress'),
			'frameless' => __('Frameless', 'podlove-podcasting-plugin-for-wordpress')
		];
	}

	public static function formats() {
		return [
			'rectangle' => __('Rectangle', 'podlove-podcasting-plugin-for-wordpress'),
			'square'    => __('Square', 'podlove-podcasting-plugin-for-wordpress'),
			'cover'     => __('Cover', 'podlove-podcasting-plugin-for-wordpress')
		];
	}

	public static function sizes() {
		return [
			'small'  => __('Small', 'podlove-podcasting-plugin-for-wordpress'),
			'medium' => __('Medium', 'podlove-podcasting-plugin-for-wordpress'),
			'big'    => __('Big', 'podlove-podcasting-plugin-for-wordpress')
		];
	}

	public static function languages() {
		return ['de', 'en', 'eo', 'fi', 'fr', 'nl', 'zh', 'ja'];
	}

	public function load() {
		
		self::register_shortcode();

		add_filter(
			'podlove_widgets',
			function ($widgets) {
				$widgets[] = '\Podlove\Modules\SubscribeButton\Widget';
				return $widgets;
			}
		);

		$this->register_option( 'use_cdn', 'radio', [
			'label' => __( 'Use CDN?', 'podlove-podcasting-plugin-for-wordpress' ),
			'description' => '<p>' . __( 'Use our CDN (https://cdn.podlove.org) to always have the current version of the button on your site. Alternatively deliver the button with your own WordPress instance with the disadvantage of not using the most recent version all the time.', 'podlove-podcasting-plugin-for-wordpress' ) . '</p>',
			'default' => '1',
			'options' => [
				1 => __('yes, use CDN', 'podlove-podcasting-plugin-for-wordpress') . ' (' . __('recommended', 'podlove-podcasting-plugin-for-wordpress') .  ')',
				0 => __('no, deliver with WordPress', 'podlove-podcasting-plugin-for-wordpress')
			]
		]);

		\Podlove\Template\Podcast::add_accessor(
			'subscribeButton', ['\Podlove\Modules\SubscribeButton\TemplateExtensions', 'accessorPodcastSubscribeButton'], 4
		);
	}

	// shortcode function
	public static function button($args = array()) {

		if (!is_array($args))
			$args = [];

		$podcast = Model\Podcast::get();

		$data = [
			'title'       => $podcast->title,
			'subtitle'    => $podcast->subtitle,
			'description' => $podcast->summary,
			'cover'       => $podcast->cover_art()->setWidth(400)->url(),
			'feeds'       => Button::feeds($podcast->feeds(['only_discoverable' => true])),
		];

		if ($podcast->language) {
			$args['language'] = Button::language($podcast->language);
		}

		$args = apply_filters('podlove_subscribe_button_args', $args, $podcast);
		$data = apply_filters('podlove_subscribe_button_data', $data, $args, $podcast);

		return (new Button())->render($data, $args);
	}

	public static function register_shortcode() {
		// backward compatible, but only load if no other plugin has registered this shortcode
		if (!shortcode_exists('podlove-subscribe-button'))
			add_shortcode('podlove-subscribe-button', [__CLASS__, 'button']);

		add_shortcode('podlove-podcast-subscribe-button', [__CLASS__, 'button']);
	}

}
