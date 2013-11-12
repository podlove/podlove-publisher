<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to an episode.
 */
class EpisodeContribution extends Base {
	
	public function getRole() {
		return ContributorRole::find_by_id($this->role_id);
	}

	public function getContributor() {
		return Contributor::find_by_id($this->contributor_id);
	}
}

EpisodeContribution::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeContribution::property( 'contributor_id', 'INT' );
EpisodeContribution::property( 'episode_id', 'INT' );
EpisodeContribution::property( 'role_id', 'INT' );
EpisodeContribution::property( 'position', 'FLOAT' );