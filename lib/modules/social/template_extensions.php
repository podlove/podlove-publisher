<?php 
namespace Podlove\Modules\Social;

use Podlove\Modules\Social\Model\ContributorService;

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
	public function accessorContributorSocialProfiles($return, $method_name, $contributor, $contribution, $args = array()) {
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

}
