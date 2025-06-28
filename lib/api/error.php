<?php

namespace Podlove\Api\Error;

class ForbiddenAccess extends \WP_Error
{
    /**
     * Constructor.
     *
     * @param mixed $code
     * @param mixed $message
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

class NotFound extends \WP_Error
{
    /**
     * Constructor.
     *
     * @param mixed $code
     * @param mixed $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'rest_not_found';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, we did not find the requested resource');
        }
        parent::__construct($code, $message, ['status' => 404]);
    }
}

class NotFoundEpisode extends \WP_Error
{
    /**
     * Constructor.
     *
     * @param mixed $episode_id
     */
    public function __construct($episode_id)
    {
        $message = 'sorry, we did not find the episode with ID '.$episode_id;
        parent::__construct('not_found', esc_html__($message), ['status' => 404]);
    }
}

class NotSupported extends \WP_Error
{
    /**
     * Constructor.
     *
     * @param mixed $code
     * @param mixed $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'rest_not_supported';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, we do not support your request');
        }
        parent::__construct($code, $message, ['status' => 415]);
    }
}

class InternalServerError extends \WP_Error
{
    /**
     * Constructor.
     *
     * @param mixed $code
     * @param mixed $message
     */
    public function __construct($code = '', $message = '')
    {
        if (strlen($code) == 0) {
            $code = 'rest_internal_server_error';
        }
        if (strlen($message) == 0) {
            $message = esc_html__('sorry, we have an internal error');
        }
        parent::__construct($code, $message, ['status' => 500]);
    }
}
