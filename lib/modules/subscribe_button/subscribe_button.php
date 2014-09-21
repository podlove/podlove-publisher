<?php
namespace Podlove\Modules\SubscribeButton;
use Podlove\Model;

class Subscribe_Button extends \Podlove\Modules\Base {

	protected $module_name = 'Subscribe Button';
	protected $module_description = 'Use <code title="Shortcode for the Subscribe Button">[podlove-subscribe-button]</code> to display a button which allows users to easily subscribe to your podcast.';
	protected $module_group = 'web publishing';

	public function load() {
		add_shortcode( 'podlove-subscribe-button', function ($args) {
			$podcast = Model\Podcast::get_instance();
			$existing_feeds = Model\Feed::all();
			$feeds = array();

			if ( ! $podcast || ! $existing_feeds )
				return;

			foreach ($existing_feeds as $feed) {
				$file_type = $feed->episode_asset()->file_type();

				switch ($file_type->name) {
					case 'MPEG-4 AAC Audio':
						$feeds['aac'] = $feed->get_subscribe_url();
					break;
					case 'Ogg Vorbis Audio':
						$feeds['ogg'] = $feed->get_subscribe_url();
					break;
					default:
						$feeds[$file_type->extension] = $feed->get_subscribe_url();
					break;
				}
			}

			$subscribe_button_info = array(
					'title' => $podcast->title,
					'subtitle' => $podcast->subtitle,
					'summary' => $podcast->summary,
					'cover' => $podcast->cover_image,
					'feeds' => $feeds
				);

			return sprintf(
					'<script>window.podcastData = %s;</script><script class="podlove-subscribe-button" src="http://cdn.podlove.org/subscribe-button/javascripts/app.js" data-language="%s" data-size="%s" data-json-data="podcastData"></script>',
					json_encode($subscribe_button_info),
					$podcast->language,
					( isset($args['size']) && in_array($args['size'], array('small', 'medium', 'big')) ? $args['size'] : 'medium' )
				);
		} );
	}
	
}