<?php
/**
 * Taxonomy: podcast_shows
 */
class Podlove_Shows_Taxonomy extends Podlove_Abstract_Taxonomy {
	
	function __construct( $do_init = true ) {
		$this->taxonomy_slug = 'podcast_shows';
		$this->field_keys = array(
			'subtitle' => array(
				'label'       => Podlove::t( 'Show Subtitle' ),
				'description' => Podlove::t( 'The subtitle is used by iTunes.' )
			),
			'label' => array(
				'label'       => Podlove::t( 'Show Label' ),
				'description' => Podlove::t( 'The show label is the prefix for every show title. It should be all caps and 3 or 4 characters long. Example: POD' )
			),
			// 'slug' => array( ... )
			'episode_prefix' => array(
				'label'       => Podlove::t( 'Episode Prefix' ),
				'description' => Podlove::t( 'Slug for file URI. Example: pod_' )
			),
			'media_file_base_uri' => array(
				'label'       => Podlove::t( 'Media File Base URI' ),
				'description' => Podlove::t( 'Example: http://cdn.example.com/pod/' )
			),
			'uri_delimiter' => array(
				'label'       => Podlove::t( 'URI Delimiter' ),
				'description' => Podlove::t( 'Example: -' )
			),
			'episode_number_length' => array(
				'label'       => Podlove::t( 'Episode Number Length' ),
				'description' => Podlove::t( 'If the episode number has fewer digits than defined here, it will be prefixed with leading zeroes. Example: 3' )
			)
		);
		
		if ( $do_init )
			$this->init();
	}
	
	/**
	 * Taxonomy for Shows.
	 * 
	 * @todo default UI sucks. show_ui => false and add a custom meta box.
	 * 	- Display all known shows with checkboxes.
	 * 	- Default: Select all (?) or configure which is/are default
	 */
	protected function register_taxonomy() {
		$show_taxonomy_labels = array(
			'name'                       => Podlove::t( 'Shows' ),
			'all_items'                  => Podlove::t( 'All Shows' ),
			'menu_name'                  => Podlove::t( 'Shows' ),
			'edit_item'                  => Podlove::t( 'Edit Show' ),
			'update_item'                => Podlove::t( 'Update Show' ),
			'parent_item'                => Podlove::t( 'Parent Show' ),
			'add_new_item'               => Podlove::t( 'Add New Show' ),
			'search_items'               => Podlove::t( 'Search Shows' ),
			'new_item_name'              => Podlove::t( 'New Show Name' ),
			'singular_name'              => Podlove::t( 'Show' ),
			'popular_items'              => Podlove::t( 'Popular Shows' ),
			'parent_item_colon'          => Podlove::t( 'Popular Shows' ),
			'add_or_remove_items'        => Podlove::t( 'Add or remove Shows' ),
			'choose_from_most_used'      => Podlove::t( 'Choose from most used' ),
			'separate_items_with_commas' => Podlove::t( 'Separate Shows with commas' )
		);
		
		$show_taxonomy_args = array(
			'public'            => true,
			'labels'            => $show_taxonomy_labels,
			'show_ui'           => true,
			'query_var'         => 'podlove',
			'hierarchical'      => false,
			'show_tagcloud'     => false,
			'show_in_nav_menus' => true,
		);

		register_taxonomy( $this->taxonomy_slug, array( 'podcast' ), $show_taxonomy_args );
	}
}