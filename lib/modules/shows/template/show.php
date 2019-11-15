<?php
namespace Podlove\Modules\Shows\Template;

use Podlove\Template\Wrapper;

/**
 * Show Template Wrapper
 *
 * @templatetag show
 */
class Show extends Wrapper
{
    private $show;

    public function __construct(\Podlove\Modules\Shows\Model\Show $show)
    {
        $this->show = $show;
    }

    protected function getExtraFilterArgs()
    {
        return array($this->show);
    }

    // /////////
    // Accessors
    // /////////

    /**
     * Title
     *
     * @accessor
     */
    public function title()
    {
        return $this->show->title;
    }

    /**
     * Subtitle
     *
     * @accessor
     */
    public function subtitle()
    {
        return $this->show->subtitle;
    }

    /**
     * Summary
     *
     * @accessor
     */
    public function summary()
    {
        return $this->show->summary;
    }

    /**
     * Slug
     *
     * @accessor
     */
    public function slug()
    {
        return $this->show->slug;
    }

    /**
     * Language
     *
     * @accessor
     */
    public function language()
    {
        return $this->show->language;
    }

    /**
     * Image
     *
     * @accessor
     */
    public function image()
    {
        return new \Podlove\Template\Image($this->show->image());
    }
}
