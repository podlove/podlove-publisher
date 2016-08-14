<?php
namespace Podlove\Modules\SubscribeButton;

class TemplateExtensions {

	/**
	 * Podcast Subscribe Button
	 * 
	 * **Examples**
	 * 
	 * ```jinja
	 * {{ podcast.subscribeButton }}
	 * ```
	 * 
	 * ```jinja
	 * {{ podcast.subscribeButton({format: 'square', color: '#000000', style: 'frameless', size: 'medium'}) }}
	 * ```
	 * 
	 * **Parameters**
	 * 
	 * - **format:** Choose a button format, options are 'rectangle', 'square' and 'cover' (**Note**: 'cover' has a max size of 300px) Default: 'cover'
	 * - **style:** Choose a button style, options are 'filled', 'outline' and 'frameless'. Default: 'filled'
	 * - **size:** Size and style of the button ('small', 'medium', 'big'). All of the sizes can be combined with 'auto' to adapt the button width to the available space like this: 'big auto'. Default: 'big'
	 * - **color:** Define the color of the button. Allowed are all notations for colors that CSS can understand (keyword, rgb-hex, rgb, rgba, hsl, hsla). Please Note: It is not possible to style multiple buttons/popups on the same page differently.
	 * - **language:** 'de', 'en', 'eo', 'fi', 'fr', 'nl', 'zh' and 'ja'. Defaults to podcast language setting.
	 * If you set the buttonid to "example123", your element must have the class "podlove-subscribe-button-example123".
	 * - **hide:** Set to `true` if you want to hide the default button element. Useful if you provide your own button via the `buttonid` setting.
	 * - **buttonid:** Use this if you want to trigger the button by clicking an element controlled by you. 
	 * 
	 * @accessor
	 * @dynamicAccessor podcast.subscribeButton
	 */
	public static function accessorPodcastSubscribeButton($return, $method_name, $podcast, $args = []) {
		return (new Button($podcast))->render($args);
	}

}
