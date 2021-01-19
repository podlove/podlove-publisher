<?php

namespace Podlove\Template;

/**
 * License Template Wrapper.
 *
 * @templatetag license
 */
class License extends Wrapper
{
    /**
     * @var Podlove\Model\License
     */
    private $license;

    public function __construct(\Podlove\Model\License $license)
    {
        $this->license = $license;
    }

    // /////////
    // Accessors
    // /////////

    /**
     * Name.
     *
     * @accessor
     */
    public function name()
    {
        return $this->license->getName();
    }

    /**
     * URL.
     *
     * @accessor
     */
    public function url()
    {
        return $this->license->getUrl();
    }

    /**
     * Image URL.
     *
     * @accessor
     */
    public function imageUrl()
    {
        if ($this->license->isCreativeCommons() == 'cc') {
            return $this->license->getPictureUrl();
        }

        return '';
    }

    /**
     * Is this a creative commons license?
     *
     * @accessor
     */
    public function creativeCommons()
    {
        return $this->license->isCreativeCommons();
    }

    /**
     * Is the license valid? Is all required data available?
     *
     * @accessor
     */
    public function valid()
    {
        return $this->url() && $this->name();
    }

    protected function getExtraFilterArgs()
    {
        return [$this->license];
    }
}
