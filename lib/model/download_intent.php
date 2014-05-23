<?php
namespace Podlove\Model;

class DownloadIntent extends Base {

}

DownloadIntent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntent::property( 'user_agent_id', 'INT' );
DownloadIntent::property( 'media_file_id', 'INT' );
DownloadIntent::property( 'request_id', 'VARCHAR(32)' );
DownloadIntent::property( 'accessed_at', 'DATETIME' );
DownloadIntent::property( 'source', 'VARCHAR(255)' );
DownloadIntent::property( 'context', 'VARCHAR(255)' );
DownloadIntent::property( 'geo_area_id', 'INT' );
DownloadIntent::property( 'lat', 'FLOAT' );
DownloadIntent::property( 'lng', 'FLOAT' );