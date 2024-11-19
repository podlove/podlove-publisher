<?php

namespace Podlove\Model;

use Podlove\Jobs\CronJobRunner;
use PodlovePublisher_Vendor\DeviceDetector\DeviceDetector;

class UserAgent extends Base
{
    /**
     * Fetch new data for all UAs.
     */
    public static function reparse_all()
    {
        CronJobRunner::create_job('\Podlove\Jobs\UserAgentRefreshJob');
    }

    /**
     * Parse UA string and fill in other attributes.
     */
    public function parse()
    {
        // Parse with OPAWG
        $data_file = \Podlove\PLUGIN_DIR . 'data/opawg.json';
        $data_raw = file_get_contents($data_file);
        $user_agent_data = json_decode($data_raw);
        $user_agent_data = apply_filters('podlove_useragent_opawg_data', $user_agent_data);

        // Check if the OPAWG data is valid
        if (!$user_agent_data) {
            error_log('[Podlove Publisher] OPAWG data file is invalid JSON');

            // Fallback to DeviceDetector parser if OPAWG data is invalid
            return $this->parse_by_device_detector();
        }

        $user_agent_string = $this->user_agent;

        // Match user agent with OPAWG data
        $user_agent_match = array_reduce($user_agent_data, function ($agg, $item) use ($user_agent_string) {
            if ($agg !== null) {
                return $agg; // Stop if a match is already found
            }

            foreach ($item->user_agents as $regex) {
                // Escape and validate regex pattern
                $compiled_regex = str_replace('/', '\/', $regex);
                if (@preg_match("/{$compiled_regex}/", null) === false) {
                    error_log("[Podlove Publisher] Invalid regex pattern in OPAWG data: {$regex}");
                    continue; // Skip invalid patterns
                }

                // Check if the regex matches the user agent string
                if (preg_match("/{$compiled_regex}/", $user_agent_string) === 1) {
                    return $item;
                }
            }

            return $agg;
        }, null);

        if ($user_agent_match) {
            // Set client name if available
            $this->client_name = isset($user_agent_match->app) ? $user_agent_match->app : '';

            // Check if the 'os' property exists before accessing it
            if (isset($user_agent_match->os)) {
                $this->os_name = self::normalizeOS($user_agent_match->os);
            } else {
                $this->os_name = ''; // Default to an empty string if 'os' is missing
                error_log('[Podlove Publisher] Missing "os" property in user agent match: ' . json_encode($user_agent_match));
            }

            // Set bot flag if the user agent is a bot
            if (isset($user_agent_match->bot) && $user_agent_match->bot) {
                $this->bot = 1;
            }

            return $this;
        }

        // Fallback to DeviceDetector parser if no match is found in OPAWG data
        return $this->parse_by_device_detector();
    }

    public static function find_or_create_by_uastring($ua_string)
    {
        $ua_string = trim($ua_string);

        if (!strlen($ua_string)) {
            return null;
        }

        $agent = self::find_one_by_user_agent($ua_string);

        if (!$agent) {
            $agent = new self();
            $agent->user_agent = $ua_string;
            $agent->parse()->save();
        }

        return $agent;
    }

    public static function normalizeOS($os_name)
    {
        $map = [
            'ios' => 'iOS',
            'android' => 'Android',
            'mac' => 'macOS',
            'macos' => 'macOS',
            'watchos' => 'watchOS',
            'windows' => 'Windows',
            'linux' => 'Linux',
            'sonos' => 'Sonos',
            'homepod_os' => 'HomepodOS',
            'tvos' => 'tvOS',
        ];

        return $map[trim(strtolower($os_name))] ?? $os_name;
    }

    private function parse_by_device_detector()
    {
        $dd = new DeviceDetector($this->user_agent);

        // only return true if a bot was detected (speeds up detection a bit)
        $dd->discardBotInformation();

        $dd->parse();

        if ($dd->isBot()) {
            $this->bot = 1;
        } else {
            $client = $dd->getClient();

            if ($this->counts_as_bot($client)) {
                $this->bot = 1;

                return $this;
            }

            if (isset($client['name'])) {
                $this->client_name = $client['name'];
            }

            if (isset($client['version'])) {
                $this->client_version = $client['version'];
            }

            if (isset($client['type'])) {
                $this->client_type = $client['type'];
            }

            $os = $dd->getOs();

            if (isset($os['name'])) {
                $this->os_name = self::normalizeOS($os['name']);
            }

            if (isset($os['version'])) {
                $this->os_version = $os['version'];
            }

            $this->device_brand = $dd->getBrand();
            $this->device_model = $dd->getModel();
        }

        return $this;
    }

    /**
     * Classify some clients as bots.
     *
     * @param mixed $client
     *
     * @return bool
     */
    private function counts_as_bot($client)
    {
        $type = isset($client['type']) ? $client['type'] : '';
        $name = isset($client['name']) ? $client['name'] : '';

        if ($type == 'library' && $name == 'WWW::Mechanize') {
            return true;
        }

        return false;
    }
}

UserAgent::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
UserAgent::property('user_agent', 'TEXT', ['index' => true, 'index_length' => 400]);

UserAgent::property('bot', 'TINYINT');
UserAgent::property('client_name', 'VARCHAR(255)');
UserAgent::property('client_version', 'VARCHAR(255)');
UserAgent::property('client_type', 'VARCHAR(255)');
UserAgent::property('os_name', 'VARCHAR(255)');
UserAgent::property('os_version', 'VARCHAR(255)');
UserAgent::property('device_brand', 'VARCHAR(255)');
UserAgent::property('device_model', 'VARCHAR(255)');
