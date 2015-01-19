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
		'donations' => 'yes',
		'flattr'    => 'yes',
		'title'     => '',
		'groupby'   => 'none'
	);

	/**
	 * Shortcode settings.
	 */
	private $settings = array();

	public function __construct() {
		// display a table/list of episode contributors
		add_shortcode( 'podlove-episode-contributor-list', array( $this, 'podlove_contributor_list') );
		// display a table/list of podcast contributors
		add_shortcode( 'podlove-podcast-contributor-list', array( $this, 'podlove_podcast_contributor_list') );
		// display a table/list of all contributors
		add_shortcode( 'podlove-global-contributor-list', array( $this, 'global_contributor_list') );
	}

	public function global_contributor_list($attributes)
	{
		if (!is_array($attributes))
			$attributes = array();

		return \Podlove\Template\TwigFilter::apply_to_html('@contributors/podcast-contributor-list.twig', $attributes);
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
	 *	groupby     - Set to 'group' to group contributors by their contributor group. Default: 'none'
	 *	donations   - One of 'yes', 'no'. Display donation column. Default: 'no'
	 *	flattr      - One of 'yes', 'no'. Display Flattr column. Default: 'yes'
	 *	              Links contributor name to the service if available. Default: 'none'
	 * 
	 * Examples:
	 *
	 *	[podlove-episode-contributor-list]
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
				$file = '@contributors/contributor-comma-separated.twig';
				break;
			case 'list':
				$file = '@contributors/contributor-list.twig';
				break;
			case 'table':
				$file = '@contributors/contributor-table.twig';
				break;
			default:
				$file = '@contributors/contributor-table.twig';
				break;
		}

		return \Podlove\Template\TwigFilter::apply_to_html($file, $this->settings);
	}

	public function podlove_podcast_contributor_list($attributes)
	{
		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge(self::$shortcode_defaults, $attributes);

		return \Podlove\Template\TwigFilter::apply_to_html('@contributors/podcast-contributor-table.twig', $this->settings);
	}
}
