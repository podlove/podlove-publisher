<?php

namespace Podlove\Api\Error;

use WP_Error;

class ForbiddenAccess extends WP_Error
{
    /**
     * Constructor.
     *
     * @param $code
     * @param $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'rest_forbidden';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, you do not have permissions to use this REST API endpoint');
        }
        parent::__construct($code, $message, ['status' => 401]);
    }
}

class NotFound extends WP_Error
{
    /**
     * Constructor.
     *
     * @param $code
     * @param $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'not_found';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, we do not found the requested resource');
        }
        parent::__construct($code, $message, ['status' => 404]);
    }
}

class NotSupported extends WP_Error
{
    /**
     * Constructor.
     *
     * @param $code
     * @param $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'not_supported';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, we do not support your request');
        }
        parent::__construct($code, $message, ['status' => 415]);
    }
}

class InternalServerError extends WP_Error
{
    /**
     * Constructor.
     *
     * @param $code
     * @param $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'internal_server_error';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, we have an internal error');
        }
        parent::__construct($code, $message, ['status' => 500]);
    }
}
