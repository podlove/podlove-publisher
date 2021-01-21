<?php

namespace Podlove\Template;

use Podlove\Model;
use PodlovePublisher_Vendor\Twig;

class TwigLoaderPodloveDatabase implements Twig\Loader\LoaderInterface
{
    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @throws Twig\Error\LoaderError When $name is not found
     *
     * @return Twig\Source
     */
    public function getSourceContext($name)
    {
        if ($template = Model\Template::find_one_by_title_with_fallback($name)) {
            if ($template->content) {
                return new Twig\Source($template->content, $name, '');
            }
        }

        throw new \PodlovePublisher_Vendor\Twig\Error\LoaderError(\sprintf('Unable to find the following template: "%s".', $name));
    }

    public function exists($name)
    {
        if (Model\Template::find_one_by_title_with_fallback($name)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name string The name of the template to load
     *
     * @return string The cache key
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string    $name The template name
     * @param timestamp $time The last modification time of the cached template
     */
    public function isFresh($name, $time)
    {
        return false;
    }
}
