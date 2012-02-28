<?php
namespace Podlove\Model;

class File extends Base {

	public function find_or_create_by_release_id_and_format_id( $release_id, $format_id ) {

		$file = File::find_by_release_id_and_format_id( $release_id, $format_id );
		
		if ( $file )
			return $file;

		$file = new File();
		$file->release_id = $release_id;
		$file->format_id = $format_id;
		$file->save();

		return $file;
	}

	public function find_by_release_id_and_format_id( $release_id, $format_id ) {
		$where = sprintf( 'release_id = "%s" AND format_id = "%s"', $release_id, $format_id );
		return File::find_one_by_where( $where );
	}
	
}

File::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
File::property( 'release_id', 'INT' );
File::property( 'format_id', 'INT' );
File::property( 'size', 'INT' );