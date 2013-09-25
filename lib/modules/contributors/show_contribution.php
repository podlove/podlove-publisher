<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ShowContribution extends Base {

}

ShowContribution::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
ShowContribution::property( 'contributor_id', 'INT' );
ShowContribution::property( 'show_id', 'INT' );
ShowContribution::property( 'role_id', 'INT' );
ShowContribution::property( 'position', 'FLOAT' );