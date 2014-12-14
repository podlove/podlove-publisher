<?php
// devalidate caches when media file has changed
add_action( 'podlove_media_file_content_has_changed', function ( $media_file_id ) {
	if ( $media_file = Model\MediaFile::find_by_id( $media_file_id ) )
		if ( $episode = $media_file->episode() )
			$episode->delete_caches();
} );

// devalidate caches when episode content has changed
add_action( 'podlove_episode_content_has_changed', function( $episode_id ) {
	if ( $episode = Model\Episode::find_by_id( $episode_id ) )
		$episode->delete_caches();
} );