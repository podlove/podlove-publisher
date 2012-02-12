<?php
/**
 * Taxonomy: podcast_file_formats
 */
class Podlove_File_Formats_Taxonomy extends Podlove_Abstract_Taxonomy {
	
	function __construct() {
		$this->taxonomy_slug = 'podcast_file_formats';
		$this->field_keys = array(
			// 'name' => array(
			// 	'label'       => Podlove::t( '' ),
			// 	'description' => Podlove::t( '' )
			// ),
			'type' => array(
				'label'       => Podlove::t( 'Format Type' ),
				'description' => Podlove::t( 'Example: audio' )
			),
			'mime-type' => array(
				'label'       => Podlove::t( 'Format Mime Type' ),
				'description' => Podlove::t( 'Example: audio/mpeg4' )
			),
			// 'slug' => array(
			// 	'label'       => Podlove::t( '' ),
			// 	'description' => Podlove::t( '' )
			// ),
			'extension' => array(
				'label'       => Podlove::t( 'Format Extension' ),
				'description' => Podlove::t( 'Example: m4a' )
			),
		);
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
			'name'                       => Podlove::t( 'Formats' ),
			'all_items'                  => Podlove::t( 'All Formats' ),
			'menu_name'                  => Podlove::t( 'Formats' ),
			'edit_item'                  => Podlove::t( 'Edit Format' ),
			'update_item'                => Podlove::t( 'Update Format' ),
			'parent_item'                => Podlove::t( 'Parent Format' ),
			'add_new_item'               => Podlove::t( 'Add New Format' ),
			'search_items'               => Podlove::t( 'Search Formats' ),
			'new_item_name'              => Podlove::t( 'New Format Name' ),
			'singular_name'              => Podlove::t( 'Format' ),
			'popular_items'              => Podlove::t( 'Popular Formats' ),
			'parent_item_colon'          => Podlove::t( 'Popular Formats' ),
			'add_or_remove_items'        => Podlove::t( 'Add or remove Formats' ),
			'choose_from_most_used'      => Podlove::t( 'Choose from most used' ),
			'separate_items_with_commas' => Podlove::t( 'Separate Formats with commas' )
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