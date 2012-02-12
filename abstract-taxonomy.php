<?php
abstract class Podlove_Abstract_Taxonomy {
	
	protected $field_keys;
	protected $taxonomy_slug;
	
	/**
	 * Initialize Taxonomy.
	 * 
	 * Call from derived constructor.
	 */
	protected final function init() {
		$this->register_taxonomy();
		add_action( $this->taxonomy_slug . '_edit_form_fields', array( $this, 'add_form_fields' ), 10, 2 );
		add_filter( 'edit_term', array( $this, 'save' ), 10, 3 );
	}
	
	protected abstract function register_taxonomy();
	
	/**
	 * Get fields for a term id.
	 * 
	 * @param int $term_id
	 * @return array
	 */
	protected final function get_fields( $term_id ) {
		$fields = $this->get_all_fields();
			
		if ( empty( $fields[ $term_id ] ) )
			$fields[ $term_id ] = array();
			
		foreach ( $this->field_keys as $key => $_ ) {
			if ( empty( $fields[ $term_id ][ $key ] ) ) {
				$fields[ $term_id ][ $key ] = NULL;
			}
		}
		
		return $fields[ $term_id ];
	}
	
	/**
	 * Get field data for all podlove_shows.
	 * 
	 * @return array
	 */
	protected final function get_all_fields() {
		$fields = get_option( $this->taxonomy_slug . '_taxonomy_fields' );

		if ( empty( $fields ) || ! is_array( $fields ) )
			$fields = array();

		return $fields;
	}

	/**
	 * Hook: Save a term.
	 */
	public final function save( $term_id, $tt_id, $taxonomy ) {
		global $wpdb;

		if ( $taxonomy != $this->taxonomy_slug )
			return;

		$fields = $this->get_all_fields();
		$fields[ $term_id ] = $_POST[ $this->taxonomy_slug ];

		update_option( $this->taxonomy_slug . '_taxonomy_fields', $fields );
	}
	
	public final function add_form_fields( $taxonomy, $taxonomy_slug ) {
		$fields = $this->get_fields( $taxonomy->term_id );
		foreach ( $fields as $key => $value ): ?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="<?php echo $key; ?>"><?php echo $this->field_keys[ $key ][ 'label' ]; ?></label>
				</th>
				<td>
					<input type="text" name="<?php echo $this->taxonomy_slug; ?>[<?php echo $key; ?>]" value="<?php echo $value; ?>" id="<?php echo $key; ?>">
					<br />
					<span class="description"><?php echo $this->field_keys[ $key ][ 'description' ]; ?></span>
				</td>
			</tr>
		<?php
		endforeach;
	}
}