<?php
namespace Podlove\Model;

class Feed extends Base {
	
}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'show_id', 'INT' );
Feed::property( 'format_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'language', 'VARCHAR(255)' );
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'block', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'show_description', 'INT' );
// @todo: itunes feed id