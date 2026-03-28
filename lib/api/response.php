<?php

namespace Podlove\Api\Response;

class OkResponse extends \WP_REST_Response
{
    /**
     * Constructor.
     *
     * @param null|mixed $data
     */
    public function __construct($data = null, array $headers = [])
    {
        parent::__construct($data, 200, $headers);
    }
}

class CreateResponse extends \WP_REST_Response
{
    /**
     * Constructor.
     *
     * @param null|mixed $data
     */
    public function __construct($data = null, array $headers = [])
    {
        parent::__construct($data, 201, $headers);
    }
}
