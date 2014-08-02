<?php
namespace Podlove\Modules\Networks\Model;

/**
 * Network Templates.
 */
class Template extends \Podlove\Model\Template {
	
}

Template::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Template::property( 'title', 'VARCHAR(255)' );
Template::property( 'content', 'TEXT' );
