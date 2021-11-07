<?php

namespace Podlove\Api\Error;

use WP_Error;

class ForbiddenAccess extends WP_Error
{
    /**
     * Constructor
     * 
     * @param $code
     * @param $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0)
            $code = 'rest_forbidden';
        if (strlen($message) == 0)
            $message = esc_html__('sorry, you do not have permissions to use this REST API endpoint');
        parent::__construct($code, $message, ['status' => 401]);
    }
}

class NotFound extends WP_Error
{
    /**
     * Constructor
     * 
     * @param $code
     * @param $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0)
            $code = 'not_found';
        if (strlen($message) == 0)
            $message = esc_html__('sorry, we do not found the requested resource');
        parent::__construct($code, $message, ['status' => 404]);
    }
}
