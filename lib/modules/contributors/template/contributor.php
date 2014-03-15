<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;
use Podlove\Template\Episode;
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
	 * @accessor
	 */
	public function avatar($size = 50) {
		// return $this->contributor->getAvatar($size);
		return new Avatar($this->contributor, $size);
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
	 * Flattr URL.
	 *
	 * When on a WordPress page, it returns the URL for the person. Otherwise a
	 * URL for that person _in this specific episode_ is generated.
	 * 
	 * @accessor
	 */
	public function flattr_url() {

		if (is_page()) {
			return "https://flattr.com/profile/" . $this->flattr();
		} else {
			return get_permalink( get_the_ID() ) . "#" . md5( $this->contributor->id . '-' . $this->flattr() );
		}
	}

	/**
	 * Episodes with this contributor
	 *
	 * @see  episode
	 * @accessor
	 */
	public function episodes() {
		global $wpdb;

		$sql = '
			SELECT
				ec.episode_id
			FROM
				' . EpisodeContribution::table_name() . ' ec
				INNER JOIN ' . \Podlove\Model\Episode::table_name() . ' e ON e.id = ec.episode_id
				INNER JOIN ' . $wpdb->posts . ' p ON p.ID = e.post_id
			WHERE
				ec.contributor_id = %d
			GROUP BY
				ec.episode_id
			ORDER BY
				p.post_date DESC
		';

		$episode_ids = $wpdb->get_col(
			$wpdb->prepare($sql, $this->contributor->id)
		);

		$episodes = array();
		foreach ($episode_ids as $episode_id) {
			$episodes[$episode_id] = new Episode(\Podlove\Model\Episode::find_one_by_id($episode_id));
		}

		return array_values($episodes);
	}

}