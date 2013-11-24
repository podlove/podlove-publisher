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

	/**
	 * Shortcode settings.
	 */
	private $settings = array();

	public function __construct() {
		add_shortcode( 'podlove-contributors', array( $this, 'shortcode') );
	}

		
	/**
	 * Parameters:
	 *
	 *	style       - One of 'table', 'list'. Default: 'table'
	 *	id          - Specify a contributor id to display a specific contributor avatar.
	 *	avatars     - One of 'yes', 'no'. Display avatars in list views or not. Default: 'yes'
	 *	donations   - One of 'yes', 'no'. Display flattr column in list view or not. Default: 'no'
	 *	avatarsize  - Specify avatar size in pixel for single contributors. Default: 50
	 *	align       - One of 'left', 'right', 'none'. Align contributor. Default: none
	 *	caption     - Optional caption for contributor avatars.
	 *	linkto      - One of 'none', 'publicemail', 'www', 'adn', 'twitter', 'facebook', 'amazonwishlist'.
	 *	              Links contributor name to the service if available. Default: 'none'
	 *	role        - Filter lists by role. Default: 'all'
	 * 
	 * Examples:
	 *
	 *	[podlove-contributors]
	 * 
	 * @todo  ShowContributions
	 * 
	 * @return string
	 */
	public function shortcode($attributes)
	{
		$defaults = array(
			'style' => 'table',
			'id' => null,
			'avatarsize' => 50,
			'align' => 'none',
			'avatars' => 'yes',
			'donations' => 'no',
			'linkto' => 'none',
			'role' => 'all'
		);

		if (!is_array($attributes))
			$attributes = array();

		$this->settings = array_merge($defaults, $attributes);

		if ($this->settings['id'] !== null)
			return $this->renderSingleContributor($this->settings['id']);
		else
			return $this->renderListOfContributors();
	}

	private function renderSingleContributor($contributor_id)
	{
		$contributor = Contributor::find_one_by_slug($contributor_id);

		if (!$contributor)
			return "";

		// determine alignment
		$alignclass = '';
		
		if ($this->settings['align'] == 'left')
			$alignclass = 'alignleft';

		if ($this->settings['align'] == 'right')
			$alignclass = 'alignright';

		$avatar = $contributor->getAvatar($this->settings['avatarsize']);
		$avatar = $this->wrapWithLink($contributor, $avatar);

		return '<div class="wp-caption ' . $alignclass . '" style="width: ' . $this->settings['avatarsize'] . 'px">
				' . $avatar . '
			<p class="wp-caption-text">' . $this->settings['caption'] . '</p>
		</div>';
	}

	/**
	 * Maybe link text to named service.
	 */
	private function wrapWithLink($contributor, $linktext)
	{
		$service = $this->getService($this->settings['linkto']);

		if (!$service || !$contributor->{$service['key']})
			return $linktext;

		return sprintf('<a href="%s">%s</a>',
			sprintf($service['url_template'], $contributor->{$service['key']}),
			$linktext
		);
	}

	private function renderListOfContributors() {
		// fetch contributions
		if ($episode = Model\Episode::get_current()) {
			$this->contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('WHERE `episode_id` = "' . $episode->id . '" ORDER BY `position` ASC');
		} else {
			$this->contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::all('GROUP BY contributor_id ORDER BY `position` ASC');
		}

		if ($this->settings['role'] != 'all') {
			$this->contributions = array_filter($this->contributions, function($c) {
				return strtolower($this->settings['role']) == $c->getRole()->slug;
			});
		}

		if (count($this->contributions) == 0)
			return "";
		
		return $this->getFlattrScript()
			 . $this->renderByStyle($this->settings['style']);
	}

	private function renderByStyle($style)
	{
		switch ($style) {
			case 'list':
				return $this->renderAsList();
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
			$list[] = '<span class="contributor">'
			     . ($this->settings['avatars'] == 'yes' ? $contributor->getAvatar(18) . ' ' : '')
			     . $this->wrapWithLink($contributor, $contributor->publicname)
			     . '</span>';
		}

		$html = '<span class="podlove-contributors">';
		$html.= implode(', ', $list);
		$html.= '</span>';

		return $html;
	}

	private function renderAsTable() {

		$donations = $this->settings['donations'] == 'yes' ? '<th>Donations</th>' : '';

		$before = <<<EOD
<table class="contributors_table">
	<thead>
		<tr>
			<th>Contributor</th>
			<th>Contact/Social</th>
			$donations
		</tr>
	<thead>
	<tbody>
EOD;

		$after = <<<EOD
	</tbody>
</table>
EOD;

		$body = "";
		foreach ($this->contributions as $contribution) {
			$contributor = $contribution->getContributor();
			$body .= "<tr>";
			$body .= "  <td>" . ($this->settings['avatars'] == 'yes' ? $contributor->getAvatar(18) . ' ' : '') . $this->wrapWithLink($contributor, $contributor->publicname) . "</td>";
			$body .= "  <td>" . $this->getSocialButtons($contributor) . "</td>";

			if ($this->settings['donations'] == 'yes')
				$body .= "  <td>" . $this->getDonationButton($contributor) . "</td>";

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

	private function getDonationButton($contributor)
	{
		if (!$contributor->flattr)
			return "";

		return "<a
			class=\"FlattrButton\"
			style=\"display:none;\"
    		title=\"Flattr {$contributor->publicname}\"
    		rel=\"flattr;button:compact;popout:0\"
    		href=\"https://flattr.com/profile/{$contributor->flattr}\">
		    	Flattr {$contributor->publicname}
		</a>";
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
