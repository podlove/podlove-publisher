<?php
/**
 * Taxonomy: podcast_feeds
 */
class Podlove_Feeds_Taxonomy extends Podlove_Abstract_Taxonomy {
	
	function __construct( $do_init = true ) {
		$this->taxonomy_slug = 'podlove_feeds';
		$this->field_keys = array(
			'feedslug' => array(
				'label'       => Podlove::t( 'Field Slug' ),
				'description' => Podlove::t( 'Will be used in Feed URL: /feed/"show slug"/"feed slug"/' )
			)
		);
		
		if ( $do_init )
			$this->init();
	}
	
	/**
	 * Taxonomy for Feeds.
	 * 
	 * @todo default UI sucks. show_ui => false and add a custom meta box.
	 * 	- Display all known feeds with checkboxes.
	 * 	- Default: Select all (?) or configure which is/are default
	 */
	protected function register_taxonomy() {
		$feed_taxonomy_labels = array(
			'name'                       => Podlove::t( 'Feeds' ),
			'all_items'                  => Podlove::t( 'All Feeds' ),
			'menu_name'                  => Podlove::t( 'Feeds' ),
			'edit_item'                  => Podlove::t( 'Edit Feed' ),
			'update_item'                => Podlove::t( 'Update Feed' ),
			'parent_item'                => Podlove::t( 'Parent Feed' ),
			'add_new_item'               => Podlove::t( 'Add New Feed' ),
			'search_items'               => Podlove::t( 'Search Feeds' ),
			'new_item_name'              => Podlove::t( 'New Feed Name' ),
			'singular_name'              => Podlove::t( 'Feed' ),
			'popular_items'              => Podlove::t( 'Popular Feeds' ),
			'parent_item_colon'          => Podlove::t( 'Popular Feeds' ),
			'add_or_remove_items'        => Podlove::t( 'Add or remove Feeds' ),
			'choose_from_most_used'      => Podlove::t( 'Choose from most used' ),
			'separate_items_with_commas' => Podlove::t( 'Separate Feeds with commas' )
		);
		
		$feed_taxonomy_args = array(
			'public'            => true,
			'labels'            => $feed_taxonomy_labels,
			'show_ui'           => true,
			'query_var'         => 'podlove',
			'hierarchical'      => false,
			'show_tagcloud'     => false,
			'show_in_nav_menus' => true,
		);

		register_taxonomy( $this->taxonomy_slug, array( 'podcast' ), $feed_taxonomy_args );
	}
}