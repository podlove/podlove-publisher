<?php

namespace Podlove\Modules\Social;

use Podlove\Modules\Social\Model\ContributorService;
use Podlove\Modules\Social\Model\ShowService;

class TemplateExtensions
{
    /**
     * List of service profiles.
     *
     * Parameters:
     *
     * - **category:** (optional) "social", "donation" or "all". Default: "all"
     * - **type:**     (optional) Filter services by type. List of all service types: 500px, about.me, amazon wishlist, app.net, auphonic credits, bandcamp, bitbucket, bitcoin, deviantart, diaspora, dogecoin, dribbble, email, facebook, flickr, foursquare, generic wishlist, github, gittip, google+, instagram, jabber, last.fm, linkedin, litecoin, openstreetmap, orcid, patreon, paypal, miiverse, pinboard, pinterest, playstation network, researchgate, scous, skype, soundcloud, soup, steam, steam wishlist, thomann wishlist, tumblr, twitch, twitter, vimeo, website, xbox live, xing, youtube
     *
     * Example:
     *
     * ```html
     * {% for service in contributor.services({category: "social"}) %}
     *   <a target="_blank" title="{{ service.title }}" href="{{ service.profileUrl }}">
     *		{{ service.image.html({width: 20}) }}
     *   </a>
     * {% endfor %}
     * ```
     *
     * @accessor
     * @dynamicAccessor contributor.services
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $contributor
     * @param mixed $contribution
     * @param mixed $args
     */
    public static function accessorContributorServices($return, $method_name, $contributor, $contribution, $args = [])
    {
        return $contributor->with_blog_scope(function () use ($contributor, $args) {
            $category = (isset($args['category']) && in_array($args['category'], ['social', 'donation', 'all'])) ? $args['category'] : 'all';

            if ($category == 'all') {
                $services = ContributorService::find_all_by_contributor_id($contributor->id);
            } else {
                $services = ContributorService::find_by_contributor_id_and_category($contributor->id, $category);
            }

            $services = array_filter($services, function ($s) use ($args) {
                return $s->service_id > 0;
            });

            if (isset($args['type']) && $args['type']) {
                $services = array_filter($services, function ($s) use ($args) {
                    return $s->get_service()->type == $args['type'];
                });
            }

            usort($services, function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }

                return $a->position < $b->position ? -1 : 1;
            });

            return array_map(function ($service) {
                return new Template\Service($service, $service->get_service());
            }, $services);
        });
    }

    /**
     * List of service profiles.
     *
     * Parameters:
     *
     * - **category:** (optional) "social", "donation" or "all". Default: "all"
     * - **type:**     (optional) Filter services by type. List of all service types: 500px, amazon wishlist, app.net, bandcamp, bitbucket, bitcoin, deviantart, diaspora, dogecoin, dribbble, facebook, flickr, generic wishlist, github, google+, instagram, jabber, last.fm, linkedin, litecoin, openstreetmap, paypal, miiverse, pinboard, pinterest, playstation network, skype, soundcloud, soup, steam, steam wishlist, thomann wishlist, twitch, tumblr, twitter, website, xbox live, xing, youtube
     *
     * Example:
     *
     * ```html
     * {% for service in podcast.services({category: "social"}) %}
     *   <a target="_blank" title="{{ service.title }}" href="{{ service.profileUrl }}">
     *		{{ service.image.html({width: 20}) }}
     *   </a>
     * {% endfor %}
     * ```
     *
     * @accessor
     * @dynamicAccessor podcast.services
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $podcast
     * @param mixed $args
     */
    public static function accessorPodcastServices($return, $method_name, $podcast, $args = [])
    {
        return $podcast->with_blog_scope(function () use ($args) {
            $category = isset($args['category']) && in_array($args['category'], ['social', 'donation', 'all']) ? $args['category'] : 'all';

            if ($category == 'all') {
                $services = ShowService::all('ORDER BY position ASC');
            } else {
                $services = ShowService::find_by_category($category);
            }

            if (isset($args['type']) && $args['type']) {
                $services = array_filter($services, function ($s) use ($args) {
                    return $s->get_service()->type == $args['type'];
                });
            }

            return array_map(function ($service) {
                return new Template\Service($service, $service->get_service());
            }, $services);
        });
    }
}
