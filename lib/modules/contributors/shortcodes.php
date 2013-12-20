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
	 *	group       - Filter lists by group. Default: 'all'
	 *	donations   - One of 'yes', 'no'. Display donation column. Default: 'no'
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
			'group'		=> 'all',
			'donations' => 'no',
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
		$title = $this->settings['title'];
		$id = $this->getId();

		$before = <<<EOD
<table id="$id" class="podlove-contributors-table">
	<thead>
		<tr>
			<th colspan="3">$title</th>
			$donations
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

			// name and role
			$body .= '<td class="title_cell">';
			$body .= $this->wrapWithLink($contributor, $contributor->publicname);

			if ($role = $contribution->getRole())
				$body .= '<br><em>' . $role->title . '</em>';

			$body .= "</td>";

			// social
			$body .= '<td class="social_cell">' . $this->getSocialButtons($contributor) . "</td>";

			// donations
			if ($this->settings['donations'] == 'yes')
				$body .= '<td class="docation_cell"><ul class="podlove-donations-list">'
			    . ( is_page() ? $this->getFlattrButton( $contributor ) : $this->getRelatedFlattrButton( $contributor, get_the_ID() ) )
			    . $this->getXcoinButton($contributor, 'bitcoin')
			    . $this->getXcoinButton($contributor, 'litecoin')
			    . $this->getPayPalButton($contributor) . "</ul></td>";

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
					'<a href="%s" target="_blank" class="contributor-contact %s" title="%s"><i class="%s"></i></a>',
					sprintf($service['url_template'], $contributor->{$service['key']}),
					$service['key'],
					$service['title'],
					$service['icon']
				);
			}
		}

		return $html;
	}

	private function getRelatedFlattrButton($contributor, $postid)
	{
		if (!$contributor->flattr)
			return "";

		return "<li><a
			class=\"FlattrButton\"
			style=\"display:none;\"
    		title=\"{$contributor->publicname}@" . get_the_title( $postid ) . "\"
    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
    		href=\"".get_permalink( $postid )."#podlove-contributor={$contributor->slug}\">
		    	Flattr {$contributor->publicname}@" . get_the_title( $postid ) . "
		</a></li>";
	}

	private function getFlattrButton($contributor)
	{
		if (!$contributor->flattr)
			return "";

		return "<li><a
			class=\"FlattrButton\"
			style=\"display:none;\"
    		title=\"Flattr {$contributor->publicname}\"
    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
    		href=\"https://flattr.com/profile/{$contributor->flattr}\">
		    	Flattr {$contributor->publicname}
		</a></li>";
	}

	private function getPayPalButton($contributor)
	{
		if (!$contributor->paypal)
			return "";

		return "<li><a
			class=\"PayPalButton\"
    		title=\"Donate with PayPal\"
    		href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id={$contributor->paypal}\">
    		<i class=\"podlove-icon-paypal\"></i>
		</a></li>";
	}

	private function getXcoinButton($contributor, $currency)
	{
		if (!$contributor->$currency)
			return "";

		return '<li><a href="' . $currency . ':' . $contributor->$currency . '">
						<img src="' . \Podlove\PLUGIN_URL  . '/lib/modules/contributors/images/' . $currency . '.png" />
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
				'icon' => 'podlove-icon-mail'
			),
			array(
				'key' => 'www',
				'url_template' => '%s',
				'title' => 'Homepage',
				'icon' => 'podlove-icon-house'
			),
			array(
				'key' => 'adn',
				'url_template' => 'http://app.net/%s',
				'title' => 'ADN',
				'icon' => 'podlove-icon-appdotnet'
			),
			array(
				'key' => 'twitter',
				'url_template' => 'http://twitter.com/%s',
				'title' => 'Twitter',
				'icon' => 'podlove-icon-twitter'
			),
			array(
				'key' => 'facebook',
				'url_template' => '%s',
				'title' => 'Facebook',
				'icon' => 'podlove-icon-facebook'
			),
			array(
				'key' => 'amazonwishlist',
				'url_template' => '%s',
				'title' => 'Wishlist',
				'icon' => 'podlove-icon-cart'
			),
		);
	}

	private function getService($service){
		$filtered = array_filter($this->getServices(), function($s) use ($service) {
			return $s['key'] == $service;
		});

		return count($filtered) ? current($filtered) : null;
	}

}
