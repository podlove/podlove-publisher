<?php
namespace Podlove\Modules\Seasons\Model;

class SeasonsIssue {

	public $type;

	public function message() {
		switch ($this->type) {
			case 'multiple_first_seasons':
				return __('Only one season can have an empty start date.', 'podlove');
				break;
			case 'duplicate_start_dates':
				return __('Some of your seasons have the same start date.', 'podlove');
				break;
			default:
				return __('Unknown seasons issue.', 'podlove');
				break;
		}
	}
}
