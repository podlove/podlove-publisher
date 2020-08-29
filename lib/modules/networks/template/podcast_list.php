<?php

namespace Podlove\Modules\Networks\Template;

use Podlove\Template\Wrapper;

/**
 * List Template Wrapper.
 *
 * Requires the "Networks" module.
 *
 * @templatetag list
 */
class PodcastList extends Wrapper
{
    /**
     * @var \Podlove\Modules\Networks\Model\Network
     */
    private $list;

    public function __construct($list)
    {
        $this->list = $list;
    }

    // /////////
    // Accessors
    // /////////

    /**
     * List title.
     *
     * @accessor
     */
    public function title()
    {
        return $this->list->title;
    }

    /**
     * List subtitle.
     *
     * @accessor
     */
    public function subtitle()
    {
        return $this->list->subtitle;
    }

    /**
     * List summary.
     *
     * @accessor
     */
    public function summary()
    {
        return $this->list->description;
    }

    /**
     * List description.
     *
     * @deprecated since 2.3, use summary instead
     */
    public function description()
    {
        return $this->list->description;
    }

    /**
     * List logo.
     *
     * @accessor
     */
    public function logo()
    {
        if ($this->list->logo) {
            $logo = new \Podlove\Model\Image($this->list->logo);

            return new \Podlove\Template\Image($logo);
        }

        return null;
    }

    /**
     * List url.
     *
     * @accessor
     */
    public function url()
    {
        return $this->list->url;
    }

    /**
     * List podcasts.
     *
     * @accessor
     */
    public function podcasts()
    {
        return array_map(function ($podcast) {
            return new \Podlove\Template\Podcast($podcast);
        }, $this->list->podcasts());
    }

    /**
     * List latest episodes from network.
     *
     * - limit:   Maximum number of episodes. Default: 10.
     * - orderby: Order episodes by 'post_date', 'post_title', 'ID' or 'comment_count'. Default: 'post_date'.
     * - order: Designates the ascending or descending order of the 'orderby' parameter. Default: 'DESC'.
     *   - 'ASC' - ascending order from lowest to highest values (1, 2, 3; a, b, c).
     *   - 'DESC' - descending order from highest to lowest values (3, 2, 1; c, b, a).
     *
     * @accessor
     *
     * @param mixed $args
     */
    public function episodes($args = [])
    {
        $number_of_episodes = isset($args['limit']) && is_numeric($args['limit']) ? $args['limit'] : 10;
        $orderby = isset($args['orderby']) && $args['orderby'] ? $args['orderby'] : 'post_date';
        $order = isset($args['order']) && $args['order'] ? $args['order'] : 'DESC';

        return $this->list->latest_episodes($number_of_episodes, $orderby, $order);
    }

    protected function getExtraFilterArgs()
    {
        return [];
    }
}
