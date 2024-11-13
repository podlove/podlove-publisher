<?php

namespace Podlove\Modules\WordpressFileUpload;

class Wordpress_File_Upload extends \Podlove\Modules\Base
{
    const DEFAULT_DIR = '/podlove-media';

    protected $module_name = 'WordPress File Upload';
    protected $module_description = 'If you want to upload your media files to WordPress, this module adds a button to the episode form to do that.';
    protected $module_group = 'system';

    public function load()
    {
        add_action('admin_init', [$this, 'register_hooks']);
        // FIXME: this is huge. admin_init is not run for REST calls? what else might this affect?
        add_action('rest_api_init', [$this, 'register_hooks']);

        add_action('init', fn () => $this->register_option('upload_subdir', 'string', [
            'label' => __('Upload subdirectory', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Directory relative to WordPress upload directory where files will be stored.', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => [
                'class' => 'regular-text podlove-check-input',
                'placeholder' => self::DEFAULT_DIR
            ],
        ]));

        $podlove_subdir = trim($this->get_module_option('upload_subdir'));
        if (!$podlove_subdir) {
            add_action('admin_notices', function () {
                ?>
                <div id="message" class="notice notice-success">
                    <p>
                        <strong><?php echo sprintf(
                            __('Module "%s" is active.', 'podlove-podcasting-plugin-for-wordpress'),
                            $this->module_name
                        ); ?></strong>
                    </p>
                    <p>
                        <?php echo __('You need to configure the subdirectory in the WordPress upload directory where your media files should be stored.', 'podlove-podcasting-plugin-for-wordpress'); ?>
                    </p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=podlove_settings_modules_handle#wordpress_file_upload'); ?>">
                          <?php echo __('Go to module settings', 'podlove-podcasting-plugin-for-wordpress'); ?>
                        </a>
                    </p>
                </div>
                <?php
            });
        }
    }

    public function register_hooks()
    {
        add_filter('upload_dir', [$this, 'custom_media_upload_dir']);
        add_filter('podlove_media_file_base_uri_form', [$this, 'set_form_placeholder']);
        add_filter('podlove_media_file_base_uri', [$this, 'set_media_file_base_uri']);
    }

    public function set_media_file_base_uri($uri)
    {
        // TODO: UX wise it is very confusing that the media_file_base_uri must
        // be empty for this to work. But there are usecases (Proxy/CDN) where
        // it's needed that cannot be ignored.
        if (trim($uri, ' /') === '') {
            $upload_dir = wp_upload_dir();
            $upload_dir = $this->custom_media_upload_dir($upload_dir, true);

            return trailingslashit($upload_dir['url']);
        }

        return $uri;
    }

    public function set_form_placeholder($config)
    {
        $upload_dir = wp_upload_dir();
        $upload_dir = $this->custom_media_upload_dir($upload_dir, true);

        $config['html']['placeholder'] = $upload_dir['url'];

        return $config;
    }

    /**
     * Override upload_dir so it ignores date subdirectories etc.
     *
     * @param mixed $upload
     * @param mixed $force_override
     */
    public function custom_media_upload_dir($upload, $force_override = false)
    {
        $podlove_subdir = $this->get_subdir();

        $id = isset($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : 0;
        $parent = $id ? get_post($id)->post_parent : 0;

        if ($force_override || 'podcast' == get_post_type($id) || 'podcast' == get_post_type($parent)) {
            $upload['subdir'] = $podlove_subdir;
        }

        $upload['path'] = $upload['basedir'].$upload['subdir'];
        $upload['url'] = $upload['baseurl'].$upload['subdir'];

        return $upload;
    }

    private function get_subdir()
    {
        $dir = trim($this->get_module_option('upload_subdir'));

        if (empty($dir)) {
            $dir = self::DEFAULT_DIR;
        }

        if ($dir[0] !== '/') {
            $dir = '/'.$dir;
        }

        return $dir;
    }
}
