<?php

namespace Podlove\Modules\Shownotes\Template;

use Podlove\Template\Wrapper;

/**
 * Shownotes entry Template Wrapper.
 *
 * @templatetag entry
 */
class Entry extends Wrapper
{
    private $entry;

    public function __construct($entry)
    {
        $this->entry = $entry;
    }

    // /////////
    // Accessors
    // /////////

    /**
     * Title.
     *
     * @accessor
     */
    public function title()
    {
        return $this->entry->title;
    }

    /**
     * Description.
     *
     * @accessor
     */
    public function description()
    {
        return $this->entry->description;
    }

    /**
     * Canonical URL.
     *
     * Defaults to `original_url` if no canonical URL is available.
     *
     * @accessor
     */
    public function url()
    {
        return $this->entry->affiliate_url ?? $this->entry->url ?? $this->entry->original_url;
    }

    /**
     * Does this entry have an affiliate URL?
     *
     * @accessor
     */
    public function hasAffiliateUrl()
    {
        return (bool) $this->entry->affiliate_url;
    }

    /**
     * User provided URL.
     *
     * @accessor
     */
    public function originalUrl()
    {
        return $this->entry->original_url;
    }

    /**
     * Website name.
     *
     * @accessor
     */
    public function siteName()
    {
        return $this->entry->site_name;
    }

    /**
     * Website URL.
     *
     * Example: The site url of https://example.com/page?param=42 is https://example.com.
     *
     * @accessor
     */
    public function siteUrl()
    {
        return $this->entry->site_url;
    }

    /**
     * Icon URL.
     *
     * @accessor
     */
    public function icon()
    {
        return $this->entry->icon;
    }

    /**
     * Image URL.
     *
     * Open Graph or Twitter image.
     *
     * @see image
     * @accessor
     */
    public function image()
    {
        $data = \unserialize($this->entry->unfurl_data);

        if (!$data) {
            return false;
        }

        $image_url = $this->entry->image ?? $data['providers']['open_graph']['image'] ?? $data['providers']['twitter']['image:src'] ?? false;

        if ($image_url) {
            return new \Podlove\Template\Image((new \Podlove\Model\Image($image_url, $this->entry->title))->setWidth(1024));
        }

        return null;
    }

    /**
     * SVG Icon for entry type.
     *
     * @accessor
     *
     * @param mixed $size
     */
    public function typeIcon($size = 0)
    {
        if ($size && $size > 0) {
            $size_style = "width: {$size}px; height: {$size}px;";
        } else {
            $size_style = '';
        }

        switch ($this->entry->type) {
            case 'link':
                return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" style="fill: none !important; {$size_style}" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
SVG;

                break;

            case 'topic':
                return <<<SVG
  <svg xmlns="http://www.w3.org/2000/svg" style="fill: none !important; {$size_style}" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>
SVG;

                break;
        }

        return null;
    }

    /**
     * Type.
     *
     * Either "text" or "link".
     *
     * @accessor
     */
    public function type()
    {
        return $this->entry->type;
    }

    protected function getExtraFilterArgs()
    {
        return [$this->entry];
    }
}
