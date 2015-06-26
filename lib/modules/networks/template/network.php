<?php
namespace Podlove\Modules\Networks\Template;

use Podlove\Template\Wrapper;
use Podlove\Modules\Networks\Model as NetworksModel;
use Podlove\Modules\Networks\Template as NetworksTemplate;

/**
 * Network Template Wrapper
 *
 * Requires the "Networks" module.
 *
 * @templatetag network
 */
class Network extends Wrapper {

	public function __construct() {

	}

	protected function getExtraFilterArgs() {
		return [];
	}

	/**
	 * Network Lists
	 * 
	 * List network lists.
	 * Use the `slug` parameter to access a specific list.
	 * 
	 * **Examples**
	 * 
	 * Iterate over all lists.
	 * 
	 * ```jinja
	 * {% for list in network.lists %}
	 *     {{ list.title }}
	 * {% endfor %}
	 * ```
	 * 
	 * Access a specific list by id.
	 * 
	 * ```jinja
	 * {{ network.lists({id: "example"}).title }}
	 * ```
	 * 
	 * @see list
	 * @accessor
	 */
	public function lists($args = []) {

		NetworksModel\PodcastList::activate_network_scope();

		if (isset($args['id'])) {
			if ($list = NetworksModel\PodcastList::find_one_by_slug($args['id']))
				return new NetworksTemplate\PodcastList($list);
		}
		
		$lists = [];
		foreach ( NetworksModel\PodcastList::all() as $list ) {
			$lists[] = new PodcastList( $list );
		}
		
		NetworksModel\PodcastList::deactivate_network_scope();

		return $lists;
	}

}


		