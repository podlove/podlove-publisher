<?php
namespace Podlove\Modules\Networks\Template;

use Podlove\Template\Wrapper;

/**
 * List Template Wrapper
 *
 * Requires the "Networks" module.
 *
 * @templatetag podlove
 */
class Podlove extends Wrapper {

	public function __construct() {
	}

	protected function getExtraFilterArgs() {
		return array();
	}

	public function lists( $args ) {
		if ( isset($args['slug']) ) {
			$list_with_slug = \Podlove\Modules\Networks\Model\PodcastList::find_one_by_property( 'slug', $args['slug'] );		
			if ( is_object( $list_with_slug ) )
				return array( new \Podlove\Modules\Networks\Template\PodcastList( 
					 	$list_with_slug
					) );

			return;
		}
		
		$lists = array();
		foreach ( \Podlove\Modules\Networks\Model\PodcastList::all() as $list ) {
			$lists[] = new \Podlove\Modules\Networks\Template\PodcastList( $list );
		}
		return $lists;
	}

}


		