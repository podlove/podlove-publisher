<?php
/**
 * Taxonomy: podcast_shows
 */
class Podlove_Shows_Taxonomy {
	
	private static $field_keys = array(
		'subtitle',
		'show_label',
		'episode_number_length'
	);
	
	function __construct() {
		$this->register_taxonomy();
		add_action( 'podcast_shows_edit_form_fields', array( $this, 'add_form_fields' ), 10, 2 );
		add_filter( 'edit_term', array( $this, 'save' ), 10, 3 );
	}
	
	/**
	 * Get field data for all podlove_shows.
	 * 
	 * @return array
	 */
	private function get_all_fields() {
		$fields = get_option( 'podlove_shows_taxonomy_fields' );
		
		if ( empty( $fields ) || ! is_array( $fields ) )
			$fields = array();
			
		return $fields;
	}
	
	/**
	 * Get fields for a term id.
	 * 
	 * @param int $term_id
	 * @return array
	 */
	private function get_fields( $term_id ) {
		$fields = $this->get_all_fields();
			
		if ( empty( $fields[ $term_id ] ) )
			$fields[ $term_id ] = array();
			
		foreach ( self::$field_keys as $key ) {
			if ( empty( $fields[ $term_id ][ $key ] ) ) {
				$fields[ $term_id ][ $key ] = NULL;
			}
		}
		
		return $fields[ $term_id ];
	}
	
	/**
	 * Hook: Save a term.
	 */
	public function save( $term_id, $tt_id, $taxonomy ) {
		global $wpdb;
		
		if ( $taxonomy != 'podcast_shows' )
			return;
		
		$fields = $this->get_all_fields();
		$fields[ $term_id ] = $_POST[ 'show' ];
		
		update_option( 'podlove_shows_taxonomy_fields', $fields );
	}
	
	public function add_form_fields( $taxonomy, $taxonomy_slug ) {
		$fields = $this->get_fields( $taxonomy->term_id );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="subtitle"><?php echo Podlove::t( 'Subtitle' ); ?></label>
			</th>
			<td>
				<input type="text" name="show[subtitle]" value="<?php echo $fields[ 'subtitle' ]; ?>" id="subtitle">
				<br />
				<span class="description"><?php echo Podlove::t('The subtitle is used by iTunes.'); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="show_label"><?php echo Podlove::t( 'Show Label' ); ?></label>
			</th>
			<td>
				<input type="text" name="show[show_label]" value="<?php echo $fields[ 'show_label' ]; ?>" id="show_label">
				<br />
				<span class="description"><?php echo Podlove::t('The show label is the prefix for every show title. It should be all caps and 3 or 4 characters long.'); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="episode_number_length"><?php echo Podlove::t( 'Episode Number Length' ); ?></label>
			</th>
			<td>
				<input type="text" name="show[episode_number_length]" value="<?php echo $fields[ 'episode_number_length' ]; ?>" id="episode_number_length">
				<br />
				<span class="description"><?php echo Podlove::t('If the episode number has fewer digits than defined here, it will be prefixed with leading zeroes.'); ?></span>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Taxonomy for Shows.
	 * 
	 * @todo default UI sucks. show_ui => false and add a custom meta box.
	 * 	- Display all known shows with checkboxes.
	 * 	- Default: Select all (?) or configure which is/are default
	 */
	private function register_taxonomy() {
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

		register_taxonomy( 'podcast_shows', array( 'podcast' ), $show_taxonomy_args );
	}
}