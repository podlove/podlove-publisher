<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

class ContributorRole extends Base {
	public static function selectOptions() {
		$list = array();
		foreach (self::all() as $role) {
			$list[$role->slug] = $role->title;
		}
		return $list;
	}
}

ContributorRole::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ContributorRole::property( 'slug', 'VARCHAR(255)' );
ContributorRole::property( 'title', 'VARCHAR(255)' );