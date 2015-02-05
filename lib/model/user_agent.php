<?php
namespace Podlove\Model;

use DeviceDetector\DeviceDetector;

class UserAgent extends Base {

	/**
	 * Fetch new data for all UAs
	 * 
	 * @deprecated use podlove_init_user_agent_refresh() instead
	 */
	public static function reparse_all() {
		podlove_init_user_agent_refresh();
	}

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

			if (isset($client['name']))
				$this->client_name = $client['name'];

			if (isset($client['version']))
				$this->client_version = $client['version'];

			if (isset($client['type']))
				$this->client_type = $client['type'];

			$os = $dd->getOs();

			if (isset($os['name']))
				$this->os_name = $os['name'];

			if (isset($os['version']))
				$this->os_version = $os['version'];

			$this->device_brand = $dd->getBrand();
			$this->device_model = $dd->getModel();
		}

		return $this;
	}

	public static function find_or_create_by_uastring($ua_string) {

		$ua_string = trim($ua_string);

		if (!strlen($ua_string))
			return NULL;

		$agent = self::find_one_by_user_agent($ua_string);

		if (!$agent) {
			$agent = new self;
			$agent->user_agent = $ua_string;
			$agent->parse()->save();
		}
		
		return $agent;
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
