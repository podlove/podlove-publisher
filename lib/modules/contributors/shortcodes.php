<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Model;

/**
 * Register all contributors shortcodes.
 */
class Shortcodes {

	/**
	 * List of contributions to be rendered.
	 */
	private $contributions = array();

	private $id;

	/**
	 * Shortcode settings.
	 */
	private $settings = array();

	public function __construct() {
		// legacy shortcode. deprecate?
		add_shortcode( 'podlove-contributors', array( $this, 'podlove_contributors') );
		// display a table/list of contributors
		add_shortcode( 'podlove-contributor-list', array( $this, 'podlove_contributor_list') );
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
		
		$defaults = array(
			'preset'  => 'comma separated',
			'linkto'  => 'none',
			'role'    => 'all',
			'roles'		=> 'no',
			'group'		=> 'all',
			'groups'	=> 'no',
			'avatars' => 'yes',
		);

		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge($defaults, $attributes);

		return $this->renderListOfContributors();
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
	 *	[podlove-contributor-list]
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

		$this->id = null; // reset id

		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge($defaults, $attributes);

		return $this->renderListOfContributors();
	}

	private function getId() {
		if ($this->id) {
			return $this->id;
		} else {
			$this->id = 'contrib_' . substr(md5(mt_rand()),0,8);
			return $this->id;
		}
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

	private function renderListOfContributors() {

		$this->id = null; // reset id

		// fetch contributions
		if ($episode = Model\Episode::get_current()) {
			$this->contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('WHERE `episode_id` = "' . $episode->id . '" ORDER BY `position` ASC');
		} else {
			$this->contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('GROUP BY contributor_id ORDER BY `position` ASC');
		}

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

		if (count($this->contributions) == 0)
			return "";
		
		return $this->getFlattrScript()
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
			     . ' <span class="name">' . $this->wrapWithLink($contributor, $contributor->publicname) . '</span>'
			     . '</li>';
		}

		$html = '<ul id="' . $this->getId() . '" class="podlove-contributors">';
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
			     . ' <span class="name">' . $this->wrapWithLink($contributor, $contributor->publicname) . '</span>'
			     . '</span>';
		}

		$html = '<span id="' . $this->getId() . '" class="podlove-contributors">';
		$html.= implode(", ", $list);
		$html.= '</span>';

		return $html;
	}

	private function renderAsTable() {

		$donations = $this->settings['donations'] == 'yes' ? '<th></th>' : '';
		$flattr = $this->settings['flattr'] == 'yes' ? '<th></th>' : '';
		$title = $this->settings['title'];
		$id = $this->getId();

		$before = <<<EOD
<table id="$id" class="podlove-contributors-table">
	<thead>
		<tr>
			<th colspan="3">$title</th>
			$donations
			$flattr
		</tr>
	<thead>
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

.podlove-contributors-table .social_cell {
	font-size: 1.7em;
}

.podlove-contributors-table .social_cell a {
	margin-right: 4px
}
</style>
EOD;

		$body = "";
		foreach ($this->contributions as $contribution) {
			$contributor = $contribution->getContributor();
			$body .= "<tr>";

			// avatar
			$body .= '<td class="avatar_cell">';
			$body .= ($this->settings['avatars'] == 'yes' ? $contributor->getAvatar(50) . ' ' : '');
			$body .= "</td>";

			// name, role and group
			$body .= '<td class="title_cell">';
			$body .= $this->wrapWithLink($contributor, $contributor->publicname);

			if ($this->settings['roles'] == 'yes' && $role = $contribution->getRole())
				$body .= '<br /><em>' . $role->title . '</em>';

			if ($this->settings['groups'] == 'yes' && $group = $contribution->getGroup())
				$body .= '<br /><em>' . $group->title . '</em>';

			$body .= "</td>";

			// social
			$body .= '<td class="social_cell">' . $this->getSocialButtons($contributor) . "</td>";

			// donations
			if ($this->settings['donations'] == 'yes')
				$body .= '<td class="docation_cell"><ul class="podlove-donations-list">'
			    . $this->getXcoinButton($contributor, 'bitcoin')
			    . $this->getXcoinButton($contributor, 'litecoin')
			    . $this->getPayPalButton($contributor)
			    . $this->getAmazonWishlistButton($contributor)
			    . "</ul></td>";

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
		$html = '<ul class="podlove-social-list">';
		foreach ($this->getServices() as $service) {
			if ($contributor->{$service['key']}) {
				$html .= sprintf(
					'<li><a href="%1$s" target="_blank" title="%2$s%3$s %4$s">
						<img src="%5$s/lib/modules/contributors/images/icons/%6$s" class="podlove-contributor-button" 
						alt="%2$s%3$s %4$s" />
					</a></li>',
					sprintf($service['url_template'], $contributor->{$service['key']}),
					( $contributor->publicname == "" ? $contributor->nickname : $contributor->publicname ),
					$service['copula'],
					$service['title'],
					\Podlove\PLUGIN_URL,
					$service['icon']
				);
			}
		}
		$html .= '</ul>';

		return $html;
	}

	private function getAmazonWishlistButton($contributor)
	{
		if (!$contributor->amazonwishlist)
			return "";

		return "<li><a
			target=\"_blank\"
    		title=\"Support {$contributor->publicname} by buying things from an Amazon Wishlist\"
    		href=\"{$contributor->amazonwishlist}\">
    		<img src=\"" . \Podlove\PLUGIN_URL  . "/lib/modules/contributors/images/icons/amazonwishlist-128.png\" class=\"podlove-contributor-button\" 
    		alt=\"Support {$contributor->publicname} by buying things from an Amazon Wishlist\" />
		</a></li>";
	}

	private function getRelatedFlattrButton($contributor, $postid)
	{
		if (!$contributor->flattr)
			return "";

		return "<a 
		    target=\"_blank\"
			class=\"FlattrButton\"
			style=\"display:none;\"
    		title=\"{$contributor->publicname}@" . get_the_title( $postid ) . "\"
    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
    		href=\"".get_permalink( $postid )."#podlove-contributor={$contributor->slug}\">
		    	Flattr {$contributor->publicname}@" . get_the_title( $postid ) . "
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
    		title=\"Flattr {$contributor->publicname}\"
    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
    		href=\"https://flattr.com/profile/{$contributor->flattr}\">
		    	Flattr {$contributor->publicname}
		</a>";
	}

	private function getPayPalButton($contributor)
	{
		if (!$contributor->paypal)
			return "";

		return "<li><a
			target=\"_blank\"
			class=\"PayPalButton\"
    		title=\"Support {$contributor->publicname} by donating with PayPal\"
    		href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id={$contributor->paypal}\">
    		<img src=\"" . \Podlove\PLUGIN_URL  . "/lib/modules/contributors/images/icons/paypal-128.png\" class=\"podlove-contributor-button\" 
    		alt=\"Support {$contributor->publicname} by donating with PayPal\" />
		</a></li>";
	}

	private function getXcoinButton($contributor, $currency)
	{
		if (!$contributor->$currency)
			return "";

		return '<li><a href="' . $currency . ':' . $contributor->$currency . '"
					 title="Support ' . $contributor->publicname . ' by donating with ' . ucfirst($currency) .'">
						<img src="' . \Podlove\PLUGIN_URL  . '/lib/modules/contributors/images/icons/' . $currency . '-128.png" class="podlove-contributor-button" 
						alt="Support ' . $contributor->publicname . ' by donating with ' . ucfirst($currency) .'" />
					</a>
				</li>';
	}

	private function getFlattrScript() {
		return "<script type=\"text/javascript\">\n
			/* <![CDATA[ */
		    (function() {
  		     var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
  		     s.type = 'text/javascript';
   		     s.async = true;
    		    s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
    		    t.parentNode.insertBefore(s, t);
   			 })();
			/* ]]> */</script>\n";
	}

	private function getServices() {
		return array(
			array(
				'key' => 'publicemail',
				'url_template' => 'mailto:%s',
				'title' => 'E-Mail',
				'copula' => 's',
				'icon' => 'email-128.png'
			),
			array(
				'key' => 'www',
				'url_template' => '%s',
				'title' => 'Homepage',
				'copula' => 's',
				'icon' => 'www-128.png'
			),
			array(
				'key' => 'adn',
				'url_template' => 'http://app.net/%s',
				'title' => 'ADN',
				'copula' => ' on',
				'icon' => 'adn-128.png'
			),
			array(
				'key' => 'twitter',
				'url_template' => 'http://twitter.com/%s',
				'title' => 'Twitter',
				'copula' => ' on',
				'icon' => 'twitter-128.png'
			),
			array(
				'key' => 'facebook',
				'url_template' => '%s',
				'title' => 'Facebook',
				'copula' => ' on',
				'icon' => 'facebook-128.png'
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
