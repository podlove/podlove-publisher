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

        if ($this->get_module_option('auphonic_api_key') == '') {
            $auth_url = 'https://auphonic.com/oauth2/authorize/?client_id=0e7fac528c570c2f2b85c07ca854d9&redirect_uri='.urlencode(get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle').'&response_type=code';
            $description = '<i class="podlove-icon-remove"></i> '
                         .__('You need to allow Podlove Publisher to access your Auphonic account. You will be redirected to this page once the auth process completed.', 'podlove-podcasting-plugin-for-wordpress')
                         .'<br><a href="'.$auth_url.'" class="button button-primary">'.__('Authorize now', 'podlove-podcasting-plugin-for-wordpress').'</a>';
            $this->register_option('auphonic_api_key', 'hidden', [
                'label' => __('Authorization', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => $description,
                'html' => ['class' => 'regular-text podlove-check-input'],
            ]);
        } else {
            $user = $this->api->fetch_authorized_user();
            if (isset($user) && is_object($user) && is_object($user->data)) {
                $description = '<i class="podlove-icon-ok"></i> '
                             .sprintf(
                                 __('You are logged in as %s. If you want to logout, click %shere%s.', 'podlove-podcasting-plugin-for-wordpress'),
                                 '<strong>'.$user->data->username.'</strong>',
                                 '<a href="'.admin_url('admin.php?page=podlove_settings_modules_handle&reset_auphonic_auth_code=1').'">',
                                 '</a>'
                             );
            } else {
                $description = '<i class="podlove-icon-remove"></i> '
                             .sprintf(
                                 __('Something went wrong with the Auphonic connection. Please reset the connection and authorize again. To do so click %shere%s', 'podlove-podcasting-plugin-for-wordpress'),
                                 '<a href="'.admin_url('admin.php?page=podlove_settings_modules_handle&reset_auphonic_auth_code=1').'">',
                                 '</a>'
                             );
            }

            $this->register_option('auphonic_api_key', 'hidden', [
                'label' => __('Authorization', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => $description,
                'html' => ['class' => 'regular-text'],
            ]);
        }
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
            if ($this->get_module_option('auphonic_api_key') == '') {
                $ch = curl_init('https://auth.podlove.org/auphonic.php');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());
                curl_setopt($ch, CURLOPT_POSTFIELDS, [
                    'redirect_uri' => get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle',
                    'code' => $_GET['code'], ]);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $result = curl_exec($ch);

                $this->update_module_option('auphonic_api_key', $result);
                header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
            }
        }

        if (isset($_GET['reset_auphonic_auth_code']) && $_GET['reset_auphonic_auth_code'] == '1') {
            $this->update_module_option('auphonic_api_key', '');
            delete_transient('podlove_auphonic_user');
            delete_transient('podlove_auphonic_presets');
            header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_settings_modules_handle');
        }
    }
}
