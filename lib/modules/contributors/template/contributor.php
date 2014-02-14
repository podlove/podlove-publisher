<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;
use Podlove\Template\Episode;

/**
 * Contributor Template Wrapper
 *
 * Requires the "Contributor" module.
 *
 * @templatetag contributor
 */
class Contributor extends Wrapper {

	private $contributor;
	private $contribution;

	public function __construct($contributor, $contribution = null) {
		$this->contributor = $contributor;
		$this->contribution = $contribution;
	}

	protected function getExtraFilterArgs() {
		return array($this->contributor, $this->contribution);
	}

	// /////////
	// Accessors
	// /////////

	/**
	 * Is the contributor public?
	 * 
	 * @accessor
	 */
	public function visible() {
		return (bool) $this->contributor->visibility;
	}

	/**
	 * Name
	 *
	 * Public name of the contributor. If no public name is set,
	 * it defaults to the real name.
	 * 
	 * @accessor
	 */
	public function name() {
		return $this->contributor->getName();
	}

	/**
	 * Real name
	 *
	 * You should use `contributor.name` as display name.
	 * 
	 * @accessor
	 */
	public function realname() {
		return $this->contributor->realname;
	}

	/**
	 * Nickname
	 * 
	 * @accessor
	 */
	public function nickname() {
		return $this->contributor->nickname;
	}

	/**
	 * Public name
	 *
	 * You should use `contributor.name` as display name.
	 * 
	 * @accessor
	 */
	public function publicname() {
		return $this->contributor->publicname;
	}

	/**
	 * Contribution role
	 *
	 * A role is only available for `episode.contributors` and `podcast.contributors`,
	 * not if you access the global `contributors` directly.
	 * 
	 * @accessor
	 */
	public function role() {
		return ($this->contribution) ? $this->contribution->getRole()->title : '';
	}

	/**
	 * Contribution group
	 *
	 * A group is only available for `episode.contributors` and `podcast.contributors`,
	 * not if you access the global `contributors` directly.
	 * 
	 * @accessor
	 */
	public function group() {
		return ($this->contribution) ? $this->contribution->getGroup()->title : '';
	}

	/**
	 * Avatar image
	 *
	 * Dimensions default to 50x50px.
	 * Change it via parameter: `contributor.avatar(32)`
	 * 
	 * @accessor
	 */
	public function avatar($size = 50) {
		return $this->contributor->getAvatar($size);
	}

	/**
	 * Avatar image URL
	 *
	 * Dimensions default to 50x50px.
	 * Change it via parameter: `contributor.avatarUrl(32)`
	 * 
	 * @accessor
	 */
	public function avatarUrl($size = 50) {
		return $this->contributor->getAvatarUrl($size);
	}

	/**
	 * Website
	 * 
	 * @accessor
	 */
	public function website() {
		return $this->contributor->www;
	}

	/**
	 * URI (unique identifier)
	 * 
	 * @accessor
	 */
	public function uri() {
		return $this->contributor->guid;
	}

	/**
	 * Facebook name
	 * 
	 * @accessor
	 */
	public function facebook() {
		return $this->contributor->facebook;
	}

	/**
	 * Twitter name
	 * 
	 * @accessor
	 */
	public function twitter() {
		return $this->contributor->twitter;
	}

	/**
	 * ADN name
	 * 
	 * @accessor
	 */
	public function adn() {
		return $this->contributor->adn;
	}

	/**
	 * Email address for public use
	 * 
	 * @accessor
	 */
	public function publicemail() {
		return $this->contributor->publicemail;
	}

	/**
	 * Email address for internal use
	 * 
	 * @accessor
	 */
	public function contactemail() {
		return $this->contributor->contactemail;
	}

	/**
	 * Flattr username
	 * 
	 * @accessor
	 */
	public function flattr() {
		return $this->contributor->flattr;
	}

	/**
	 * PayPal button id
	 * 
	 * @accessor
	 */
	public function paypal() {
		return $this->contributor->paypal;
	}

	/**
	 * Bitcoin address
	 * 
	 * @accessor
	 */
	public function bitcoin() {
		return $this->contributor->bitcoin;
	}

	/**
	 * Litecoin address
	 * 
	 * @accessor
	 */
	public function litecoin() {
		return $this->contributor->litecoin;
	}

	/**
	 * Amazon wishlist URL
	 * 
	 * @accessor
	 */
	public function amazon_wishlist() {
		return $this->contributor->amazon;
	}

	/**
	 * Episodes with this contributor
	 *
	 * @see  episode
	 * @accessor
	 */
	public function episodes() {
		$episodes = array();

		foreach ($this->contributor->getContributions() as $contribution) {
			$episode = $contribution->getEpisode();
			if ($episode && !in_array($episode->id, array_keys($episodes))) {
				$episodes[$episode->id] = new Episode($episode);
			}
		}

		return array_values($episodes);
	}

}