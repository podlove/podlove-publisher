<?php

namespace Podlove\Modules\Contributors\Model;

use Podlove\Model\Base;
use Podlove\Model\Episode;
use Podlove\Model\Image;

class Contributor extends Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    public static function byGroup($groupSlug)
    {
        global $wpdb;

        $sql = '
			SELECT
				contributor_id
			FROM
				'.EpisodeContribution::table_name().'
			WHERE
				group_id = (SELECT id FROM '.ContributorGroup::table_name().' WHERE slug = %s)
			GROUP BY
				contributor_id
		';

        $contributor_ids = $wpdb->get_col(
            $wpdb->prepare($sql, $groupSlug)
        );

        if (is_array($contributor_ids) && count($contributor_ids) > 0) {
            return Contributor::all('WHERE id IN ('.implode(',', $contributor_ids).')');
        }

        return [];
    }

    public static function byRole($roleSlug)
    {
        global $wpdb;

        $sql = '
			SELECT
				contributor_id
			FROM
				'.EpisodeContribution::table_name().'
			WHERE
				role_id = (SELECT id FROM '.ContributorRole::table_name().' WHERE slug = %s)
			GROUP BY
				contributor_id
		';

        $contributor_ids = $wpdb->get_col(
            $wpdb->prepare($sql, $roleSlug)
        );

        if (is_array($contributor_ids) && count($contributor_ids) > 0) {
            return Contributor::all('WHERE id IN ('.implode(',', $contributor_ids).')');
        }

        return [];
    }

    public static function byGroupAndRole($groupSlug = null, $roleSlug = null)
    {
        global $wpdb;

        if (!$groupSlug && !$roleSlug) {
            return self::all();
        }

        if ($groupSlug && !$roleSlug) {
            return self::byGroup($groupSlug);
        }

        if (!$groupSlug && $roleSlug) {
            return self::byRole($roleSlug);
        }

        $sql = '
			SELECT
				contributor_id
			FROM
				'.EpisodeContribution::table_name().'
			WHERE
				role_id = (SELECT id FROM '.ContributorRole::table_name().' WHERE slug = %s)
				AND
				group_id = (SELECT id FROM '.ContributorGroup::table_name().' WHERE slug = %s)
			GROUP BY
				contributor_id
		';

        $contributor_ids = $wpdb->get_col(
            $wpdb->prepare($sql, $roleSlug, $groupSlug)
        );

        if (is_array($contributor_ids) && count($contributor_ids) > 0) {
            return Contributor::all('WHERE id IN ('.implode(',', $contributor_ids).')');
        }

        return [];
    }

    public function getName()
    {
        if ($this->publicname) {
            return $this->publicname;
        }
        if ($this->realname) {
            return $this->realname;
        }

        return $this->nickname;
    }

    public function avatar()
    {
        if ($this->avatar) {
            if (filter_var($this->avatar, FILTER_VALIDATE_EMAIL) === false) {
                $url = $this->avatar;
            } else {
                $url = $this->getGravatarUrl(512, $this->avatar);
            }
        } else {
            $url = $this->getGravatarUrl(512);
        }

        return new Image($url, $this->getName());
    }

    public function getContributions()
    {
        return EpisodeContribution::find_all_by_contributor_id($this->id);
    }

    /**
     * Episodes.
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
     * @param mixed $args
     */
    public function episodes($args = [])
    {
        return $this->with_blog_scope(function () use ($args) {
            global $wpdb;

            $joins = '';

            if (isset($args['group']) && $args['group']) {
                $joins .= 'INNER JOIN '.ContributorGroup::table_name()." g ON g.id = ec.group_id AND g.slug = '".esc_sql($args['group'])."'";
            }

            if (isset($args['role']) && $args['role']) {
                $joins .= 'INNER JOIN '.ContributorRole::table_name()." r ON r.id = ec.role_id AND r.slug = '".esc_sql($args['role'])."'";
            }

            $where = 'ec.contributor_id = '.(int) $this->id;

            if (isset($args['post_status']) && in_array($args['post_status'], get_post_stati())) {
                $where .= " AND p.post_status = '".$args['post_status']."'";
            } else {
                $where .= " AND p.post_status = 'publish'";
            }

            // order
            $order_map = [
                'publicationDate' => 'p.post_date',
                'recordingDate' => 'e.recordingDate',
                'slug' => 'e.slug',
                'title' => 'p.post_title',
            ];

            if (isset($args['orderby'], $order_map[$args['orderby']])) {
                $orderby = $order_map[$args['orderby']];
            } else {
                $orderby = $order_map['publicationDate'];
            }

            if (isset($args['order'])) {
                $args['order'] = strtoupper($args['order']);
                if (in_array($args['order'], ['ASC', 'DESC'])) {
                    $order = $args['order'];
                } else {
                    $order = 'DESC';
                }
            } else {
                $order = 'DESC';
            }

            if (isset($args['limit'])) {
                $limit = ' LIMIT '.(int) $args['limit'];
            } else {
                $limit = '';
            }

            $sql = '
				SELECT
					ec.episode_id
				FROM
					'.EpisodeContribution::table_name().' ec
					INNER JOIN '.\Podlove\Model\Episode::table_name().' e ON e.id = ec.episode_id
					INNER JOIN '.$wpdb->posts.' p ON p.ID = e.post_id
					'.$joins.'
				WHERE '.$where.'
				GROUP BY ec.episode_id
				ORDER BY '.$orderby.' '.$order.
                $limit
            ;

            $episode_ids = $wpdb->get_col($sql);

            return array_map(function ($episode_id) {
                return \Podlove\Model\Episode::find_one_by_id($episode_id);
            }, array_unique($episode_ids));
        });
    }

    public function getPublishedContributionCount()
    {
        global $wpdb;

        $sql = '
        SELECT count(*) FROM (
            SELECT
				e.id
			FROM
				'.EpisodeContribution::table_name().' ec
				JOIN '.Episode::table_name().' e ON ec.episode_id = e.id
				JOIN '.$wpdb->posts." p ON e.post_id = p.ID
			WHERE
				ec.contributor_id = %d
				AND p.post_status = 'publish'
            GROUP BY
                e.id) tmp
		";

        $contributionCount = $wpdb->get_var(
            $wpdb->prepare($sql, $this->id)
        );

        return $contributionCount;
    }

    public function getShowContributions()
    {
        return ShowContribution::find_all_by_contributor_id($this->id);
    }

    public function getDefaultContributions()
    {
        return DefaultContribution::find_all_by_contributor_id($this->id);
    }

    /**
     * Calculates episode contributions and stores them in contributioncount attribute.
     */
    public function calcContributioncount()
    {
        $this->contributioncount = $this->getPublishedContributionCount();
        $this->save();
    }

    /**
     * Return private mail address in RFC2822 format.
     *
     * Something like:
     *
     *   John Doe <hello@doe.com>
     *
     * @return string
     */
    public function getMailAddress()
    {
        $name = $this->getName();
        $email = $this->privateemail;

        if (empty($email)) {
            return '';
        }

        if (empty($name)) {
            return $email;
        }

        return sprintf('%s <%s>', trim($name), trim($email));
    }

    /**
     * @override \Podlove\Model\Base::delete();
     */
    public function delete()
    {
        foreach ($this->getContributions() as $contribution) {
            $contribution->delete();
        }

        foreach ($this->getShowContributions() as $contribution) {
            $contribution->delete();
        }

        foreach ($this->getDefaultContributions() as $contribution) {
            $contribution->delete();
        }

        parent::delete();
    }

    /**
     * Get Gravatar URL for a specified email address.
     *
     * Yes, I know there is get_avatar() but that returns the img tag and I need the URL.
     *
     * @param string     $s     Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param null|mixed $email
     * @source http://gravatar.com/site/implement/images/php/
     */
    private function getGravatarUrl($s = 80, $email = null)
    {
        $email = $email ? $email : $this->publicemail;

        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= '.jpg';
        $url .= "?s={$s}&d=mm&r=g";

        return $url;
    }
}

Contributor::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Contributor::property('identifier', 'VARCHAR(255)');
Contributor::property('gender', 'VARCHAR(255)');
Contributor::property('organisation', 'TEXT');
Contributor::property('department', 'TEXT');
Contributor::property('jobtitle', 'TEXT');
Contributor::property('avatar', 'TEXT');
Contributor::property('flattr', 'VARCHAR(255)');
Contributor::property('publicemail', 'TEXT');				// DEPRECATED since 1.10.23
Contributor::property('privateemail', 'TEXT');
Contributor::property('realname', 'TEXT');
Contributor::property('nickname', 'TEXT');
Contributor::property('publicname', 'TEXT');
Contributor::property('visibility', 'TINYINT(1)');
Contributor::property('guid', 'TEXT');
Contributor::property('contributioncount', 'INT');
