<?php 
namespace Podlove\Modules\Social\Model;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ContributorService extends Base {

}

ContributorService::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ContributorService::property( 'contributor_id', 'INT' );
ContributorService::property( 'service_id', 'INT' );
ContributorService::property( 'value', 'TEXT' );
ContributorService::property( 'title', 'TEXT' );
ContributorService::property( 'position', 'INT' );