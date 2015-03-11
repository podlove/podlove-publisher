<?php
namespace Podlove\Modules\SubscribeButton;
use Podlove\Model;

class Subscribe_Button extends \Podlove\Modules\Base {

	protected $module_name = 'Subscribe Button';
	protected $module_description = 'Use <code title="Shortcode for the Subscribe Button">[podlove-subscribe-button]</code> to display a button which allows users to easily subscribe to your podcast.';
	protected $module_group = 'web publishing';

	public function load() {
		add_shortcode( 'podlove-subscribe-button', array( $this, 'button' ) );

		add_action( 'widgets_init', function(){
		     register_widget( '\Podlove\Modules\SubscribeButton\Widget' );
		});
	}

	/**
	 * Prepare Podlove feeds for Subscribe Button format.
	 *
	 * @todo $file_type->name could change! there must be another way
	 * 
	 * @param  array $podlove_feeds List of podlove feeds
	 * @return array
	 */
	public static function prepare_feeds($podlove_feeds) {
		
		$feeds = array();

		foreach ($podlove_feeds as $feed) {
			$file_type = $feed->episode_asset()->file_type();

			switch ($file_type->name) {
				case 'MPEG-4 AAC Audio':
					$format = 'aac';
				break;
				case 'Ogg Vorbis Audio':
					$format = 'ogg';
				break;
				default:
					$format = $file_type->extension;
				break;
			}

			$feeds[] = array(
				'type'    => $file_type->type,
				'format'  => $format,
				'url'     => $feed->get_subscribe_url(),
				'variant' => 'high'
			);
		}

		return $feeds;
	}

	/**
	 * Prepare podcast data for Subscribe Button format.
	 * 
	 * @param  \Podlove\Model\Podcast $podcast
	 * @param  array $feeds
	 * @return array
	 */
	public static function prepare_podcast($podcast, $feeds) {
		return array(
			'title'    => $podcast->title,
			'subtitle' => $podcast->subtitle,
			'summary'  => $podcast->summary,
			'cover'    => $podcast->cover_image,
			'feeds'    => $feeds
		);
	}

	/**
	 * Prepare Subscribe Button parameters
	 *
	 * - size:  one of 'small', 'medium', 'big', 'big-logo'
	 * - width: 'auto' to enable auto-size, all other values deactivate it
	 * 
	 * @param  array $args [ 'size' => value, 'width' => value ]
	 * @return array
	 */
	public static function prepare_button_params($args) {

		// apply defaults
		$defaults = array(
			'size'  => 'big-logo',
			'width' => 'auto'
		);
		$args = wp_parse_args($args, $defaults);

		// whitelist size parameter
		$valid_sizes = array('small', 'medium', 'big', 'big-logo');
		if (!in_array($args['size'], $valid_sizes)) {
			$args['size'] = $defaults['size'];
		}

		// only "auto" is allowed, otherwise empty param
		if ($args['width'] == 'auto') {
			$args['width'] = ' auto';
		} else {
			$args['width'] = '';
		}

		return $args;
	}

	/**
	 * Return Subscribe Button HTML
	 *
	 *	For $args doc, see `prepare_button_params`.
	 * 
	 * @param  array $args
	 * @return string
	 */
	public static function render_button($args) {
		$args = Subscribe_Button::prepare_button_params($args);

		$podcast       = Model\Podcast::get();
		$podlove_feeds = Model\Feed::find_all_by_discoverable(1);
		$feeds         = Subscribe_Button::prepare_feeds($podlove_feeds);

		if (!$podcast || !$feeds)
			return;

		$podcast_data = Subscribe_Button::prepare_podcast($podcast, $feeds);

		return sprintf(
			'<script>window.podcastData = %s;</script>
			 <script class="podlove-subscribe-button" src="https://cdn.podlove.org/subscribe-button/javascripts/app.js" data-language="%s" data-size="%s%s" data-json-data="podcastData"></script>',
			json_encode($podcast_data),
			$podcast->language,
			$args['size'],
			$args['width']
		);
	}

	// shortcode function
	public function button($args) {
		return Subscribe_Button::render_button($args);
	}
}
