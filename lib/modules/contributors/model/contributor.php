<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;
use \Podlove\Model\Episode;

class Contributor extends Base
{	
	use \Podlove\Model\KeepsBlogReferenceTrait;

	public function __construct() { $this->set_blog_id(); }

	public static function byGroup($groupSlug) {
		global $wpdb;

		$sql = '
			SELECT
				contributor_id
			FROM
				' . EpisodeContribution::table_name() . '
			WHERE
				group_id = (SELECT id FROM ' . ContributorGroup::table_name() . ' WHERE slug = %s)
			GROUP BY
				contributor_id
		';

		$contributor_ids = $wpdb->get_col(
			$wpdb->prepare($sql, $groupSlug)
		);

		if (is_array($contributor_ids) && count($contributor_ids) > 0) {
			return Contributor::all('WHERE id IN (' . implode(',', $contributor_ids) . ')');
		} else {
			return array();
		}
	}

	public static function byRole($roleSlug) {
		global $wpdb;

		$sql = '
			SELECT
				contributor_id
			FROM
				' . EpisodeContribution::table_name() . '
			WHERE
				role_id = (SELECT id FROM ' . ContributorRole::table_name() . ' WHERE slug = %s)
			GROUP BY
				contributor_id
		';

		$contributor_ids = $wpdb->get_col(
			$wpdb->prepare($sql, $roleSlug)
		);

		if (is_array($contributor_ids) && count($contributor_ids) > 0) {
			return Contributor::all('WHERE id IN (' . implode(',', $contributor_ids) . ')');
		} else {
			return array();
		}
	}

	public static function byGroupAndRole($groupSlug = null, $roleSlug = null) {
		global $wpdb;

		if (!$groupSlug && !$roleSlug)
			return self::all();

		if ($groupSlug && !$roleSlug)
			return self::byGroup($groupSlug);

		if (!$groupSlug && $roleSlug)
			return self::byRole($roleSlug);

		$sql = '
			SELECT
				contributor_id
			FROM
				' . EpisodeContribution::table_name() . '
			WHERE
				role_id = (SELECT id FROM ' . ContributorRole::table_name() . ' WHERE slug = %s)
				AND
				group_id = (SELECT id FROM ' . ContributorGroup::table_name() . ' WHERE slug = %s)
			GROUP BY
				contributor_id
		';

		$contributor_ids = $wpdb->get_col(
			$wpdb->prepare($sql, $roleSlug, $groupSlug)
		);

		if (is_array($contributor_ids) && count($contributor_ids) > 0) {
			return Contributor::all('WHERE id IN (' . implode(',', $contributor_ids) . ')');
		} else {
			return array();
		}
	}

	public function getName() {
		if ($this->publicname) {
			return $this->publicname;
		} else {
			if ($this->realname) {
				return $this->realname;
			} else {
				return $this->nickname;
			}
		}
	}

	public function getAvatar($size) {
		return '<img alt="avatar" src="' . $this->getAvatarUrl($size) . '" class="avatar avatar-' . $size . ' photo" height="' . $size . '" width="' . $size . '">';
	}

	public function getAvatarUrl($size) {

		if ($this->avatar)
			if (filter_var($this->avatar, FILTER_VALIDATE_EMAIL) === FALSE) {
				return $this->avatar;
			} else {
				return $this->getGravatarUrl($size, $this->avatar);
			}
		else
			return $this->getGravatarUrl($size);
	}

	public function getContributions() {
		return EpisodeContribution::find_all_by_contributor_id($this->id);
	}

	/**
	 * Episodes
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
	 */	
	public function episodes($args = []) {
		return $this->with_blog_scope(function() use ($args) {
			global $wpdb;

			$joins = "";

			if (isset($args['group']) && $args['group']) {
				$joins .= "INNER JOIN " . ContributorGroup::table_name() . " g ON g.id = ec.group_id AND g.slug = '" . esc_sql($args['group']) . "'";
			}

			if (isset($args['role']) && $args['role']) {
				$joins .= "INNER JOIN " . ContributorRole::table_name() . " r ON r.id = ec.role_id AND r.slug = '" . esc_sql($args['role']) . "'";
			}

			$where = "ec.contributor_id = " . (int) $this->id;

			if (isset($args['post_status']) && in_array($args['post_status'], get_post_stati())) {
				$where .= " AND p.post_status = '" . $args['post_status'] . "'";
			} else {
				$where .= " AND p.post_status = 'publish'";
			}

			// order
			$order_map = array(
				'publicationDate' => 'p.post_date',
				'recordingDate'   => 'e.recordingDate',
				'slug'            => 'e.slug',
				'title'           => 'p.post_title'
			);

			if (isset($args['orderby']) && isset($order_map[$args['orderby']])) {
				$orderby = $order_map[$args['orderby']];
			} else {
				$orderby = $order_map['publicationDate'];
			}

			if (isset($args['order'])) {
				$args['order'] = strtoupper($args['order']);
				if (in_array($args['order'], array('ASC', 'DESC'))) {
					$order = $args['order'];
				} else {
					$order = 'DESC';
				}
			} else {
				$order = 'DESC';
			}

			if (isset($args['limit'])) {
				$limit = ' LIMIT ' . (int) $args['limit'];
			} else {
				$limit = '';
			}

			$sql = '
				SELECT
					ec.episode_id
				FROM
					' . EpisodeContribution::table_name() . ' ec
					INNER JOIN ' . \Podlove\Model\Episode::table_name() . ' e ON e.id = ec.episode_id
					INNER JOIN ' . $wpdb->posts . ' p ON p.ID = e.post_id
					' . $joins . '
				WHERE ' . $where . '
				GROUP BY ec.episode_id
				ORDER BY ' . $orderby . ' ' . $order . 
				$limit
			;

			$episode_ids = $wpdb->get_col($sql);

			return array_map(function($episode_id) {
				return \Podlove\Model\Episode::find_one_by_id($episode_id);
			}, array_unique($episode_ids));
		});
	}

	public function getPublishedContributionCount() {
		global $wpdb;

		$sql = "
			SELECT
				COUNT(*)
			FROM
				" . EpisodeContribution::table_name() . " ec
				JOIN " . Episode::table_name() . " e ON ec.episode_id = e.id
				JOIN " . $wpdb->posts .  " p ON e.post_id = p.ID
			WHERE
				ec.contributor_id = %d
				AND p.post_status = 'publish'
		";

		$contributionCount = $wpdb->get_var(
			$wpdb->prepare($sql, $this->id)
		);

		return $contributionCount;
	}

	public function getShowContributions() {
		return ShowContribution::find_all_by_contributor_id($this->id);
	}

	public function getDefaultContributions() {
		return DefaultContribution::find_all_by_contributor_id($this->id);
	}

	/**
	 * Calculates episode contributions and stores them in contributioncount attribute
	 *
	 * Note: Counts only one contribution per episode even if one contributor has
	 * multiple contributions in an episode.
	 */
	public function calcContributioncount() {
		global $wpdb;

		$sql = "
			SELECT COUNT(*) FROM (
				SELECT
					ec.contributor_id, ec.episode_id
				FROM
					" . EpisodeContribution::table_name() . " ec
					JOIN " . Episode::table_name() . " e ON e.id = ec.episode_id
					JOIN " . $wpdb->posts . " p ON p.ID = e.post_id
				WHERE
					ec.contributor_id = %d
				GROUP BY
					ec.episode_id
			) x
		";

		$this->contributioncount = $wpdb->get_var($wpdb->prepare($sql, $this->id));
		$this->save();
	}

	/**
	 * Get Gravatar URL for a specified email address.
	 *
	 * Yes, I know there is get_avatar() but that returns the img tag and I need the URL.
	 *
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @source http://gravatar.com/site/implement/images/php/
	 */
	private function getGravatarUrl( $s = 80, $email = null ) {

		$email = $email ? $email : $this->publicemail;

		$url = 'https://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=mm&r=g";
		return $url;
	}	

	/**
	 * @override \Podlove\Model\Base::delete();
	 */
	public function delete() {
		foreach ( $this->getContributions() as $contribution )
			$contribution->delete();

		foreach ( $this->getShowContributions() as $contribution )
			$contribution->delete();

		foreach ( $this->getDefaultContributions() as $contribution )
			$contribution->delete();

		parent::delete();
	}
}

Contributor::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Contributor::property( 'slug', 'VARCHAR(255)' );
Contributor::property( 'gender', 'VARCHAR(255)' );
Contributor::property( 'organisation', 'TEXT' );
Contributor::property( 'department', 'TEXT' );
Contributor::property( 'jobtitle', 'TEXT' );
Contributor::property( 'avatar', 'TEXT' );
Contributor::property( 'flattr', 'VARCHAR(255)' );
Contributor::property( 'publicemail', 'TEXT' );				// DEPRECATED since 1.10.23
Contributor::property( 'privateemail', 'TEXT' );
Contributor::property( 'realname', 'TEXT' );
Contributor::property( 'nickname', 'TEXT' );
Contributor::property( 'publicname', 'TEXT' );
Contributor::property( 'visibility', 'TINYINT(1)' );
Contributor::property( 'guid', 'TEXT' );
Contributor::property( 'contributioncount', 'INT' );