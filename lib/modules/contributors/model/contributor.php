<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

class Contributor extends Base
{
	public function getAvatar($size) {
		return '<img alt="avatar" src="' . $this->getAvatarUrl($size) . '" class="avatar avatar-18 photo" height="' . $size . '" width="' . $size . '">';
	}

	private function getAvatarUrl($size) {

		if ($this->avatar)
			return $this->avatar;
		else
			return $this->getGravatarUrl($size);
	}

	/**
	 * Get Gravatar URL for a specified email address.
	 *
	 * Yes, I know there is get_avatar() but that returns the img tag and I need the URL.
	 *
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
	 * @source http://gravatar.com/site/implement/images/php/
	 */
	private function getGravatarUrl( $s = 80, $d = 'mm', $r = 'g' ) {
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $this->publicemail ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		return $url;
	}	
}

Contributor::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Contributor::property( 'slug', 'VARCHAR(255)' );
Contributor::property( 'gender', 'VARCHAR(255)' );
Contributor::property( 'organisation', 'TEXT' );
Contributor::property( 'department', 'TEXT' );
Contributor::property( 'avatar', 'TEXT' );
Contributor::property( 'twitter', 'VARCHAR(255)' );
Contributor::property( 'adn', 'VARCHAR(255)' );
Contributor::property( 'facebook', 'VARCHAR(255)' );
Contributor::property( 'flattr', 'VARCHAR(255)' );
Contributor::property( 'amazonwishlist', 'TEXT' );
Contributor::property( 'publicemail', 'TEXT' );
Contributor::property( 'privateemail', 'TEXT' );
Contributor::property( 'role', 'VARCHAR(255)' );
Contributor::property( 'realname', 'TEXT' );
Contributor::property( 'nickname', 'TEXT' );
Contributor::property( 'publicname', 'TEXT' );
Contributor::property( 'showpublic', 'VARCHAR(255)' );
Contributor::property( 'permanentcontributor', 'VARCHAR(255)' );
Contributor::property( 'guid', 'TEXT' );
Contributor::property( 'www', 'TEXT' );