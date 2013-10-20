<?php
namespace Podlove\Model;

/**
 * Episode Templates.
 */
class Template extends Base {

}

Template::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Template::property( 'title', 'VARCHAR(255)' );
Template::property( 'type', 'VARCHAR(255)' );
Template::property( 'before', 'TEXT' );
Template::property( 'content', 'TEXT' );
Template::property( 'after', 'TEXT' );