<?php

namespace Podlove\Modules\Auphonic;

use Podlove\Http;

class Auphonic extends \Podlove\Modules\Base
{
    protected $module_name = 'Auphonic';
    protected $module_description = 'Auphonic is an audio post production web service. This module adds an interface to episodes, so you can create and manage productions right from Podlove Publisher.';
    protected $module_group = 'external services';

    /**
     * API to Auphonic Service.
     *
     * @var Podlove\Modules\Auphonic\API_Wrapper
     */
    private $api;

    /**
     * Plus file transfer handler.
     *
     * @var Podlove\Modules\Auphonic\PlusFileTransfer
     */
    private $plus_file_transfer;

    public function load()
    {
        $this->api = new API_Wrapper($this);
        $this->plus_file_transfer = new PlusFileTransfer($this);

        new EpisodeEnhancer($this);

        add_action('wp_ajax_podlove-add-production-for-auphonic-webhook', [$this, 'ajax_add_episode_for_auphonic_webhook']);
        add_action('wp', [$this, 'auphonic_webhook']);

        add_action('rest_api_init', [$this, 'api_init']);

        add_action('wp_ajax_podlove-refresh-auphonic-presets', [$this, 'ajax_refresh_presets']);

        add_action('podlove_show_form_end', [$this, 'shows_module_append_preset_option']);

        add_filter('pre_update_option_'.$this->get_module_options_name(), [$this, 'intercept_settings_save'], 10, 2);
        add_action('admin_notices', [$this, 'render_settings_errors']);

        if (isset($_GET['page']) && $_GET['page'] == 'podlove_settings_modules_handle') {
            add_action('admin_bar_init', [$this, 'check_code']);
        }

        add_action('init', [$this, 'register_settings']);
    }

    public function api_init()
    {
        $api_v2 = new REST_API($this);
        $api_v2->register_routes();
    }

    public function register_settings()
    {
        if (!self::is_module_settings_page()) {
            return;
        }

        $this->register_option('auphonic_api_key', 'hidden', [
            'label' => __('Authorization', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => $this->get_authorization_description(),
            'html' => ['class' => 'regular-text'],
        ]);

        $this->register_option('auphonic_manual_api_key', 'password', [
            'label' => __('API Key', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => sprintf(
                '%s<br>%s',
                __('Paste an Auphonic API key from your account settings. If you save a new key here, it replaces the current Auphonic connection after validation.', 'podlove-podcasting-plugin-for-wordpress'),
                sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                    esc_url('https://auphonic.com/accounts/settings/'),
                    __('Open Auphonic account settings', 'podlove-podcasting-plugin-for-wordpress')
                )
            ),
            'html' => [
                'class' => 'regular-text',
                'autocomplete' => 'new-password',
                'placeholder' => __('Paste API key to replace current token', 'podlove-podcasting-plugin-for-wordpress'),
            ],
        ]);
    }

    public function render_settings_errors()
    {
        if (!self::is_module_settings_page()) {
            return;
        }

        settings_errors($this->get_module_options_name());
    }

    public function intercept_settings_save($new_value, $old_value)
    {
        if (!self::is_module_settings_page()) {
            return $new_value;
        }

        if (!is_array($new_value)) {
            $new_value = [];
        }

        if (!is_array($old_value)) {
            $old_value = [];
        }

        $manual_api_key = '';

        if (isset($new_value['auphonic_manual_api_key'])) {
            $manual_api_key = trim(wp_unslash($new_value['auphonic_manual_api_key']));
            unset($new_value['auphonic_manual_api_key']);
        }

        if ($manual_api_key === '') {
            return $new_value;
        }

        if (!$this->validate_api_key($manual_api_key)) {
            add_settings_error(
                $this->get_module_options_name(),
                'invalid_auphonic_api_key',
                __('The Auphonic API key could not be verified. The existing connection was kept.', 'podlove-podcasting-plugin-for-wordpress'),
                'error'
            );

            $new_value['auphonic_api_key'] = isset($old_value['auphonic_api_key']) ? $old_value['auphonic_api_key'] : '';

            return $new_value;
        }

        $new_value['auphonic_api_key'] = $manual_api_key;
        $this->clear_auphonic_cache();

        add_settings_error(
            $this->get_module_options_name(),
            'valid_auphonic_api_key',
            __('The Auphonic API key was saved successfully.', 'podlove-podcasting-plugin-for-wordpress'),
            'updated'
        );

        return $new_value;
    }

    public function shows_module_append_preset_option($wrapper)
    {
        $preset_list = $this->get_presets_list();

        $wrapper->select('auphonic_preset', [
            'label' => __('Auphonic Preset', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Define a Auphonic Preset for this show. If none is set, the global module preset is used.', 'podlove-podcasting-plugin-for-wordpress'),
            'type' => 'select',
            'options' => $preset_list,
        ]);
    }

    /**
     * Register Event for Auphonic Webhook.
     */
    public function auphonic_webhook()
    {
        if (!isset($_REQUEST['podlove-auphonic-production']) || empty($_REQUEST['podlove-auphonic-production']) || empty($_POST)) {
            return;
        }

        if ($_POST['status_string'] !== 'Done') {
            \Podlove\Log::get()->addError(
                'Auphonic webhook failed.',
                ['data' => $_POST]
            );

            exit;
        }

        $post_id = (int) $_REQUEST['podlove-auphonic-production'];
        $webhook_config = \get_post_meta($post_id, 'auphonic_webhook_config', true);

        [
            'authkey' => $authkey,
            'enabled' => $enabled
        ] = $webhook_config;

        if ($_REQUEST['authkey'] !== $authkey) {
            \Podlove\Log::get()->addWarning(
                'Auphonic webhook failed. AuthKey mismatch.',
                ['post_id' => $post_id]
            );

            return;
        }

        $this->update_production_data($post_id);

        if (\Podlove\Modules\Plus\FileStorage::is_enabled()) {
            $transfer_status = get_post_meta($post_id, 'auphonic_plus_transfer_status', true);

            if (empty($transfer_status) || $transfer_status === 'waiting_for_webhook') {
                $this->plus_file_transfer->initiate_transfers($post_id);
            }
        }

        if (!$enabled) {
            \Podlove\Log::get()->addInfo(
                'Auphonic webhook was enabled on production start but disabled during production. Episode data was updated but not published.',
                ['post_id' => $post_id]
            );

            return;
        }

        \wp_publish_post($post_id);
        \Podlove\Log::get()->addInfo(
            'Auphonic webhook finished. Episode published.',
            ['post_id' => $post_id]
        );
        exit;
    }

    /**
     * Updates Episode production data after Auphonic production has finished.
     *
     * @param mixed $post_id
     */
    public function update_production_data($post_id)
    {
        $episode = \Podlove\Model\Episode::find_or_create_by_post_id($post_id);
        $production = json_decode($this->fetch_production($_POST['uuid']), true)['data'];

        $metadata = [
            'title' => get_the_title($post_id),
            'subtitle' => $episode->subtitle,
            'summary' => $episode->summary,
            'duration' => $episode->duration,
            'chapters' => $episode->chapters,
            'slug' => $episode->slug,
            'license' => $episode->license,
            'license_url' => $episode->license_url,
            'tags' => implode(',', array_map(function ($tag) {
                return $tag->name;
            }, wp_get_post_tags($post_id))),
        ];

        $auphonic_metadata = [
            'title' => $production['metadata']['title'],
            'subtitle' => $production['metadata']['subtitle'],
            'summary' => $production['metadata']['summary'],
            'duration' => $production['length_timestring'],
            'chapters' => $this->convert_chapters_to_string($production['chapters']),
            'slug' => $production['output_basename'],
            'license' => $production['metadata']['license'],
            'license_url' => $production['metadata']['license_url'],
            'tags' => implode(',', $production['metadata']['tags']),
        ];

        // Merge both arrays
        foreach ($metadata as $metadata_key => $metadata_entry) {
            if (is_null($metadata_entry) || empty($metadata_entry)) {
                $metadata[$metadata_key] = $auphonic_metadata[$metadata_key];
            }
        }

        $episode->subtitle = $metadata['subtitle'];
        $episode->summary = $metadata['summary'];
        $episode->duration = $metadata['duration'];
        $episode->chapters = $metadata['chapters'];
        $episode->slug = $metadata['slug'];
        $episode->license = $metadata['license'];
        $episode->license_url = $metadata['license_url'];
        $episode->save();

        wp_update_post([
            'ID' => $post_id,
            'post_title' => $metadata['title'],
        ]);
        wp_set_post_tags($post_id, $metadata['tags']);
    }

    /**
     * Initiate PLUS file transfers for an episode.
     *
     * @param int $post_id
     */
    public function initiate_plus_file_transfers($post_id)
    {
        $this->plus_file_transfer->initiate_transfers($post_id);
    }

    /**
     * Get PLUS file transfer queue for an episode.
     *
     * @param int $post_id
     *
     * @return array
     */
    public function get_plus_transfer_queue($post_id)
    {
        return $this->plus_file_transfer->get_transfer_queue($post_id);
    }

    /**
     * Transfer a single PLUS file for an episode.
     *
     * @param int   $post_id
     * @param array $file_data
     *
     * @return array
     */
    public function transfer_single_plus_file($post_id, $file_data)
    {
        return $this->plus_file_transfer->transfer_single_file($post_id, $file_data);
    }

    /**
     * Set final PLUS transfer status after frontend processing.
     *
     * @param int $post_id
     * @param string $status
     * @param array|null $files
     * @param string|null $errors
     */
    public function set_plus_transfer_final_status($post_id, $status, $files = null, $errors = null)
    {
        $this->plus_file_transfer->set_final_transfer_status($post_id, $status, $files, $errors);
    }

    public function convert_chapters_to_string($chapters)
    {
        if (!is_array($chapters)) {
            return;
        }

        $chapters_string = '';

        foreach ($chapters as $chapter) {
            $chapters_string .= $chapter['start_output'].' ';
            $chapters_string .= $chapter['title'];

            if (!empty($chapter['url'])) {
                $chapters_string = $chapters_string.' <'.$chapter['url'].'>';
            }

            $chapters_string .= "\n";
        }

        return $chapters_string;
    }

    /**
     * Refresh the list of auphonic presets.
     */
    public function ajax_refresh_presets()
    {
        delete_transient('podlove_auphonic_presets');
        $result = $this->api->fetch_presets();

        return \Podlove\AJAX\AJAX::respond_with_json($result);
    }

    public function get_presets_list()
    {
        $presets = $this->api->fetch_presets();
        if ($presets && is_array($presets->data)) {
            $preset_list = [];

            $raw_list = $presets->data;
            usort($raw_list, function ($a, $b) {
                return $a->preset_name <=> $b->preset_name;
            });

            foreach ($raw_list as $preset) {
                $preset_list[$preset->uuid] = $preset->preset_name;
            }
        } else {
            $preset_list[] = __('Presets could not be loaded', 'podlove-podcasting-plugin-for-wordpress');
        }

        return $preset_list;
    }

    /**
     * Register a new Episode that can be published via Auphonic.
     */
    public function ajax_add_episode_for_auphonic_webhook()
    {
        $post_id = $_REQUEST['post_id'];
        $auth_key = $_REQUEST['authkey'];
        $action = $_REQUEST['flag'];

        if (!$post_id || !$action || !$auth_key) {
            return \Podlove\AJAX\AJAX::respond_with_json(false);
        }

        $episodes_to_be_remote_published = get_option('podlove_episodes_to_be_remote_published');

        if (!is_array($episodes_to_be_remote_published)) {
            $episodes_to_be_remote_published = [];
        }

        $episodes_to_be_remote_published[$post_id] = [
            'post_id' => $post_id,
            'auth_key' => $auth_key,
            'action' => $action,
        ];
        update_option('podlove_episodes_to_be_remote_published', $episodes_to_be_remote_published);

        \Podlove\Log::get()->addDebug(
            'Auphonic webhooks changed.',
            ['data' => $episodes_to_be_remote_published]
        );

        return \Podlove\AJAX\AJAX::respond_with_json(true);
    }

    /**
     * Fetch name of logged in user via Auphonic API.
     *
     * Cached in transient "podlove_auphonic_user".
     *
     * @return string
     */
    public function fetch_authorized_user()
    {
        $cache_key = 'podlove_auphonic_user';

        if (($user = get_transient($cache_key)) !== false) {
            return $user;
        }
        if (!($token = $this->get_module_option('auphonic_api_key'))) {
            return '';
        }

        $curl = new Http\Curl();
        $curl->request('https://auphonic.com/api/user.json', [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->get_module_option('auphonic_api_key'),
            ],
        ]);
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $decoded_user = json_decode($response['body']);
            $user = $decoded_user ? $decoded_user : false;
            set_transient($cache_key, $user, 60 * 60 * 24 * 365); // 1 year, we devalidate manually

            return $user;
        }

        return false;
    }

    /**
     * Fetch production via Auphonic APU.
     *
     * @param mixed $uuid
     *
     * @return string
     */
    public function fetch_production($uuid)
    {
        if (!($token = $this->get_module_option('auphonic_api_key'))) {
            return '';
        }

        $curl = new Http\Curl();
        $curl->request('https://auphonic.com/api/production/'.$uuid.'.json', [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->get_module_option('auphonic_api_key'),
            ],
        ]);
        $response = $curl->get_response();

        return $response['body'];
    }

    /**
     * Fetch list of presets via Auphonic APU.
     *
     * Cached in transient "podlove_auphonic_presets".
     *
     * @return string
     */
    public function fetch_presets()
    {
        $cache_key = 'podlove_auphonic_presets';

        if (($presets = get_transient($cache_key)) !== false) {
            return $presets;
        }
        if (!($token = $this->get_module_option('auphonic_api_key'))) {
            return '';
        }

        $curl = new Http\Curl();
        $curl->request('https://auphonic.com/api/presets.json', [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->get_module_option('auphonic_api_key'),
            ],
        ]);
        $response = $curl->get_response();

        if ($curl->isSuccessful()) {
            $presets = json_decode($response['body']);
            set_transient($cache_key, $presets, 60 * 60 * 24 * 365); // 1 year, we devalidate manually

            return $presets;
        }

        return [];
    }

    public function check_code()
    {
        if (isset($_GET['code']) && $_GET['code']) {
            $ch = curl_init('https://auth.podlove.org/auphonic.php');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'redirect_uri' => get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle',
                'code' => $_GET['code'], ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);

            $this->update_module_option('auphonic_api_key', $result);
            $this->clear_auphonic_cache();
            header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
        }

        if (isset($_GET['reset_auphonic_auth_code']) && $_GET['reset_auphonic_auth_code'] == '1') {
            $this->update_module_option('auphonic_api_key', '');
            $this->clear_auphonic_cache();
            header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
        }
    }

    private function get_authorization_description()
    {
        $auth_url = 'https://auphonic.com/oauth2/authorize/?client_id=0e7fac528c570c2f2b85c07ca854d9&redirect_uri='.urlencode(get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle').'&response_type=code';
        $user = $this->api->fetch_authorized_user();
        $reset_link = '<a href="'.esc_url(admin_url('admin.php?page=podlove_settings_modules_handle&reset_auphonic_auth_code=1')).'">'.__('Reset connection', 'podlove-podcasting-plugin-for-wordpress').'</a>';

        if (isset($user) && is_object($user) && is_object($user->data)) {
            $status = '<i class="podlove-icon-ok"></i> '
                    .sprintf(
                        __('Auphonic is connected as %s.', 'podlove-podcasting-plugin-for-wordpress'),
                        '<strong>'.esc_html($user->data->username).'</strong>'
                    );
        } elseif ($this->get_module_option('auphonic_api_key') != '') {
            $status = '<i class="podlove-icon-remove"></i> '
                    .__('A stored Auphonic token could not be verified. Reauthorize with OAuth, replace it with an API key, or reset the connection.', 'podlove-podcasting-plugin-for-wordpress');
        } else {
            $status = '<i class="podlove-icon-remove"></i> '
                    .__('No Auphonic credentials configured yet. Connect with OAuth or paste an API key below.', 'podlove-podcasting-plugin-for-wordpress');
        }

        return implode('<br>', array_filter([
            $status,
            __('Authorize via OAuth. You will be redirected back here after Auphonic completes the authorization flow.', 'podlove-podcasting-plugin-for-wordpress'),
            '<a href="'.esc_url($auth_url).'" class="button button-primary">'.__('Authorize with OAuth', 'podlove-podcasting-plugin-for-wordpress').'</a>',
            $this->get_module_option('auphonic_api_key') != '' ? $reset_link : '',
        ]));
    }

    private function validate_api_key($token)
    {
        $curl = new Http\Curl();
        $curl->request('https://auphonic.com/api/user.json', [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        if (!$curl->isSuccessful()) {
            return false;
        }

        $response = $curl->get_response();
        $decoded_user = json_decode($response['body']);

        return $decoded_user && isset($decoded_user->data);
    }

    private function clear_auphonic_cache()
    {
        delete_transient('podlove_auphonic_user');
        delete_transient('podlove_auphonic_presets');
    }
}
