<?php
namespace Podlove\Model;

/**
 * Contains cleaned up data of DownloadIntent table.
 */
class DownloadIntentClean extends Base {

}

DownloadIntentClean::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntentClean::property( 'user_agent_id', 'INT' );
DownloadIntentClean::property( 'media_file_id', 'INT' );
DownloadIntentClean::property( 'request_id', 'VARCHAR(32)' );
DownloadIntentClean::property( 'accessed_at', 'DATETIME' );
DownloadIntentClean::property( 'source', 'VARCHAR(255)' );
DownloadIntentClean::property( 'context', 'VARCHAR(255)' );
DownloadIntentClean::property( 'geo_area_id', 'INT' );
DownloadIntentClean::property( 'lat', 'FLOAT' );
DownloadIntentClean::property( 'lng', 'FLOAT' );
DownloadIntentClean::property( 'httprange', 'VARCHAR(255)' );
