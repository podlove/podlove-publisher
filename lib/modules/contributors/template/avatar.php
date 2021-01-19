<?php

namespace Podlove\Modules\Contributors\Template;

use Podlove\Template\Image;
use Podlove\Template\Wrapper;

/**
 * Contributor Avatar Template Wrapper.
 *
 * Requires the "Contributor" module.
 *
 * @deprecated since 2.2.0
 * @templatetag avatar
 */
class Avatar extends Wrapper
{
    private $contributor;

    public function __construct($contributor)
    {
        $this->contributor = $contributor;
    }

    // /////////
    // Accessors
    // /////////

    /**
     * Avatar image URL.
     *
     * Dimensions default to 50x50px.
     * Change it via parameter: `avatar.url(32)`
     *
     * @accessor
     *
     * @param mixed $size
     */
    public function url($size = 50)
    {
        return $this->contributor->avatar()->setWidth($size)->url();
    }

    /**
     * Avatar image tag.
     *
     * Dimensions default to 50x50px.
     * Change it via parameter: `avatar.html({width: 100})`
     *
     * @accessor
     *
     * @see image
     *
     * @param mixed $args
     */
    public function html($args = [])
    {
        if (!isset($args['width'])) {
            $args['width'] = 50;
        }

        $image = new Image($this->contributor->avatar());

        return $image->html($args);
    }

    protected function getExtraFilterArgs()
    {
        return [$this->contributor];
    }
}
