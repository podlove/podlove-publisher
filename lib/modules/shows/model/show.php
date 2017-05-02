<?php 
namespace Podlove\Modules\Shows\Model;
use \Podlove\Model\Image;
use \Podlove\Model\Episode;

class Show {

	/**
	 * A show object consists of the following properties:
	 * 	- Title/Name
	 * 	- Subtitle*
	 * 	- Slug
	 * 	- Description
	 * 	- Image*
	 * 	- Language*
	 * 
	 * Properties marked with * are meta
	 */	
	public function __construct() {
		$this->id = false;
		$this->title = '';
		$this->subtitle = '';
		$this->slug = '';
		$this->summary = '';
		$this->image = '';
		$this->language = '';
	}

	/*
	 * Searches all Show terms and returns all values matching $property == $value
	 * 
	 * @param string $property
	 * @param string $value
	 * 
	 * @return array
	 */
	public static function find_all_terms_by_property($property = false, $value = false) {
		$existing_properties =  [ 'title', 'description', 'slug', 'id' ];
		$existing_meta_properties = ['image', 'language', 'subtitle'];
		$search_parameters = array(
				'taxonomy' => 'shows',
				'hide_empty' => false
			);

		if ( in_array($property, $existing_meta_properties) ) {
			$search_parameters['meta_key'] = $property;
			$search_parameters['meta_value'] = $value;
		}

		if ( in_array($property, $existing_properties) ) {
			switch ($property) {
				case 'id':
					$search_parameters['term_id'] = $value;
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

		return self::format_terms( get_terms($search_parameters) );
	}

	public static function find_one_term_by_property($property = false, $value = false) {
		$terms = self::find_all_terms_by_property($property, $value);

		if ( is_array($terms) ) {
			return $terms[0]; // returns first element only
		} else {
			return;
		}
	}

	public static function find_by_id($id) {
		return self::find_one_term_by_property('id', $id);
	}

	public static function find_one_by_episode_id($episode_id) {
		$episode = Episode::find_by_id($episode_id);
		
		return self::find_one_by_post_id($episode->post_id);
	}

	public static function find_one_by_post_id($post_id) {
		$postterms = get_the_terms( $post_id, 'shows' );
		
		return ( isset($postterms[0]) ? self::find_by_id($postterms[0]->term_id) : false );
	}

	public static function all() {
		return self::find_all_terms_by_property();
	}

	/*
	 * Returns terms as a well-defined object including all meta data.
	 * 
	 * @param mixed $terms Term(s) to be formated
	 * 
	 * @return mixed Returns an array if an array or object based on the type of $terms
	 */
	public static function format_terms($terms) {
		$format_terms = function($term) {
			$term_object = new Show;
			$term_object->id = $term->term_id;
			$term_object->title = $term->name;
			$term_object->subtitle = get_term_meta( $term->term_id, 'subtitle', true );
			$term_object->slug = $term->slug;
			$term_object->summary = $term->description;
			$term_object->image = get_term_meta( $term->term_id, 'image', true );
			$term_object->language = get_term_meta( $term->term_id, 'language', true );

			return $term_object;
		};

		if ( ! is_array($terms) ) {
			return $format_terms($terms);
		} else {
			$formated_terms = [];

			foreach ($terms as $term) {
				$formated_terms[] = $format_terms($term);
			}

			return $formated_terms;
		}
	}

	public function image() {
		return new Image($this->image, $this->title);
	}

}