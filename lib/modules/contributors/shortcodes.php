<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Modules\Contributors\Model\Contributor;

use \Podlove\Model;

/**
 * Register all contributors shortcodes.
 */
class Shortcodes {

	/**
	 * List of contributions to be rendered.
	 */
	private $contributions = array();

	private static $shortcode_defaults = array(
		'preset'    => 'table',
		'avatars'   => 'yes',
		'role'      => 'all',
		'roles'		=> 'no',
		'group'		=> 'all',
		'groups'	=> 'no',
		'donations' => 'no',
		'flattr'    => 'yes',
		'title'     => ''
	);

	/**
	 * Shortcode settings.
	 */
	private $settings = array();

	public function __construct() {
		// legacy shortcode. deprecate?
		add_shortcode( 'podlove-contributors', array( $this, 'podlove_contributors') );
		// display a table/list of contributors
		add_shortcode( 'podlove-contributor-list', array( $this, 'podlove_contributor_list') );
		// display a table/list of podcast contributors
		add_shortcode( 'podlove-podcast-contributor-list', array( $this, 'podlove_podcast_contributor_list') );

		add_shortcode( 'podlove-global-contributor-list', array( $this, 'global_contributor_list') );
	}

	public function global_contributor_list($attributes)
	{
		if (!is_array($attributes))
			$attributes = array();

		$tpl = \Podlove\load_template( trailingslashit(dirname(__FILE__)) . 'templates/podcast-contributor-list.twig');
		return \Podlove\Template\TwigFilter::apply_to_html($tpl, $attributes);
	}

	/**
	 * Legacy Contributors Shortcode.
	 * 
	 * Examples:
	 *
	 *	[podlove-contributors]
	 * 
	 * @return string
	 */
	public function podlove_contributors($attributes) {
		$this->podlove_contributor_list($attributes);
	}

	/**
	 * Parameters:
	 *
	 *	preset      - One of 'table', 'list', 'comma separated'. Default: 'table'
	 *	title       - Optional table header title. Default: none
	 *	avatars     - One of 'yes', 'no'. Display avatars. Default: 'yes'
	 *	role        - Filter lists by role. Default: 'all'
	 *	roles       - One of 'yes', 'no'. Display role. Default: 'no' 
	 *	group       - Filter lists by group. Default: 'all'
	 *	groups      - One of 'yes', 'no'. Display group. Default: 'no' 
	 *	donations   - One of 'yes', 'no'. Display donation column. Default: 'no'
	 *	flattr      - One of 'yes', 'no'. Display Flattr column. Default: 'yes'
	 *	              Links contributor name to the service if available. Default: 'none'
	 * 
	 * Examples:
	 *
	 *	[podlove-contributor-list] / [podlove-podcast-contributor-list]
	 * 
	 * @return string
	 */
	public function podlove_contributor_list($attributes)
	{
		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge(self::$shortcode_defaults, $attributes);

		switch ($this->settings['preset']) {
			case 'comma separated':
				$file = 'contributor-comma-separated.twig';
				break;
			case 'list':
				$file = 'contributor-list.twig';
				break;
			case 'table':
				$file = 'contributor-table.twig';
				break;
			default:
				$file = 'contributor-table.twig';
				break;
		}

		$tpl = \Podlove\load_template( trailingslashit(dirname(__FILE__)) . 'templates/' . $file);
		return \Podlove\Template\TwigFilter::apply_to_html($tpl, $this->settings);
	}

	public function podlove_podcast_contributor_list($attributes)
	{
		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge(self::$shortcode_defaults, $attributes);

		$tpl = \Podlove\load_template( trailingslashit(dirname(__FILE__)) . 'templates/podcast-contributor-table.twig');
		return \Podlove\Template\TwigFilter::apply_to_html($tpl, $this->settings);
	}
}
