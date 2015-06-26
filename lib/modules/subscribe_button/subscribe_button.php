<?php
namespace Podlove\Modules\SubscribeButton;
use Podlove\Model;

class Subscribe_Button extends \Podlove\Modules\Base {

	protected $module_name = 'Subscribe Button';
	protected $module_description = 'Use <code title="Shortcode for the Subscribe Button">[podlove-subscribe-button]</code> to display a button which allows users to easily subscribe to your podcast.';
	protected $module_group = 'web publishing';

	public function load() {
		
		self::register_shortcode();

		add_filter(
			'podlove_widgets',
			function ($widgets) {
				$widgets[] = '\Podlove\Modules\SubscribeButton\Widget';
				return $widgets;
			}
		);

		\Podlove\Template\Podcast::add_accessor(
			'subscribeButton', ['\Podlove\Modules\SubscribeButton\TemplateExtensions', 'accessorPodcastSubscribeButton'], 4
		);
	}

	// shortcode function
	public static function button($args) {
		return (new Button(Model\Podcast::get()))->render($args);
	}

	public static function register_shortcode() {
		// backward compatible, but only load if no other plugin has registered this shortcode
		if (!shortcode_exists('podlove-subscribe-button'))
			add_shortcode('podlove-subscribe-button', [__CLASS__, 'button']);

		add_shortcode('podlove-podcast-subscribe-button', [__CLASS__, 'button']);
	}

}
