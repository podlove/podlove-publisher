<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;
use Podlove\Template\Episode;
use Podlove\Template\Image;
use Podlove\Modules\Contributors\Model\EpisodeContribution;

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
	 * ID
	 * 
	 * @accessor
	 */
	public function id() {
		return $this->contributor->slug;
	}

	/**
	 * URI
	 * 
	 * @accessor
	 */
	public function uri() {
		return $this->contributor->guid;
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
		if ($role = $this->contribution->getRole())
			return $role->title;
		else
			return '';
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
		if ($group = $this->contribution->getGroup())
			return $group->title;
		else
			return '';
	}

	/**
	 * Contribution comment
	 * 
	 * @accessor
	 */
	public function comment() {
		return ($this->contribution) ? $this->contribution->comment : '';
	}

	/**
	 * Avatar image
	 *
	 * Dimensions default to 50x50px.
	 * Change it via parameter: `contributor.avatar(32)`
	 *
	 * To render an HTML image tag:
	 * `{% include '@contributors/avatar.twig' with {'avatar': contributor.avatar} only %}`
	 * or
	 * `{% include '@contributors/avatar.twig' with {'avatar': contributor.avatar, 'size': 150} only %}`
	 * 
	 * @deprecated use contributor.image instead
	 * @accessor
	 */
	public function avatar($size = 50) {
		return new Avatar($this->contributor, $size);
	}

	public function image() {
		return new Image($this->contributor->avatar());
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
	 * Flattr URL.
	 *
	 * When on a WordPress page, it returns the URL for the person. Otherwise a
	 * URL for that person _in this specific episode_ is generated.
	 * 
	 * @accessor
	 */
	public function flattr_url() {
		return $this->contributor->with_blog_scope(function() {
			if (is_page()) {
				return "https://flattr.com/profile/" . $this->flattr();
			} else {
				return get_permalink( get_the_ID() ) . "#" . md5( $this->contributor->id . '-' . $this->flattr() );
			}
		});
	}

	/**
	 * Episodes with this contributor
	 * 
	 * Filter and order episodes with parameters:
	 * 
	 * - group: Filter by contribution group. Default: ''.
	 * - role: Filter by contribution role. Default: ''.
	 * - post_status: Publication status of the post. Defaults to 'publish'
	 * - order: Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'.
	 *   - 'ASC' - ascending order from lowest to highest values (1, 2, 3; a, b, c).
	 *   - 'DESC' - descending order from highest to lowest values (3, 2, 1; c, b, a).
	 * - orderby: Sort retrieved episodes by parameter. Defaults to 'publicationDate'.
	 *   - 'publicationDate' - Order by publication date.
	 *   - 'recordingDate' - Order by recording date.
	 *   - 'title' - Order by title.
	 *   - 'slug' - Order by episode slug.
	 *	 - 'limit' - Limit the number of returned episodes.
	 *
	 * @see  episode
	 * @accessor
	 */
	public function episodes($args = []) {
		return array_map(function($e) {
			return new Episode($e);
		}, $this->contributor->episodes($args));
	}

}