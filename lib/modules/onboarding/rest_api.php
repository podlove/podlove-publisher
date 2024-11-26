<?php

namespace Podlove\Modules\Onboarding;

use Podlove\Modules\WordpressFileUpload\Wordpress_File_Upload;
use WP_REST_Controller;

class WP_REST_PodloveOnboarding_Controller extends WP_REST_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'onboarding';
    }

    /**
     * Register the component routes.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, $this->rest_base."/setup", [
            [
                'methods' => \WP_REST_SERVER::EDITABLE,
                'callback' => [$this, 'update_items'],
                'permission_callback' => [$this, 'update_permissions_check']
            ]
        ]);
    }

    public function update_items($request)
    {
        // activate File-Upload-Module and set default settings
        if (!\Podlove\Modules\Base::is_active('wordpress_file_upload')) {
            \Podlove\Modules\Base::activate('wordpress_file_upload');
        }
        $upload_modul = Wordpress_File_Upload::instance();
        $upload_modul_dir = $upload_modul->get_module_option('upload_subdir');
        if (empty($upload_modul_dir)) {
            $upload_modul->update_module_option('upload_subdir', 'podlove-media');
        }
        // set upload loaction to emty
        $settings = get_option('podlove_podcast');
        $settings["media_file_base_uri"] = "";
        update_option('podlove_podcast', $settings);
        // activated contributor module
        if (isset($request['contributor'])) {
            $contributor = $request['contributor'];
            if (!\Podlove\Modules\Base::is_active('contributors') && $contributor) {
                \Podlove\Modules\Base::activate('contributors');
            }
        }
        // activated transcript module
        if (isset($request['transcript'])) {
            $transcript = $request['transcript'];
            if (!\Podlove\Modules\Base::is_active('transcripts') && $transcript) {
                \Podlove\Modules\Base::activate('transcripts');
            }
        }
        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }

    public function update_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
