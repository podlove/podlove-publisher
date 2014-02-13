<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

class Contributor extends Base
{	
	public function getName() {
		if ($this->publicname) {
			return $this->publicname;
		} else {
			if ($this->realname) {
				return $this->realname;
			} else {
				return $this->nickname;
			}
		}
	}

	public function getAvatar($size) {
		return '<img alt="avatar" src="' . $this->getAvatarUrl($size) . '" class="avatar avatar-' . $size . ' photo" height="' . $size . '" width="' . $size . '">';
	}

	public function getRole() {
		return ContributorRole::find_one_by_slug($this->role);
	}

	public function getAvatarUrl($size) {

		if ($this->avatar)
			if (filter_var($this->avatar, FILTER_VALIDATE_EMAIL) === FALSE) {
				return $this->avatar;
			} else {
				return $this->getGravatarUrl($size, $this->avatar);
			}
		else
			return $this->getGravatarUrl($size);
	}

	public function getContributions() {
		return EpisodeContribution::find_all_by_contributor_id($this->id);
	}

	public function getShowContributions() {
		return ShowContribution::find_all_by_contributor_id($this->id);
	}

	public function getDefaultContributions() {
		return DefaultContribution::find_all_by_contributor_id($this->id);
	}

	public function calcContributioncount() {
		$this->contributioncount = count($this->getContributions());
		$this->save();
	}

	/**
	 * Get Gravatar URL for a specified email address.
	 *
	 * Yes, I know there is get_avatar() but that returns the img tag and I need the URL.
	 *
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @source http://gravatar.com/site/implement/images/php/
	 */
	private function getGravatarUrl( $s = 80, $email = null ) {

		$email = $email ? $email : $this->publicemail;

		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=mm&r=g";
		return $url;
	}	

	/**
	 * @override \Podlove\Model\Base::delete();
	 */
	public function delete() {
		foreach ( $this->getContributions() as $contribution )
			$contribution->delete();

		foreach ( $this->getShowContributions() as $contribution )
			$contribution->delete();

		foreach ( $this->getDefaultContributions() as $contribution )
			$contribution->delete();

		parent::delete();
	}
}

Contributor::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Contributor::property( 'slug', 'VARCHAR(255)' );
Contributor::property( 'gender', 'VARCHAR(255)' );
Contributor::property( 'organisation', 'TEXT' );
Contributor::property( 'department', 'TEXT' );
Contributor::property( 'jobtitle', 'TEXT' );
Contributor::property( 'avatar', 'TEXT' );
Contributor::property( 'twitter', 'VARCHAR(255)' );
Contributor::property( 'adn', 'VARCHAR(255)' );
Contributor::property( 'googleplus', 'TEXT' );
Contributor::property( 'facebook', 'VARCHAR(255)' );
Contributor::property( 'flattr', 'VARCHAR(255)' );
Contributor::property( 'paypal', 'VARCHAR(255)' );
Contributor::property( 'bitcoin', 'VARCHAR(255)' );
Contributor::property( 'litecoin', 'VARCHAR(255)' );
Contributor::property( 'amazonwishlist', 'TEXT' );
Contributor::property( 'publicemail', 'TEXT' );
Contributor::property( 'privateemail', 'TEXT' );
Contributor::property( 'realname', 'TEXT' );
Contributor::property( 'nickname', 'TEXT' );
Contributor::property( 'publicname', 'TEXT' );
Contributor::property( 'visibility', 'TINYINT(1)' );
Contributor::property( 'guid', 'TEXT' );
Contributor::property( 'www', 'TEXT' );
Contributor::property( 'contributioncount', 'INT' );