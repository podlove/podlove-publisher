<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ShowContribution extends Base {
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

ShowContribution::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ShowContribution::property( 'contributor_id', 'INT' );
ShowContribution::property( 'show_id', 'INT' );
ShowContribution::property( 'role_id', 'INT' );
ShowContribution::property( 'group_id', 'INT' );
ShowContribution::property( 'position', 'FLOAT' );