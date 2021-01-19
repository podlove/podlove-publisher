<?php

namespace Podlove\Modules\Transcripts\Template;

use Podlove\Modules\Contributors;
use Podlove\Template\Wrapper;

/**
 * Transcript Group Template Wrapper.
 *
 * @templatetag group
 */
class Group extends Wrapper
{
    private $lines;
    private $voice;
    private $contributor_id;

    public function __construct($lines, $contributor_id, $voice)
    {
        $this->lines = $lines;
        $this->contributor_id = $contributor_id;
        $this->voice = $voice;
    }

    /**
     * Items / Lines.
     *
     * @accessor
     */
    public function items()
    {
        return $this->lines;
    }

    /**
     * Start time in ms.
     *
     * @accessor
     */
    public function start()
    {
        $first_line = reset($this->lines);

        return $first_line->start();
    }

    /**
     * End time in ms.
     *
     * @accessor
     */
    public function end()
    {
        $last_line = end($this->lines);

        return $last_line->end();
    }

    /**
     * Voice / Contributor.
     *
     * @accessor
     */
    public function contributor()
    {
        if (!$this->contributor_id) {
            return null;
        }

        $contributor = Contributors\Model\Contributor::find_by_id($this->contributor_id);

        if (!$contributor) {
            return null;
        }

        return new Contributors\Template\Contributor($contributor);
    }

    public function voice()
    {
        if (!$this->voice) {
            return "Can't find the voice";
        }

        return $this->voice;
    }

    protected function getExtraFilterArgs()
    {
        return [$this->lines];
    }
}
