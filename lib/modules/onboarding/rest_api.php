<?php

namespace Podlove\Modules\Onboarding;

use Podlove\Modules\WordpressFileUpload\Wordpress_File_Upload;

class WP_REST_PodloveOnboarding_Controller extends \WP_REST_Controller
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
        register_rest_route($this->namespace, $this->rest_base.'/setup', [
            [
                'methods' => \WP_REST_SERVER::EDITABLE,
                'callback' => [$this, 'update_items'],
                'permission_callback' => [$this, 'update_permissions_check']
            ]
        ]);
    }

    public function update_items($request)
    {
        Wordpress_File_Upload::activate_and_setup();

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
        if (!current_user_can('manage_options')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

        return true;
    }
}
