<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Modules\Contributors\Model\Contributor;
use \Podlove\Modules\Contributors\Model\ContributorRole;
use \Podlove\Modules\Contributors\Model\ContributorGroup;
use \Podlove\Modules\Contributors\Model\EpisodeContribution;
use \Podlove\Modules\Contributors\Model\ShowContribution;
use \Podlove\Modules\Contributors\Model\DefaultContribution;

class TemplateExtensions {
	
	/**
	 * List of episode contributors
	 *
	 * **Examples**
	 *
	 * Iterating over a list of contributors
	 * 
	 * ```jinja
	 * {% for contributor in episode.contributors %}
	 * 	{{ contributor.name }}
	 * 	{% if not loop.last %}, {% endif %}
	 * {% endfor %}
	 * ```
	 * 
	 * Iterating over a grouped list of contributors
	 * 
	 * ```jinja
	 * {% for contributorGroup in episode.contributors({groupby: "group"}) %}
	 * 	<strong>{{ contributorGroup.group.title }}:</strong> 
	 * 	{% for contributor in contributorGroup.contributors %}
	 * 		{{ contributor.name }}
	 * 		{% if not loop.last %}, {% endif %}
	 * 	{% endfor %}
	 * {% endfor %}
	 * ```
	 * 
	 * **Parameters**
	 *
	 * - **id:**      Fetch one contributor by its id.
	 *                Example: `episode.contributors({id: 'james'}).name`
	 * - **group:**   group slug. If none is given, show all contributors.
	 * - **role:**    role slug. If none is given, show all contributors.
	 * - **groupby:** group or role slug. Group by "group" or "role".
	 * 	         If used, the returned data is has another layer for the groups.
	 * 	         See examples for more details.
	 * - **order:**   Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'ASC'.
	 *   - 'ASC' - ascending order from lowest to highest values (1, 2, 3; a, b, c).
	 *   - 'DESC' - descending order from highest to lowest values (3, 2, 1; c, b, a).
	 * - **orderby:** Sort contributors by parameter. Defaults to 'position'.
	 *   - 'position' - Order by the contributors position in the episode.
	 *   - 'comment' - Order by the contributors comment in the episode.
	 *
	 * @accessor
	 * @dynamicAccessor episode.contributors
	 */
	public static function accessorEpisodeContributors($return, $method_name, $episode, $post, $args = array()) {
		return $episode->with_blog_scope(function() use ($return, $method_name, $episode, $post, $args) {
			$defaults = array(
				'order'   => 'ASC',
				'orderby' => 'position'
			);
			$args = wp_parse_args($args, $defaults);

			$contributions = EpisodeContribution::find_all_by_episode_id($episode->id);
			$contributions = \Podlove\Modules\Contributors\Contributors::orderContributions($contributions, $args);
			return \Podlove\Modules\Contributors\Contributors::filterContributions($contributions, $args);
		});
	}

	/**
	 * List of podcast contributors.
	 *
	 * **Examples**
	 *
	 * Iterating over a list of contributors
	 * 
	 * ```jinja
	 * {% for contributor in podcast.contributors({scope: "podcast"}) %}
	 * 	{{ contributor.name }}
	 * 	{% if not loop.last %}, {% endif %}
	 * {% endfor %}
	 * ```
	 * 
	 * Iterating over a grouped list of contributors
	 * 
	 * ```jinja
	 * {% for contributorGroup in podcast.contributors({scope: "podcast", groupby: "group"}) %}
	 * 	<strong>{{ contributorGroup.group.title }}:</strong> 
	 * 	{% for contributor in contributorGroup.contributors %}
	 * 		{{ contributor.name }}
	 * 		{% if not loop.last %}, {% endif %}
	 * 	{% endfor %}
	 * {% endfor %}
	 * ```
	 * 
	 * **Parameters**
	 *
	 * - **id:**      Fetch one contributor by its id.
	 *                Example: `podcast.contributors({id: 'james'}).name`
	 * - **scope:**   Either "global", "global-active" or "podcast".
	 *                - "global" returns all contributors.
	 *                - "global-active" returns all contributors with 
	 *                   at least one contribution in a published episode.
	 * 	              - "podcast" returns the contributors configured in podcast settings.
	 * 	              Default: "global-active".
	 * - **group:**   filter by group slug. Defaults to "all", which does not filter.
	 * - **role:**    filter by role slug. Defaults to "all", which does not filter.
	 * - **groupby:** group or role slug. Group by "group" or "role".
	 * 	              If used, the returned data is has another layer for the groups.
	 * 	              See examples for more details.
	 * - **order:**   Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'.
	 *   - 'ASC' - ascending order from lowest to highest values (1, 2, 3; a, b, c).
	 *   - 'DESC' - descending order from highest to lowest values (3, 2, 1; c, b, a).
	 * - **orderby:** Sort contributors by parameter. Defaults to 'name'.
	 *   - 'name' - Order by public name.
	 *
	 * @accessor
	 * @dynamicAccessor podcast.contributors
	 */
	public static function accessorPodcastContributors($return, $method_name, $podcast, $args = array()) {
		return $podcast->with_blog_scope(function() use ($podcast, $args) {
			$args = shortcode_atts( array(
				'id'      => null,
				'scope'   => 'global-active',
				'group'   => 'all',
				'role'    => 'all',
				'groupby' => null,
				'order'   => 'ASC',
				'orderby' => 'name',
			), $args );
			
			if ($args['id'])
				return new Template\Contributor(Contributor::find_one_by_slug($args['id']));

			$scope = in_array($args['scope'], array('global', 'global-active', 'podcast')) ? $args['scope'] : 'global-active';

			$contributors = array();
			if (in_array($scope, array("global", "global-active"))) {
				// fetch by group and/or role. defaults to *all* contributors
				// if no role or group are given
				$group = $args['group'] !== 'all' ? $args['group'] : null;
				$role  = $args['role']  !== 'all' ? $args['role']  : null;
				$contributors = Contributor::byGroupAndRole($group, $role);

				if ($scope == 'global-active') {
					$contributors = array_filter($contributors, function($contributor) {
						return $contributor->getPublishedContributionCount() > 0;
					});
				}

				$contributors = array_map(function($contributor) {
					return new Template\Contributor($contributor);
				}, $contributors);
			} else {
				$contributions = ShowContribution::all();
				$contributors = \Podlove\Modules\Contributors\Contributors::filterContributions($contributions, $args);
			}

			$sort_by_name = function($a, $b) {
				return strcmp(strtolower($a->name()), strtolower($b->name()));
			};

			// sort
			if ($args['groupby'] == 'group') {
				foreach ($contributors as $group_id => $group) {
					usort($contributors[$group_id]['contributors'], $sort_by_name);

					if (strtoupper($args['order']) == 'DESC') {
						$contributors[$group_id]['contributors'] = array_reverse($contributors[$group_id]['contributors']);
					}
				}
			} else {
				usort($contributors, $sort_by_name);

				if (strtoupper($args['order']) == 'DESC') {
					$contributors = array_reverse($contributors);
				}
			}

			return $contributors;
		});
	}
}
