<?php

namespace Podlove\Webhook;

use Podlove\Http\Curl;

class Webhook
{
    private $event;
    private $payload;
    private $method = 'POST';

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function send($url)
    {
        $curl = new Curl();
        $curl->request($url, [
            'method' => $this->method,
            'body' => [
                'event' => $this->event,
                'payload' => json_encode($this->payload)
            ],
            '_redirection' => 0
        ]);
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function payload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    public function method($method)
    {
        $this->method = $method;

        return $this;
    }
}
