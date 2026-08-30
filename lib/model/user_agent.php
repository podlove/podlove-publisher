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
        [
            'bot' => $bot,
            'client_name' => $client_name,
            'type' => $type
        ] = self::find_opawg_client($this->user_agent);

        if ($client_name != null) {
            $this->bot = $bot;
            $this->client_name = $client_name;

            // if it's not a bot, find device
            if (!$this->bot) {
                [
                    'device_category' => $device_category,
                    'name' => $name
                ] = self::find_opawg_device($this->user_agent);

                $this->client_type = $device_category;
                $this->device_model = $name;
            }

            $referer = filter_var($_SERVER['HTTP_REFERER'] ?? '', FILTER_VALIDATE_URL);

            if ($type == 'browsers' && $referer !== false) {
                ['name' => $referrer_name] = self::find_opawg_referrer($referer);

                // I should probably save this separately, but let's be
                // pragmatic and not write a database migration for now. I a
                // user listens via Apple Podcasts on Safari, the interesting
                // bit for the podcaster os the app/platform, not the browser.
                // So let's override it for the moment.
                if ($referrer_name) {
                    $this->client_name = $referrer_name.' (Web)';
                }
            }

            return $this;
        }

        // TODO: simplify, get rid of DeviceDetector
        // fallback to DeviceDetector parser
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

    private static function find_opawg_client($user_agent)
    {
        $types = ['bots', 'apps', 'libraries', 'browsers'];

        foreach ($types as $type) {
            $user_agent_data = self::read_opawg_file($type);
            foreach ($user_agent_data->entries as $entry) {
                $compiled_regex = str_replace('/', '\/', $entry->pattern);
                if (preg_match("/{$compiled_regex}/", $user_agent) === 1) {
                    return [
                        'bot' => $type == 'bots',
                        'client_name' => $entry->name,
                        'type' => $type
                    ];
                }
            }
        }

        return ['bot' => false, 'client_name' => null, 'type' => null];
    }

    private static function find_opawg_device($user_agent)
    {
        $user_agent_data = self::read_opawg_file('devices');
        foreach ($user_agent_data->entries as $entry) {
            $compiled_regex = str_replace('/', '\/', $entry->pattern);
            if (preg_match("/{$compiled_regex}/", $user_agent) === 1) {
                return ['device_category' => $entry->category, 'name' => $entry->name];
            }
        }

        return ['device_category' => null, 'name' => null];
    }

    private static function find_opawg_referrer($referer)
    {
        $user_agent_data = self::read_opawg_file('referrers');
        foreach ($user_agent_data->entries as $entry) {
            $compiled_regex = str_replace('/', '\/', $entry->pattern);
            if (preg_match("/{$compiled_regex}/", $referer) === 1) {
                return ['name' => $entry->name];
            }
        }

        return ['name' => null];
    }

    private static function read_opawg_file($name)
    {
        $data_file = \Podlove\PLUGIN_DIR.'data/'.$name.'.runtime.json';
        $data_raw = file_get_contents($data_file);

        return json_decode($data_raw);
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
