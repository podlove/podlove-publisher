<?php 
namespace Podlove\Modules\Social;

use Podlove\Modules\Social\Model\ContributorService;
use Podlove\Modules\Social\Model\ShowService;

class TemplateExtensions {

	/**
	 * List of service profiles
	 *
	 * Parameters:
	 *
	 * - **type:** (optional) "social", "donation" or "all". Default: "all"
	 *
	 * Example:
	 *
	 * ```html
	 * {% for service in contributor.services({type: "social"}) %}
	 *   <a target="_blank" title="{{ service.title }}" href="{{ service.profileUrl }}">
	 *     <img width="32" height="32" src="{{ service.logoUrl }}" class="podlove-contributor-button" alt="{{ service.title }}" />
	 *   </a>
	 * {% endfor %}
	 * ```
	 * 
	 * @accessor
	 * @dynamicAccessor contributor.services
	 */
	public function accessorContributorServices($return, $method_name, $contributor, $contribution, $args = array()) {

		$type = (isset($args['type']) && in_array($args['type'], array("social", "donation", "all"))) ? $args['type'] : "all";

		if ($type == "all") {
			$services = ContributorService::find_all_by_contributor_id($contributor->id);
		} else {
			$services = ContributorService::find_by_contributor_id_and_type($contributor->id, $type);
		}

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
	 * List of service profiles
	 * 
	 * Parameters:
	 * 
	 * - **type:** (optional) "social", "donation" or "all". Default: "all"
	 *
	 * Example:
	 *
	 * ```html
	 * {% for service in podcast.services({type: "social"}) %}
	 *   <a target="_blank" title="{{ service.title }}" href="{{ service.profileUrl }}">
	 *     <img width="32" height="32" src="{{ service.logoUrl }}" class="podlove-contributor-button" alt="{{ service.title }}" />
	 *   </a>
	 * {% endfor %}
	 * ```
	 * 
	 * @accessor
	 * @dynamicAccessor podcast.services
	 */
	public function accessorPodcastServices($return, $method_name, $podcast, $args = array()) {

		$type = isset($args['type']) && in_array($args['type'], array("social", "donation", "all")) ? $args['type'] : "all";

		if ($type == "all") {
			$services = ShowService::all("ORDER BY position ASC");
		} else {
			$services = ShowService::find_by_type($type);
		}

		return array_map(function($service) {
			return new Template\Service($service, $service->get_service());
		}, $services);
	}

}
