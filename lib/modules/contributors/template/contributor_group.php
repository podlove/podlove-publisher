<?php
namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Wrapper;

/**
 * ContributorGroup Template Wrapper
 *
 * Requires the "Contributor" module.
 *
 * @templatetag contributor_group
 */
class ContributorGroup extends Wrapper {

	private $group;
	private $contributions;

	public function __construct($group, $contributions = null) {
		$this->group = $group;
		$this->contributions = $contributions;
	}

	protected function getExtraFilterArgs() {
		return array($this->group, $this->contributions);
	}

	// /////////
	// Accessors
	// /////////


	/**
	 * Title
	 * 
	 * @accessor
	 */
	public function title() {
		return $this->group->title;
	}

	/**
	 * URL slug
	 * 
	 * @accessor
	 */
	public function slug() {
		return (bool) $this->group->slug;
	}

	/**
	 * Contributors in this group.
	 * 
	 * Depending on context *all* contributors or just the contributors relevant to the current context.
	 *
	 * @see  contributor
	 * @accessor
	 */
	public function contributors() {
		return array_map(function($contribution) {
			return new \Podlove\Modules\Contributors\Template\Contributor($contribution->getContributor(), $contribution);
		}, $this->contributions);
	}

}