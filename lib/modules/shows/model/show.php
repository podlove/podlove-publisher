<?php

namespace Podlove\Modules\Shows\Model;

use Podlove\Model\Episode;
use Podlove\Model\Image;
use Ramsey\Uuid\Uuid as UUID;

class Show
{
    public $id;
    public $title;
    public $subtitle;
    public $slug;
    public $summary;
    public $image;
    public $language;
    public $category;
    public $auphonic_preset;
    public $guid;

    /**
     * A show object consists of the following properties:
     *     - Title/Name
     *     - Subtitle*
     *     - Slug
     *     - Description
     *     - Image*
     *     - Language*
     *     - Category*.
     *
     * Properties marked with * are meta
     */
    public function __construct()
    {
        $this->id = false;
        $this->title = '';
        $this->subtitle = '';
        $this->slug = '';
        $this->summary = '';
        $this->image = '';
        $this->language = '';
        $this->category = '';
        $this->auphonic_preset = '';
        $this->guid = '';
    }

    /**
     * Searches all Show terms and returns all values matching $property == $value.
     *
     * @param string $property
     * @param string $value
     *
     * @return array
     */
    public static function find_all_terms_by_property($property = false, $value = false)
    {
        $existing_properties = ['title', 'description', 'slug', 'id'];
        $existing_meta_properties = ['image', 'language', 'subtitle', 'category'];
        $search_parameters = [
            'taxonomy' => 'shows',
            'hide_empty' => false,
        ];

        if (in_array($property, $existing_meta_properties)) {
            $search_parameters['meta_key'] = $property;
            $search_parameters['meta_value'] = $value;
        }

        if (in_array($property, $existing_properties)) {
            switch ($property) {
                case 'id':
                    $search_parameters['term_taxonomy_id'] = $value;

                    break;
                case 'title':
                    $search_parameters['name'] = $value;

                    break;
                case 'description':
                    $search_parameters['description__like'] = $value;

                    break;

                default:
                    $search_parameters[$property] = $value;

                    break;
            }
        }

        return self::format_terms(get_terms($search_parameters));
    }

    public static function find_one_term_by_property($property = false, $value = false)
    {
        $terms = self::find_all_terms_by_property($property, $value);

        if (is_array($terms) && !empty($terms)) {
            return $terms[0]; // returns first element only
        }
    }

    public static function find_by_id($id)
    {
        return self::format_term(get_term($id, 'shows'));
    }

    public static function find_one_by_episode_id($episode_id)
    {
        $episode = Episode::find_by_id($episode_id);

        return self::find_one_by_post_id($episode->post_id);
    }

    public static function find_one_by_post_id($post_id)
    {
        $postterms = get_the_terms($post_id, 'shows');

        return isset($postterms[0]) ? self::find_by_id($postterms[0]->term_id) : false;
    }

    public static function all()
    {
        return self::find_all_terms_by_property();
    }

    /**
     * Returns terms as a well-defined object including all meta data.
     *
     * @param mixed $terms Term(s) to be formated
     *
     * @return mixed Returns an array if an array or object based on the type of $terms
     */
    public static function format_terms($terms)
    {
        if (is_array($terms)) {
            return array_map([__CLASS__, 'format_term'], $terms);
        }

        return self::format_term($terms);
    }

    /**
     * Convert show term to instance of this show class.
     *
     * @param [type] $term [description]
     *
     * @return [type] [description]
     */
    public static function format_term($term)
    {
        $show = new Show();
        $show->id = $term->term_id;
        $show->title = $term->name;
        $show->subtitle = get_term_meta($term->term_id, 'subtitle', true);
        $show->slug = $term->slug;
        $show->summary = $term->description;
        $show->image = get_term_meta($term->term_id, 'image', true);
        $show->language = get_term_meta($term->term_id, 'language', true);
        $show->category = get_term_meta($term->term_id, 'category', true);
        $show->auphonic_preset = get_term_meta($term->term_id, 'auphonic_preset', true);
        $show->guid = get_term_meta($term->term_id, 'guid', true);

        return $show;
    }

    public function image()
    {
        return new Image($this->image, $this->title);
    }

    public static function generate_guid($term_id)
    {
        if (!get_term_meta($term_id, 'guid', true)) {
            update_term_meta($term_id, 'guid', UUID::uuid4());
        }
    }
}
