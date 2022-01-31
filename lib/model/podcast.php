<?php

namespace Podlove\Model;

/**
 * Simplified model for podcast data.
 */
class Podcast implements Licensable
{
    use KeepsBlogReferenceTrait;

    /**
     * Contains property names.
     *
     * @var array
     */
    protected static $properties = [];

    /**
     * Contains property values.
     *
     * @var array
     */
    private $data = [];

    protected function __construct($blog_id)
    {
        $this->set_blog_id($blog_id);
        $this->fetch();
    }

    public function __clone()
    {
    }

    public function __set($name, $value)
    {
        if ($this->has_property($name)) {
            $this->set_property($name, $value);
        } else {
            $this->{$name} = $value;
        }
    }

    public function __get($name)
    {
        if ($this->has_property($name)) {
            return $this->get_property($name);
        }

        return $this->{$name};
    }

    public static function get($blog_id = null)
    {
        return new self($blog_id);
    }

    public static function name()
    {
        return 'podcast';
    }

    /**
     * Does the given property exist?
     *
     * @param string $name name of the property to test
     *
     * @return bool true if the property exists, else false
     */
    public function has_property($name)
    {
        return in_array($name, $this->property_names());
    }

    /**
     * Return a list of property names.
     *
     * @return array property names
     */
    public function property_names()
    {
        return array_map(function ($p) {
            return $p['name'];
        }, self::$properties);
    }

    /**
     * Define a property with by name.
     *
     * @param string $name Name of the property / column
     */
    public static function property($name)
    {
        if (!isset(self::$properties)) {
            self::$properties = [];
        }

        array_push(self::$properties, ['name' => $name]);
    }

    /**
     * Save current state to database.
     */
    public function save()
    {
        $this->set_property('media_file_base_uri', trailingslashit($this->media_file_base_uri));

        $this->with_blog_scope(function () {
            update_option('podlove_podcast', $this->data);

            do_action('podlove_model_save', $this);
            do_action('podlove_model_change', $this);
        });
    }

    /**
     * Generate a human readable title.
     *
     * Return name and, if available, the subtitle. Separated by a dash.
     *
     * @return string
     */
    public function full_title()
    {
        $t = $this->title;

        if ($this->subtitle) {
            $t = $t.' - '.$this->subtitle;
        }

        return $t;
    }

    public function get_license()
    {
        return new License('podcast', [
            'license_name' => $this->license_name,
            'license_url' => $this->license_url,
        ]);
    }

    public function get_license_picture_url()
    {
        return $this->get_license()->getPictureUrl();
    }

    public function get_license_html()
    {
        return $this->get_license()->getHtml();
    }

    public function get_url_template()
    {
        return $this->with_blog_scope(function () {
            return \Podlove\get_setting('website', 'url_template');
        });
    }

    public function get_feed_episode_title_variant()
    {
        if ($this->feed_episode_title_variant) {
            return $this->feed_episode_title_variant;
        }

        return 'blog';
    }

    public function get_feed_episode_title_template()
    {
        if ($this->feed_episode_title_template) {
            return $this->feed_episode_title_template;
        }

        return '%mnemonic%%episode_number% %episode_title%';
    }

    public function get_media_file_base_uri()
    {
        return apply_filters('podlove_media_file_base_uri', $this->media_file_base_uri);
    }

    /**
     * Fetch all valid feeds.
     *
     * A feed is valid if...
     *
     * - it has an asset assigned (and the asset has a filetype assigned)
     * - the slug in not empty
     *
     * @param mixed $args
     *
     * @return array list of feeds
     */
    public function feeds($args = [])
    {
        return $this->with_blog_scope(function () use ($args) {
            $discoverable_condition = '';
            if (isset($args['only_discoverable']) && $args['only_discoverable']) {
                $discoverable_condition = ' AND f.discoverable';
            }

            $sql = '
				SELECT
					f.*
				FROM
					'.Feed::table_name().' f
					JOIN '.EpisodeAsset::table_name().' a ON a.id = f.episode_asset_id
					JOIN '.FileType::table_name()." ft ON ft.id = a.file_type_id
				WHERE
					f.slug IS NOT NULL {$discoverable_condition}
				ORDER BY
					position ASC
			";

            return Feed::find_all_by_sql($sql);
        });
    }

    public function landing_page_url()
    {
        return $this->with_blog_scope(function () {
            return \Podlove\get_landing_page_url();
        });
    }

    public function cover_art()
    {
        return new Image($this->cover_image, $this->title);
    }

    public function has_cover_art()
    {
        return strlen(trim($this->cover_image)) > 0;
    }

    public function default_copyright_claim()
    {
        return '© '.date('Y').' '.($this->author_name ?? $this->title);
    }

    /**
     * Episodes.
     *
     * Filter and order episodes with parameters:
     *
     * - post_id: one episode matching the given post id
     * - post_ids: list of episodes matching the given list of post ids
     * - category: list of episodes matching the category slug
     * - show: list of episodes matching the show slug
     * - slug: one episode matching the given slug
     * - slugs: list of episodes matching the given list of slugs
     * - post_status: Publication status of the post. Defaults to 'publish'
     * - order: Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'.
     *   - 'ASC' - ascending order from lowest to highest values (1, 2, 3; a, b, c).
     *   - 'DESC' - descending order from highest to lowest values (3, 2, 1; c, b, a).
     * - orderby: Sort retrieved episodes by parameter. Defaults to 'publicationDate'.
     *   - 'publicationDate' - Order by publication date.
     *   - 'recordingDate' - Order by recording date.
     *   - 'title' - Order by title.
     *   - 'slug' - Order by episode slug.
     *     - 'limit' - Limit the number of returned episodes.
     *
     * @param mixed $args
     */
    public function episodes($args = [])
    {
        return $this->with_blog_scope(function () use ($args) {
            global $wpdb;

            // fetch single episodes
            if (isset($args['post_id'])) {
                return Episode::find_one_by_post_id($args['post_id']);
            }

            if (isset($args['slug'])) {
                return Episode::find_one_by_slug($args['slug']);
            }

            // eager load posts, which fills WP object cache, avoiding n+1 performance issues
            $posts = get_posts([
                'post_type' => 'podcast',
                'posts_per_page' => '-1',
            ]);

            // build conditions
            $where = '1 = 1';
            $joins = '';
            if (isset($args['post_ids'])) {
                $ids = array_filter( // remove "0"-IDs
                    array_map( // convert elements to integers
                        function ($n) {
                            return (int) trim($n);
                        },
                        $args['post_ids']
                    )
                );

                if (count($ids)) {
                    $where .= ' AND p.ID IN ('.implode(',', $ids).')';
                }
            }

            if (isset($args['slugs'])) {
                $slugs = array_filter( // remove empty slugs
                    array_map( // trim
                        function ($n) {
                            return "'".trim($n)."'";
                        },
                        $args['slugs']
                    )
                );

                if (count($slugs)) {
                    $where .= ' AND e.slug IN ('.implode(',', $slugs).')';
                }
            }

            if (isset($args['post_status']) && is_array($args['post_status'])) {
                $ins = [];
                foreach ($args['post_status'] as $status) {
                    $ins[] = '"'.$status.'"';
                }
                $where .= ' AND p.post_status IN ('.implode(',', $ins).')';
            } elseif (isset($args['post_status']) && in_array($args['post_status'], get_post_stati())) {
                $where .= " AND p.post_status = '".$args['post_status']."'";
            } else {
                $where .= " AND p.post_status = 'publish'";
            }

            if (isset($args['category']) && strlen($args['category'])) {
                $joins .= '
					JOIN '.$wpdb->term_relationships.' tr ON p.ID = tr.object_id
					JOIN '.$wpdb->term_taxonomy.' tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = "category"
					JOIN '.$wpdb->terms.' t ON t.term_id = tt.term_id AND t.slug = '.$wpdb->prepare('%s', $args['category']).'
				';
            }

            if (isset($args['show']) && strlen($args['show'])) {
                $joins .= '
					JOIN '.$wpdb->term_relationships.' tr_show ON p.ID = tr_show.object_id
					JOIN '.$wpdb->term_taxonomy.' tt_show ON tt_show.term_taxonomy_id = tr_show.term_taxonomy_id AND tt_show.taxonomy = "shows"
					JOIN '.$wpdb->terms.' t_show ON t_show.term_id = tt_show.term_id AND t_show.slug = '.$wpdb->prepare('%s', $args['show']).'
				';
            }

            // order
            $order_map = [
                'publicationDate' => 'p.post_date',
                'recordingDate' => 'e.recording_date',
                'slug' => 'e.slug',
                'title' => 'p.post_title',
            ];

            if (isset($args['orderby'], $order_map[$args['orderby']])) {
                $orderby = $order_map[$args['orderby']];
            } else {
                $orderby = $order_map['publicationDate'];
            }

            if (isset($args['order'])) {
                $args['order'] = strtoupper($args['order']);
                if (in_array($args['order'], ['ASC', 'DESC'])) {
                    $order = $args['order'];
                } else {
                    $order = 'DESC';
                }
            } else {
                $order = 'DESC';
            }

            if (isset($args['limit'])) {
                $limit = ' LIMIT '.(int) $args['limit'];
            } else {
                $limit = '';
            }

            $sql = '
				SELECT
                	e.*,
                    p.post_status
				FROM
					'.Episode::table_name().' e
					INNER JOIN '.$wpdb->posts.' p ON e.post_id = p.ID
					'.$joins.'
				WHERE
					'.$where.'
					AND p.post_type = "podcast"
				ORDER BY '.$orderby.' '.$order.
                $limit;

            $rows = $wpdb->get_results($sql);

            if (!$rows) {
                return [];
            }

            $episodes = [];
            foreach ($rows as $row) {
                $episode = new Episode();
                $episode->flag_as_not_new();
                foreach ($row as $property => $value) {
                    $episode->{$property} = $value;
                }
                $episodes[] = $episode;
            }

            // filter out invalid episodes
            return array_filter($episodes, function ($e) {
                return $e->is_valid();
            });
        });
    }

    private function set_property($name, $value)
    {
        $this->data[$name] = $value;
    }

    private function get_property($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Return a list of property dictionaries.
     *
     * @return array property list
     */
    private function properties()
    {
        if (!isset(self::$properties)) {
            self::$properties = [];
        }

        return self::$properties;
    }

    /**
     * Load podcast data.
     */
    private function fetch()
    {
        $this->data = $this->with_blog_scope(function () {
            return get_option('podlove_podcast', []);
        });
    }
}

Podcast::property('title');
Podcast::property('subtitle');
Podcast::property('itunes_type');
Podcast::property('cover_image');
Podcast::property('summary');
Podcast::property('mnemonic');
Podcast::property('author_name');
Podcast::property('owner_name');
Podcast::property('owner_email');
Podcast::property('publisher_name');
Podcast::property('publisher_url');
Podcast::property('license_type');
Podcast::property('license_name');
Podcast::property('license_url');
Podcast::property('license_cc_allow_modifications');
Podcast::property('license_cc_allow_commercial_use');
Podcast::property('license_cc_license_jurisdiction');
Podcast::property('category_1');
Podcast::property('category_2');
Podcast::property('category_3');
Podcast::property('explicit');
Podcast::property('label');
Podcast::property('episode_prefix');
Podcast::property('media_file_base_uri');
Podcast::property('uri_delimiter');
Podcast::property('limit_items');
Podcast::property('feed_episode_title_variant');
Podcast::property('feed_episode_title_template');
Podcast::property('language');
Podcast::property('complete');
Podcast::property('flattr'); // @deprecated since 2.3.0 (now: wp_option "podlove_flattr")
Podcast::property('plus_enable_proxy');
Podcast::property('funding_url');
Podcast::property('funding_label');
Podcast::property('copyright');
