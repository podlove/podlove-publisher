<?php

namespace Podlove\Template;

/**
 * Asset Template Wrapper.
 *
 * @templatetag asset
 */
class Asset extends Wrapper
{
    /**
     * @var Podlove\Model\EpisodeAsset
     */
    private $asset;

    public function __construct(\Podlove\Model\EpisodeAsset $asset)
    {
        $this->asset = $asset;
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
        return $this->asset->title;
    }

    /**
     * ID.
     *
     * @accessor
     */
    public function identifier()
    {
        return $this->asset->identifier;
    }

    /**
     * Is the asset downloadable?
     *
     * @accessor
     */
    public function downloadable()
    {
        return (bool) $this->asset->downloadable;
    }

    /**
     * File type.
     *
     * @see  file_type
     * @accessor
     */
    public function fileType()
    {
        return new FileType($this->asset->file_type());
    }

    protected function getExtraFilterArgs()
    {
        return [$this->asset];
    }
}
