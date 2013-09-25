<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Model\Base;

class Contributor extends Base
{
	
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