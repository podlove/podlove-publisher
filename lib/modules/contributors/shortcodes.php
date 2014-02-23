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

	public function global_contributor_list($atts) {

		if (isset($atts['group'])) {
			$contributors = Contributor::byGroup($atts['group']);
		} else {
			$contributors = Contributor::all();
		}

		$atts['contributors'] = array_map(function($contributor) {
			return new \Podlove\Modules\Contributors\Template\Contributor($contributor);
		}, $contributors);

		$tpl = \Podlove\load_template( trailingslashit(dirname(__FILE__)) . 'templates/contributor-list.twig');
		return \Podlove\Template\TwigFilter::apply_to_html($tpl, $atts);
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
	 *	linkto      - One of 'none', 'publicemail', 'www', 'adn', 'twitter', 'facebook', 'amazonwishlist'.
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
		$defaults = array(
			'preset'    => 'table',
			'avatars'   => 'yes',
			'role'      => 'all',
			'roles'		=> 'no',
			'group'		=> 'all',
			'groups'	=> 'no',
			'donations' => 'no',
			'flattr'    => 'yes',
			'linkto'    => 'none',
			'title'     => ''
		);

		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge($defaults, $attributes);

		$this->fetchContributions('episode');

		$this->settings['contributors'] = array_map(function($contribution) {
			return new \Podlove\Modules\Contributors\Template\Contributor($contribution->getContributor(), $contribution);
		}, $this->contributions);

		$tpl = \Podlove\load_template( trailingslashit(dirname(__FILE__)) . 'templates/contributor-table.twig');
		return \Podlove\Template\TwigFilter::apply_to_html($tpl, $this->settings);
	}

	public function podlove_podcast_contributor_list($attributes)
	{
		$defaults = array(
			'preset'    => 'table',
			'avatars'   => 'yes',
			'role'      => 'all',
			'roles'		=> 'no',
			'group'		=> 'all',
			'groups'	=> 'no',
			'donations' => 'no',
			'flattr'    => 'yes',
			'linkto'    => 'none',
			'title'     => ''
		);

		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge($defaults, $attributes);

		$this->fetchContributions('podcast');

		$this->settings['contributors'] = array_map(function($contribution) {
			return new \Podlove\Modules\Contributors\Template\Contributor($contribution->getContributor(), $contribution);
		}, $this->contributions);

		$tpl = \Podlove\load_template( trailingslashit(dirname(__FILE__)) . 'templates/contributor-table.twig');
		return \Podlove\Template\TwigFilter::apply_to_html($tpl, $this->settings);
	}

	/**
	 * Maybe link text to named service.
	 */
	private function wrapWithLink($contributor, $linktext)
	{
		$service = $this->getService($this->settings['linkto']);

		if (!$service || !$contributor->{$service['key']})
			return $linktext;

		return sprintf('<a href="%s" target="_blank">%s</a>',
			sprintf($service['url_template'], $contributor->{$service['key']}),
			$linktext
		);
	}

	private function fetchContributions($relation='episode') {
		// fetch contributors
		switch ( $relation ) {
			case 'episode' :
				if ($episode = Model\Episode::get_current()) {
					$this->contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('WHERE `episode_id` = "' . $episode->id . '" ORDER BY `position` ASC');
				} else {
					$this->contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('GROUP BY contributor_id ORDER BY `position` ASC');
				}
			break;
			case 'podcast' :
				$this->contributions = \Podlove\Modules\Contributors\Model\ShowContribution::all();
			break;
		}

		// Remove all contributions with missing contributors.
		$this->contributions = array_filter($this->contributions, function($c) {
			return (bool) $c->getContributor();
		});

		if ($this->settings['role'] != 'all') {
			$role = $this->settings['role'];
			$this->contributions = array_filter($this->contributions, function($c) use ($role) {
				return strtolower($role) == $c->getRole()->slug;
			});
		}

		if ($this->settings['group'] != 'all') {
			$group = $this->settings['group'];
			$this->contributions = array_filter($this->contributions, function($c) use ($group) {
				return strtolower($group) == $c->getGroup()->slug;
			});
		}
	}

	private function renderListOfContributors( $relation='episode' ) {

		$this->fetchContributions($relation);

		if (count($this->contributions) == 0)
			return "";
		
		return \Podlove\Flattr\getFlattrScript()
			 . $this->renderByStyle($this->settings['preset']);
	}

	private function renderByStyle($preset)
	{
		switch ($preset) {
			case 'list':
				return $this->renderAsList();
				break;
			case 'comma separated':
				return $this->renderAsCommaSeparated();
				break;
			case 'table': // table is default
			default:
				return $this->renderAsTable();

				break;
		}
	}

	private function renderAsList()
	{
		$list = array();
		foreach ($this->contributions as $contribution) {
			$contributor = $contribution->getContributor();
			$list[] = '<li>'
			     . (($this->settings['avatars'] == 'yes') ? '<span class="avatar">' . $contributor->getAvatar(18) . '</span>' : '')
			     . ' <span class="name">' . $this->wrapWithLink($contributor, $contributor->getName()) . '</span>'
			     . '</li>';
		}

		$html = '<ul class="podlove-contributors">';
		$html.= implode("\n\t", $list);
		$html.= '</ul>';

		return $html;
	}

	private function renderAsCommaSeparated()
	{
		$list = array();
		foreach ($this->contributions as $contribution) {
			$contributor = $contribution->getContributor();
			$list[] = '<span>'
			     . (($this->settings['avatars'] == 'yes') ? '<span class="avatar">' . $contributor->getAvatar(18) . '</span>' : '')
			     . ' <span class="name">' . $this->wrapWithLink($contributor, $contributor->getName()) . '</span>'
			     . '</span>';
		}

		$html = '<span class="podlove-contributors">';
		$html.= implode(", ", $list);
		$html.= '</span>';

		return $html;
	}

	private function renderAsTable() {

		$title = $this->settings['title'] == '' ? '' : '<caption>' . $this->settings['title'] . '</caption>';

		$before = <<<EOD
<table class="podlove-contributors-table">
	$title
	<tbody>
EOD;

		$after = <<<EOD
	</tbody>
</table>

<style type="text/css">
.podlove-contributors-table .avatar_cell {
	width: 60px;
}

.podlove-contributors-table .title_cell {
	line-height: 1em;
}

.podlove-contributors-table .social_cell a, .podlove-contributors-table .donation_cell a {
	margin-right: 4px;
	background: none;
	text-decoration: none;
}

.podlove-contributors-table .flattr_cell iframe {
	margin-bottom: 0px;
}

.podlove-contributors-table td {
	vertical-align: middle;
}
</style>
EOD;

		$body = "";
		foreach ($this->contributions as $contribution) {
			$contributor = $contribution->getContributor();

			if( !is_object( $contributor ) )
				continue;

			$body .= "<tr>";

			// avatar
			$body .= '<td class="avatar_cell">';
			$body .= ($this->settings['avatars'] == 'yes' ? $contributor->getAvatar(50) . ' ' : '');
			$body .= "</td>";

			// name and comment
			$body .= '<td class="title_cell">';
			$body .= $this->wrapWithLink($contributor, $contributor->getName());
			$body .= $contribution->comment == '' ? '' :'<br /><em>' . $contribution->comment . '</em>';
			$body .= '</td>';

			// group
			if ($this->settings['groups'] == 'yes' && $group = $contribution->getGroup())
				$body .= '<td>' . $group->title . '</td>';

			// role
			if ($this->settings['roles'] == 'yes' && $role = $contribution->getRole())
				$body .= '<td>' . $role->title . '</td>';

			// social
			$body .= '<td class="social_cell">' . $this->getSocialButtons($contributor) . "</td>";

			// donations
			if ($this->settings['donations'] == 'yes')
				$body .= '<td class="donation_cell">'
			    . $this->getXcoinButton($contributor, 'bitcoin')
			    . $this->getXcoinButton($contributor, 'litecoin')
			    . $this->getPayPalButton($contributor)
			    . $this->getAmazonWishlistButton($contributor)
			    . "</td>";

			// flattr
			if ($this->settings['flattr'] == 'yes')
				$body .= '<td class="flattr_cell">'
				. ( is_page() ? $this->getFlattrButton( $contributor ) : $this->getRelatedFlattrButton( $contributor, get_the_ID() ) )
				. "</td>";

			$body .= "</tr>";
		}

		return $before . $body . $after;
	}

	private function getSocialButtons($contributor)
	{
		$html = '';
		foreach ($this->getServices() as $service) {
			if ($contributor->{$service['key']}) {
				$html .= sprintf(
					'<a href="%1$s" target="_blank" title="%3$s">
						<img width="32" height="32" src="%4$s/lib/modules/contributors/images/icons/%5$s" class="podlove-contributor-button" 
						alt="%3$s" />
					</a>',
					sprintf($service['url_template'], $contributor->{$service['key']}),
					( $contributor->getName() == "" ? $contributor->nickname : $contributor->getName() ),
					$service['title'],
					\Podlove\PLUGIN_URL,
					$service['icon']
				);
			}
		}

		return $html;
	}

	private function getAmazonWishlistButton($contributor)
	{
		if (!$contributor->amazonwishlist)
			return "";

		return "<a
			target=\"_blank\"
    		title=\"Support {$contributor->getName()} by buying things from an Amazon Wishlist\"
    		href=\"{$contributor->amazonwishlist}\">
    		<img width=\"32\" height=\"32\" src=\"" . \Podlove\PLUGIN_URL  . "/lib/modules/contributors/images/icons/amazonwishlist-128.png\" class=\"podlove-contributor-button\" 
    		alt=\"" . sprintf( __('Support %s by buying things from an Amazon Wishlist'),  $contributor->getName() ) . "\" />
		</a>";
	}

	private function getRelatedFlattrButton($contributor, $postid)
	{
		if (!$contributor->flattr)
			return "";

		return "<a 
		    target=\"_blank\"
			class=\"FlattrButton\"
			style=\"display:none;\"
    		title=\"{$contributor->getName()}@" . get_the_title( $postid ) . "\"
    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
    		href=\"".get_permalink( $postid )."#" . md5( $contributor->id . '-' .$contributor->flattr ) . "\">
		    	Flattr {$contributor->getName()}@" . get_the_title( $postid ) . "
		</a>";
	}

	private function getFlattrButton($contributor)
	{
		if (!$contributor->flattr)
			return "";

		return "<a 
		    target=\"_blank\"
			class=\"FlattrButton\"
			style=\"display:none;\"
    		title=\"Flattr {$contributor->getName()}\"
    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
    		href=\"https://flattr.com/profile/{$contributor->flattr}\">
		    	Flattr {$contributor->getName()}
		</a>";
	}

	private function getPayPalButton($contributor)
	{
		if (!$contributor->paypal)
			return "";

		return "<a
			target=\"_blank\"
			class=\"PayPalButton\"
    		title=\"Support {$contributor->getName()} by donating with PayPal\"
    		href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id={$contributor->paypal}\">
    		<img width=\"32\" height=\"32\" src=\"" . \Podlove\PLUGIN_URL  . "/lib/modules/contributors/images/icons/paypal-128.png\" class=\"podlove-contributor-button\" 
    		alt=\"" . sprintf( __('Support %s by donating with PayPal'), $contributor->getName() ) ."\" />
		</a>";
	}

	private function getXcoinButton($contributor, $currency)
	{
		if (!$contributor->$currency)
			return "";

		return '<a href="' . $currency . ':' . $contributor->$currency . '"
					 title="Support ' . $contributor->getName() . ' by donating with ' . ucfirst($currency) .'">
						<img width="32" height="32" src="' . \Podlove\PLUGIN_URL  . '/lib/modules/contributors/images/icons/' . $currency . '-128.png" class="podlove-contributor-button" 
						alt="' . sprintf( __('Support %s by donating with %s'), $contributor->getName(), ucfirst($currency) ) . '" />
					</a>
				';
	}

	private function getServices() {
		return array(
			array(
				'key' => 'publicemail',
				'url_template' => 'mailto:%s',
				'title' => 'E-Mail',
				'icon' => 'email-128.png'
			),
			array(
				'key' => 'www',
				'url_template' => '%s',
				'title' => 'Homepage',
				'icon' => 'www-128.png'
			),
			array(
				'key' => 'adn',
				'url_template' => 'http://app.net/%s',
				'title' => 'App.net',
				'icon' => 'adn-128.png'
			),
			array(
				'key' => 'twitter',
				'url_template' => 'http://twitter.com/%s',
				'title' => 'Twitter',
				'icon' => 'twitter-128.png'
			),
			array(
				'key' => 'facebook',
				'url_template' => 'http://facebook.com/%s',
				'title' => 'Facebook',
				'icon' => 'facebook-128.png'
			),
			array(
				'key' => 'googleplus',
				'url_template' => '%s',
				'title' => 'Google+',
				'icon' => 'googleplus-128.png'
			)
		);
	}

	private function getService($service){
		$filtered = array_filter($this->getServices(), function($s) use ($service) {
			return $s['key'] == $service;
		});

		return count($filtered) ? current($filtered) : null;
	}

}
