<?php
namespace Podlove\Model;

class File extends Base {
	
}

File::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
File::property( 'release_id', 'INT' );
File::property( 'format_id', 'INT' );
File::property( 'size', 'INT' );