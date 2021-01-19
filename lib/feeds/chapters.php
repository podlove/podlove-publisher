<?php

namespace Podlove\Feeds;

use Podlove\Model;

/**
 * Embed chapters into feed.
 */
class Chapters
{
    private $episode;

    public function __construct(Model\Episode $episode)
    {
        $this->episode = $episode;
    }

    /**
     * Render chapters into feed.
     *
     * @param string $style 'inline' or 'link'. Default: link
     */
    public function render($style = 'link')
    {
        $this->{'render_'.$style}();
    }

    public function render_inline()
    {
        echo $this->episode->get_chapters('psc');
    }

    public function render_link()
    {
        echo Model\Feed::get_link_tag([
            'prefix' => 'atom',
            'rel' => 'http://podlove.org/simple-chapters',
            'type' => '',
            'title' => '',
            'href' => get_permalink().'?chapters_format=psc',
        ]);
    }
}
