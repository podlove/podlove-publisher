<?php
namespace Podlove\Model;

use DeviceDetector\DeviceDetector;

class UserAgent extends Base {

	/**
	 * Parse UA string and fill in other attributes
	 */
	public function parse()
	{
		$dd = new DeviceDetector($this->user_agent);

		// only return true if a bot was detected (speeds up detection a bit)
		$dd->discardBotInformation();

		$dd->parse();

		if ($dd->isBot()) {
			$this->bot = 1;
		} else {
			$client = $dd->getClient();
			$this->client_name = $client['name'];
			$this->client_version = $client['version'];
			$this->client_type = $client['type'];

			$os = $dd->getOs();
			$this->os_name = $os['name'];
			$this->os_version = $os['version'];

			$this->device_brand = $dd->getBrand();
			$this->device_model = $dd->getModel();
		}
	}

}

UserAgent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
UserAgent::property( 'user_agent', 'TEXT', array( 'index' => true, 'index_length' => 400 ) );

UserAgent::property( 'bot', 'TINYINT' );
UserAgent::property( 'client_name', 'VARCHAR(255)' );
UserAgent::property( 'client_version', 'VARCHAR(255)' );
UserAgent::property( 'client_type', 'VARCHAR(255)' );
UserAgent::property( 'os_name', 'VARCHAR(255)' );
UserAgent::property( 'os_version', 'VARCHAR(255)' );
UserAgent::property( 'device_brand', 'VARCHAR(255)' );
UserAgent::property( 'device_model', 'VARCHAR(255)' );
