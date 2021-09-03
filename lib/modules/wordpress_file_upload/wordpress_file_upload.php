<?php
// todo: override podcast "upload location" and add a note to that form field that it's ignored
//   - nope, must be writable to allow CDN URLs
//   - instead, maybe assume identical if location is empty, otherwise use location
// todo: replace all uses of $podcast->media_file_base_uri with a method call with a filter, so this module can hook into it

namespace Podlove\Modules\WordpressFileUpload;

class Wordpress_File_Upload extends \Podlove\Modules\Base
{
    const DEFAULT_DIR = '/podlove-media';

    protected $module_name = 'WordPress File Upload';
    protected $module_description = 'If you want to upload you media files to WordPress, this module adds a button to the episode form to do that.';
    protected $module_group = 'system';

    public function load()
    {
        add_action('admin_init', [$this, 'register_hooks']);

        $this->register_option('upload_subdir', 'string', [
            'label' => __('Upload subdirectory', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => __('Directory relative to WordPress upload directory where files will be stored.', 'podlove-podcasting-plugin-for-wordpress'),
            'html' => [
                'class' => 'regular-text podlove-check-input',
                'placeholder' => self::DEFAULT_DIR
            ],
        ]);

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
        add_filter('podlove_episode_form_data', [$this, 'add_upload_button_to_form']);
        add_action('podlove_episode_meta_box_end', [$this, 'add_upload_button_styles_and_scripts']);
    }

    public function add_upload_button_to_form($form_data)
    {
        $form_data[] = [
            'type' => 'upload',
            'key' => 'file_upload',
            'options' => [
                'label' => __('File Upload', 'podlove-podcasting-plugin-for-wordpress'),
                'media_title' => __('Media File', 'podlove-podcasting-plugin-for-wordpress'),
                'media_button_text' => __('Use Media File', 'podlove-podcasting-plugin-for-wordpress'),
                'form_button_text' => __('Upload Media File', 'podlove-podcasting-plugin-for-wordpress'),
                'allow_multi_upload' => false
            ],
            'position' => 512,
        ];

        return $form_data;
    }

    /**
     * Override upload_dir so it ignores date subdirectories etc.
     *
     * @param mixed $upload
     */
    public function custom_media_upload_dir($upload)
    {
        $podlove_subdir = trim($this->get_module_option('upload_subdir'));

        if (empty($podlove_subdir)) {
            $podlove_subdir = self::DEFAULT_DIR;
        }

        if ($podlove_subdir[0] !== '/') {
            $podlove_subdir = '/'.$podlove_subdir;
        }

        $id = (int) $_REQUEST['post_id'];
        $parent = get_post($id)->post_parent;

        if ('podcast' == get_post_type($id) || 'podcast' == get_post_type($parent)) {
            $upload['subdir'] = $podlove_subdir;
        }

        $upload['path'] = $upload['basedir'].$upload['subdir'];
        $upload['url'] = $upload['baseurl'].$upload['subdir'];

        return $upload;
    }

    public function add_upload_button_styles_and_scripts()
    {
        ?>
        <style>
        #_podlove_meta_file_upload,
        .podlove-media-upload-wrap .podlove_preview_pic,
        .podlove-media-upload-wrap p
        {
        display: none !important;
        }
        </style>
        <script>
        const uploadUrlInput = document.getElementById('_podlove_meta_file_upload')
        const slugInput = document.getElementById('_podlove_meta_slug');

        uploadUrlInput.addEventListener('change', function (e) {
            const value = e.target.value;
            const slug = value.split('\\').pop().split('/').pop().split('.').shift()
            console.log({slug: slug});
            slugInput.value = slug;
        });
        </script>
    <?php
    }
}
