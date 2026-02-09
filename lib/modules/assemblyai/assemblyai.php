<?php

namespace Podlove\Modules\AssemblyAI;

class AssemblyAI extends \Podlove\Modules\Base
{
    protected $module_name = 'AssemblyAI';
    protected $module_description = 'Generate transcripts for your episodes using AssemblyAI. Transcripts are imported into the Transcripts module.';
    protected $module_group = 'external services';

    public function load()
    {
        new EpisodeEnhancer($this);

        add_action('rest_api_init', [$this, 'api_init']);
        add_action('init', [$this, 'register_settings']);
    }

    public function api_init()
    {
        $api = new REST_API($this);
        $api->register_routes();
    }

    public function register_settings()
    {
        if (!self::is_module_settings_page()) {
            return;
        }

        $api_key = $this->get_module_option('assemblyai_api_key', '');

        if ($api_key) {
            $reset_url = wp_nonce_url(
                admin_url('admin.php?page=podlove_settings_modules_handle&reset_assemblyai_api_key=1'),
                'reset_assemblyai_api_key'
            );
            $description = '<i class="podlove-icon-ok"></i> '
                .__('API key is set.', 'podlove-podcasting-plugin-for-wordpress')
                .' <a href="'.esc_url($reset_url).'">'
                .__('Remove', 'podlove-podcasting-plugin-for-wordpress')
                .'</a>';
        } else {
            $description = __('Get your API key at', 'podlove-podcasting-plugin-for-wordpress')
                .' <a href="https://www.assemblyai.com/" target="_blank">assemblyai.com</a>';
        }

        $this->register_option('assemblyai_api_key', 'string', [
            'label' => __('API Key', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => $description,
            'html' => ['class' => 'regular-text podlove-check-input'],
        ]);

        if (isset($_GET['reset_assemblyai_api_key']) && $_GET['reset_assemblyai_api_key'] == '1') {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'reset_assemblyai_api_key')) {
                return;
            }
            $this->update_module_option('assemblyai_api_key', '');
            wp_safe_redirect(admin_url('admin.php?page=podlove_settings_modules_handle'));
            exit;
        }
    }
}
