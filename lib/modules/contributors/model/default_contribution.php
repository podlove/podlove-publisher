<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class DefaultContribution extends Base {
	public function getRole() {
		return ContributorRole::find_by_id($this->role_id);
	}

	public function getGroup() {
		return ContributorGroup::find_by_id($this->group_id);
	}

	public function getContributor() {
		return Contributor::find_by_id($this->contributor_id);
	}
}

DefaultContribution::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DefaultContribution::property( 'contributor_id', 'INT' );
DefaultContribution::property( 'show_id', 'INT' );
DefaultContribution::property( 'role_id', 'INT' );
DefaultContribution::property( 'group_id', 'INT' );
DefaultContribution::property( 'position', 'FLOAT' );
DefaultContribution::property( 'comment', 'TEXT' );