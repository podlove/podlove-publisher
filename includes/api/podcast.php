<?php

namespace Podlove\Api\Podcast;

use Podlove\Model\Podcast;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;

add_action( 'rest_api_init', function() {
        $controller = new WP_REST_Podlove_Controller();
        $controller->register_routes();
});

class WP_REST_Podlove_Controller extends WP_REST_Controller {
    /** 
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'podlove/v1';
        $this->rest_base = 'podlove';
    }

    /**
     * Register the component routes
     */
    public function register_routes()
    {
        register_rest_route( $this->namespace, '/'.$this->rest_base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_item'),
                'permission_callback' => array( $this, 'get_item_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array( $this, 'update_item'),
                'permission_callback' => array( $this, 'update_item_permissions_check'),
                'args' => $this->get_endpoint_args_for_item_schema(false),
            )
        ));
    }

    /**
     * Check permission for read
     */
    public function get_item_permissions_check($request)
    {
        return true;
    }

    /**
     * Check permission for change
     */
    public function update_item_permissions_check($request)
    {
        if (!current_user_can( 'edit_posts')) {
            return new WP_Error('rest_forbidden', 
                esc_html__('sorry, you do not have permissions to use this REST API endpoint'),
                array('status' => 401));
        }
        return true;
    }

    public function get_item($request)
    {
        $podcast = Podcast::get();

        $res = [];
        $res['_version'] = 'v1';
        $res['title'] = $podcast->title;
        $res['subtitle'] = $podcast->subtitle;
        $res['summary'] = $podcast->summary;
        $res['mnemonic'] = $podcast->mnemonic;
        $res['itunes_type'] = $podcast->itunes_type;
        $res['author_name'] = $podcast->author_name;
        $res['poster'] = $podcast->cover_art()->setWidth(500)->url();
        $res['link'] = \Podlove\get_landing_page_url();

        $res = apply_filters('podlove_api_podcast_response', $res);

        return rest_ensure_response($res);
    }

    public function update_item($request)
    {
        $podcast = Podcast::get();
        if ( isset($request['title'])) {
            $title = $request['title'];
            $podcast->title = $title;
        }
        if ( isset($request['subtitle'])) {
            $subtitle = $request['subtitle'];
            $podcast->subtitle = $subtitle;
        }
        if ( isset($request['summary'])) {
            $summary = $request['summary'];
            $podcast->summary = $summary;
        }
        if ( isset($request['mnemonic'])) {
            $mnemonic = $request['mnemonic'];
            $podcast->mnemonic = $mnemonic;
        }

        $podcast->save();

        return new WP_REST_Response(null, 200);
    }
}