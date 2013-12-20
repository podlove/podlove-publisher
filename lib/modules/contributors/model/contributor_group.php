<?php 
namespace Podlove\Modules\Contributors\Model;

use \Podlove\Model\Base;

class ContributorGroup extends Base {
	public static function selectOptions() {
		$list = array();
		foreach (self::all() as $role) {
			$list[$role->slug] = $role->title;
		}
		return $list;
	}
}

ContributorGroup::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ContributorGroup::property( 'slug', 'VARCHAR(255)' );
ContributorGroup::property( 'title', 'VARCHAR(255)' );