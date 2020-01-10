<?php

namespace Podlove\Modules\Shownotes\Template;

use Podlove\Template\Wrapper;

/**
 * Shownotes entry Template Wrapper
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

    protected function getExtraFilterArgs()
    {
        return array($this->entry);
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
        return $this->entry->title;
    }

    /**
     * Description
     *
     * @accessor
     */
    public function description()
    {
        return $this->entry->description;
    }

    /**
     * Canonical URL
     *
     * Defaults to `original_url` if no canonical URL is available.
     *
     * @accessor
     */
    public function url()
    {
        return $this->entry->affiliate_url
            ?? $this->entry->url
            ?? $this->entry->original_url;
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
     * User provided URL
     *
     * @accessor
     */
    public function originalUrl()
    {
        return $this->entry->original_url;
    }

    /**
     * Website name
     *
     * @accessor
     */
    public function siteName()
    {
        return $this->entry->site_name;
    }

    /**
     * Website URL
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
     * Icon URL
     *
     * @accessor
     */
    public function icon()
    {
        return $this->entry->icon;
    }

    /**
     * Type
     *
     * Either "text" or "link".
     *
     * @accessor
     */
    public function type()
    {
        return $this->entry->type;
    }
}
