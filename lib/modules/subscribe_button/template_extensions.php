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
	 * {{ podcast.subscribeButton({size: 'small', width: '', colors: 'black;;;#ffffff'}) }}
	 * ```
	 * 
	 * **Parameters**
	 * 
	 * - **language:** 'de', 'en' or 'ja'. Defaults to podcast language setting.
	 * - **size:** Size and style of the button ('small', 'medium', 'big', 'big-logo'). Default: 'big-logo'
	 * - **buttonid:** Use this if you want to trigger the button by clicking an element controlled by you. 
	 * If you set the buttonid to "example123", your element must have the class "podlove-subscribe-button-example123".
	 * - **hide:** Set to `true` if you want to hide the default button element. Useful if you provide your own button via the `buttonid` setting.
	 * - **colors:** 9 colors, separated by semocolon, can be configured. Any blank color uses the default.
	 * 
	 * The colors are:
	 * 
	 * 1. buttonBackgroundColor
	 * 2. buttonHoverBackgroundColor
	 * 3. buttonActiveBackgroundColor
	 * 4. buttonTextColor
	 * 5. buttonHoverTextColor
	 * 6. buttonActiveTextColor
	 * 7. buttonBorderColor
	 * 8. listHighlightBackgroundColor
	 * 9. listHighlightTextColor
	 * 
	 * **Please Note:** It is not possible to style multiple buttons/popups on the same page differently.
	 * 
	 * Example color configurations:
	 * 
	 * - Complete: `#75ad91;#75c39d;#61937b;#ffffff;#ffffff;#ffffff;#456757;#328398;#ffffff`
	 * - Idle button background and text color: `#75ad91;;;#ffffff`
	 * 
	 * @accessor
	 * @dynamicAccessor podcast.subscribeButton
	 */
	public static function accessorPodcastSubscribeButton($return, $method_name, $podcast, $args = []) {
		return (new Button($podcast))->render($args);
	}

}