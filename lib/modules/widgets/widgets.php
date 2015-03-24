<?php
namespace Podlove\Modules\Widgets;
use Podlove\Model;

class Widgets extends \Podlove\Modules\Base {

	protected $module_name = 'Widgets';
	protected $module_description = 'Brings a bunch of useful Podlove Publisher widgets to WordPress.';
	protected $module_group = 'web publishing';

	public function load() {
		$widgets = array(
					'\Podlove\Modules\Widgets\Widgets\PodcastLicense',
					'\Podlove\Modules\Widgets\Widgets\RecentEpisodes',
					'\Podlove\Modules\Widgets\Widgets\PodcastInformation',
					'\Podlove\Modules\Widgets\Widgets\RenderTemplate'
				);
		$widgets = apply_filters( 'podlove_widgets', $widgets );

		foreach ($widgets as $widget_class ) {
			add_action( 'widgets_init', function() use ( $widget_class ) {
			     register_widget( $widget_class );
			});
		}
	}
}
