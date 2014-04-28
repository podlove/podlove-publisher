<?php
namespace Podlove\Model;

class UserAgent extends Base {

}

UserAgent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
UserAgent::property( 'user_agent', 'TEXT' );
