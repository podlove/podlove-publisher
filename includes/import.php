<?php

// Fix WordPress post meta import for our custom GUID
// The importer inserts a post, which creates a new GUID. Then it adds the
// post metas resulting in two GUID entries. Here we make sure to only use
// the previous one.
add_action( 'added_post_meta', function ( $meta_id, $post_id, $meta_key, $_meta_value ) {
	
	if ( $meta_key !== '_podlove_guid' )
		return;

	$metas = get_post_meta( $post_id, '_podlove_guid' );
	if ( count( $metas ) > 1 ) {
		foreach ( $metas as $meta ) {
			if ( $meta !== $_meta_value ) {
				delete_post_meta( $post_id, $meta_key, $meta );
			}
		}
	}

}, 10, 4 );

// Ensure WordPress importer keeps the mapping id for old<->new post id.
// This is required for the Im/Export module. To avoid user errors, it is
// better to keep this behaviour in core.
add_filter( 'wp_import_post_meta', function($postmetas, $post_id, $post) {
	$postmetas[] = array(
		'key' => 'import_id',
		'value' => $post_id
	);
	return $postmetas;
}, 10, 3 );