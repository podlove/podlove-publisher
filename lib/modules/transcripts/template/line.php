<?php
namespace Podlove\Modules\Transcripts\Template;

use Podlove\Template\Wrapper;

/**
 * Transcript Line Template Wrapper
 *
 * @templatetag line
 */
class Line extends Wrapper
{

    private $line;

    public function __construct($line)
    {
        $this->line = $line;
    }

    protected function getExtraFilterArgs()
    {
        return array($this->line);
    }

    // /////////
    // Accessors
    // /////////

    /**
     * Content
     *
     * @accessor
     */
    public function content()
    {
        return $this->line['text'];
    }

    /**
     * Start time in ms
     *
     * @accessor
     */
    public function start()
    {
        // fixme: this is silly, Duration should take ms as parameter, not a whole episode object
        $episode           = new \Podlove\Model\Episode;
        $episode->duration = $this->line['start_ms'] / 1000;

        return new \Podlove\Template\Duration($episode);
    }

    /**
     * End time in ms
     *
     * @accessor
     */
    public function end()
    {
        $episode           = new \Podlove\Model\Episode;
        $episode->duration = $this->line['end_ms'] / 1000;

        return new \Podlove\Template\Duration($episode);
    }
}
