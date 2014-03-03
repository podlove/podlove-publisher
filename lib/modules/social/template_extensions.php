<?php 
namespace Podlove\Modules\Social;

use Podlove\Modules\Social\Model\ContributorService;
use Podlove\Modules\Social\Model\ShowService;

class TemplateExtensions {

	/**
	 * List of social profiles
	 *
	 * Example:
	 *
	 * ```html
	 * {% for service in contributor.socialServices %}
	 *   <a target="_blank" title="{{ service.title }}" href="{{ service.profileUrl }}">
	 *     <img width="32" height="32" src="{{ service.logoUrl }}" class="podlove-contributor-button" alt="{{ service.title }}" />
	 *   </a>
	 * {% endfor %}
	 * ```
	 * 
	 * @accessor
	 * @dynamicAccessor contributor.socialProfiles
	 */
	public function accessorContributorSocialServices($return, $method_name, $contributor, $contribution, $args = array()) {
		$services = ContributorService::find_all_by_contributor_id($contributor->id);

		usort($services, function($a, $b) {
			if ($a == $b)
				return 0;

			return $a->position < $b->position ? -1 : 1;
		});

		return array_map(function($service) {
			return new Template\Service($service, $service->get_service());
		}, $services);
	}

	/**
	 * List of social profiles
	 *
	 * Example:
	 *
	 * ```html
	 * {% for service in podcast.socialServices %}
	 *   <a target="_blank" title="{{ service.title }}" href="{{ service.profileUrl }}">
	 *     <img width="32" height="32" src="{{ service.logoUrl }}" class="podlove-contributor-button" alt="{{ service.title }}" />
	 *   </a>
	 * {% endfor %}
	 * ```
	 * 
	 * @accessor
	 * @dynamicAccessor podcast.socialProfiles
	 */
	public function accessorPodcastSocialServices($return, $method_name, $podcast, $args = array()) {
		$services = ShowService::all("ORDER BY position ASC");

		return array_map(function($service) {
			return new Template\Service($service, $service->get_service());
		}, $services);
	}

}
