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

	public function getEpisode() {
		return \Podlove\Model\Episode::find_one_by_id($this->episode_id);
	}

	public function save() {
		parent::save();
		$this->getContributor()->calcContributioncount();
	}

	public function delete() {
		parent::delete();
		$this->getContributor()->calcContributioncount();
	}
}

EpisodeContribution::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
EpisodeContribution::property( 'contributor_id', 'INT' );
EpisodeContribution::property( 'episode_id', 'INT' );
EpisodeContribution::property( 'role_id', 'INT' );
EpisodeContribution::property( 'position', 'FLOAT' );