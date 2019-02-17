<?php
namespace Podlove\Modules\Shownotes;

use \Podlove\Modules\Shownotes\Model;
use \Podlove\Modules\Shownotes\Template;

class TemplateExtensions
{
    /**
     * Episode Shownotes (Beta Release only)
     *
     * **Examples**
     *
     * ```
     * <ul>
     * {% for entry in episode.shownotes %}
     *   <li class="psn-entry">
     *     {% if entry.type == "link" %}
     *       {% if entry.icon %}
     *         <img class="psn-icon" src="{{ entry.icon }}" />
     *       {% endif %}
     *       <a class="psn-link" href="{{ entry.url }}">{{ entry.title }}</a>
     *     {% elseif entry.type == "text" %}
     *       {{ entry.title }}
     *     {% endif %}
     *   </li>
     * {% endfor %}
     * </ul>
     * ```
     *
     * @accessor
     * @dynamicAccessor episode.shownotes
     */
    public static function accessorEpisodeShownotes($return, $method_name, \Podlove\Model\Episode $episode)
    {
        return $episode->with_blog_scope(function () use ($return, $method_name, $episode) {

            $entries = Model\Entry::find_all_by_property('episode_id', $episode->id);

            if (!is_array($entries)) {
                return [];
            }

            return array_map(function ($entry) {
                return new Template\Entry($entry);
            }, $entries);
        });
    }
}
